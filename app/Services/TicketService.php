<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\BuildingRole;
use App\Enums\TicketStatus;
use App\Enums\TicketVisibility;
use App\Events\TicketAssigned;
use App\Events\TicketCommented;
use App\Events\TicketCreated;
use App\Events\TicketResolved;
use App\Events\TicketStatusChanged;
use App\Models\Apartment;
use App\Models\Building;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use App\Repositories\Contracts\TicketRepositoryInterface;
use App\Support\Images\ImageResizer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class TicketService
{
    public function __construct(
        private readonly TicketRepositoryInterface $tickets,
        private readonly ImageResizer $imageResizer,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(Building $building, User $reporter, array $data): Ticket
    {
        return DB::transaction(function () use ($building, $reporter, $data): Ticket {
            $apartment = $this->resolveApartment($building, $data['apartment_id'] ?? null);
            $assigneeId = $this->resolveAssignee($building, $data['assigned_to'] ?? null);
            $status = $this->normalizeStatus($data['status'] ?? TicketStatus::New);

            $ticket = $this->tickets->create([
                'apartment_id' => $apartment?->getKey(),
                'assigned_to' => $assigneeId,
                'building_id' => $building->getKey(),
                'description' => $data['description'],
                'priority' => $data['priority'],
                'reported_by' => $reporter->getKey(),
                'resolved_at' => null,
                'status' => $status,
                'title' => $data['title'],
                'visibility' => $this->normalizeVisibility($data['visibility'] ?? TicketVisibility::Private),
            ]);

            $this->storeAttachments($ticket, $data['attachments'] ?? []);

            $ticket->statusHistory()->create([
                'changed_by' => $reporter->getKey(),
                'from_status' => null,
                'note' => 'Ticket created.',
                'to_status' => $ticket->status,
            ]);

            $ticket->load(['apartment', 'reporter', 'assignee', 'attachments', 'comments.user', 'statusHistory.actor']);

            event(new TicketCreated($ticket));

            return $ticket;
        });
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(Ticket $ticket, User $actor, array $data): Ticket
    {
        return DB::transaction(function () use ($ticket, $actor, $data): Ticket {
            $fromStatus = $ticket->status;
            $fromAssigneeId = $ticket->assigned_to;
            $status = array_key_exists('status', $data)
                ? $this->normalizeStatus($data['status'])
                : $ticket->status;
            $assigneeId = array_key_exists('assigned_to', $data)
                ? $this->resolveAssignee($ticket->building, $data['assigned_to'])
                : $ticket->assigned_to;

            $updatedTicket = $this->tickets->update($ticket, [
                'assigned_to' => $assigneeId,
                'description' => $data['description'] ?? $ticket->description,
                'priority' => $data['priority'] ?? $ticket->priority,
                'resolved_at' => $status === TicketStatus::Resolved ? now() : null,
                'status' => $status,
                'title' => $data['title'] ?? $ticket->title,
                'visibility' => $this->normalizeVisibility(
                    array_key_exists('visibility', $data) ? $data['visibility'] : $ticket->visibility,
                ),
            ]);

            $this->storeAttachments($updatedTicket, $data['attachments'] ?? []);

            $statusChanged = $fromStatus !== $updatedTicket->status;
            $note = $data['status_note'] ?? null;

            if ($statusChanged) {
                $updatedTicket->statusHistory()->create([
                    'changed_by' => $actor->getKey(),
                    'from_status' => $fromStatus,
                    'note' => $note ?? 'Ticket status updated.',
                    'to_status' => $updatedTicket->status,
                ]);
            }

            $updatedTicket->load(['apartment', 'reporter', 'assignee', 'attachments', 'comments.user', 'statusHistory.actor']);

            // Status events: resolved gets its own dedicated copy. Cancelled is intentionally
            // silent (terminal state, no actionable info for participants). Anything else
            // uses the generic status-change event.
            if ($statusChanged) {
                if ($updatedTicket->status === TicketStatus::Resolved) {
                    event(new TicketResolved($updatedTicket, $actor, $note));
                } elseif ($updatedTicket->status !== TicketStatus::Cancelled) {
                    event(new TicketStatusChanged($updatedTicket, $actor, $fromStatus, $updatedTicket->status, $note));
                }
            }

            // Assignment event: only when assignee actually changed to a (new) non-null user.
            $assigneeChanged = $fromAssigneeId !== $updatedTicket->assigned_to;
            if ($assigneeChanged && $updatedTicket->assignee !== null) {
                event(new TicketAssigned($updatedTicket, $actor, $updatedTicket->assignee));
            }

            return $updatedTicket;
        });
    }

    /**
     * @param array<string, mixed> $data
     */
    public function addComment(Ticket $ticket, User $actor, array $data): TicketComment
    {
        $comment = $this->tickets->addComment($ticket, [
            'body' => $data['body'],
            'user_id' => $actor->getKey(),
        ]);

        $comment->load('user');

        event(new TicketCommented(
            $ticket->load(['reporter', 'assignee', 'apartment', 'building']),
            $comment,
            $actor,
        ));

        return $comment;
    }

    private function resolveApartment(Building $building, mixed $apartmentId): ?Apartment
    {
        if ($apartmentId === null) {
            return null;
        }

        return Apartment::query()
            ->where('building_id', $building->getKey())
            ->findOrFail((int) $apartmentId);
    }

    private function resolveAssignee(Building $building, mixed $assigneeId): ?int
    {
        if ($assigneeId === null || $assigneeId === '') {
            return null;
        }

        $isManager = $building->users()
            ->whereKey((int) $assigneeId)
            ->wherePivot('role', BuildingRole::PropertyManager->value)
            ->exists();

        if (! $isManager) {
            throw ValidationException::withMessages([
                'assigned_to' => ['The selected assignee is not a property manager in this building.'],
            ]);
        }

        return (int) $assigneeId;
    }

    private function normalizeStatus(mixed $status): TicketStatus
    {
        if ($status instanceof TicketStatus) {
            return $status;
        }

        return TicketStatus::from((string) $status);
    }

    private function normalizeVisibility(mixed $visibility): TicketVisibility
    {
        if ($visibility instanceof TicketVisibility) {
            return $visibility;
        }

        if ($visibility === null || $visibility === '') {
            return TicketVisibility::Private;
        }

        return TicketVisibility::from((string) $visibility);
    }

    /**
     * Toggle a tenant's "I have this issue too" affected flag on a public ticket.
     * Recomputes affected_count atomically.
     */
    public function toggleAffected(Ticket $ticket, User $user): bool
    {
        return DB::transaction(function () use ($ticket, $user): bool {
            $exists = $ticket->affectedUsers()->whereKey($user->getKey())->exists();

            if ($exists) {
                $ticket->affectedUsers()->detach($user->getKey());
            } else {
                $ticket->affectedUsers()->attach($user->getKey());
            }

            $ticket->forceFill([
                'affected_count' => $ticket->affectedUsers()->count(),
            ])->save();

            return ! $exists;
        });
    }

    /**
     * @param array<int, UploadedFile> $attachments
     */
    private function storeAttachments(Ticket $ticket, array $attachments): void
    {
        if ($attachments === []) {
            return;
        }

        $existingChecksums = $ticket->attachments()
            ->whereNotNull('checksum')
            ->pluck('checksum')
            ->all();
        $seen = array_fill_keys($existingChecksums, true);
        $duplicates = [];
        $prepared = [];

        foreach ($attachments as $index => $attachment) {
            $processed = $this->imageResizer->resize($attachment);

            $realPath = $processed->getRealPath();
            $checksum = $realPath !== false ? hash_file('sha256', $realPath) : null;

            if (is_string($checksum) && isset($seen[$checksum])) {
                $duplicates["attachments.{$index}"] = [
                    __('The file ":name" is already attached to this ticket.', [
                        'name' => $attachment->getClientOriginalName(),
                    ]),
                ];
                continue;
            }

            $prepared[] = [$attachment, $processed, $checksum];

            if (is_string($checksum)) {
                $seen[$checksum] = true;
            }
        }

        if ($duplicates !== []) {
            throw ValidationException::withMessages($duplicates);
        }

        foreach ($prepared as [$original, $processed, $checksum]) {
            $path = $processed->store('tickets/'.$ticket->getKey(), 'public');

            $ticket->attachments()->create([
                'disk' => 'public',
                'mime_type' => $processed->getMimeType(),
                'original_name' => $original->getClientOriginalName(),
                'path' => $path,
                'size' => $processed->getSize(),
                'checksum' => $checksum,
            ]);
        }
    }
}