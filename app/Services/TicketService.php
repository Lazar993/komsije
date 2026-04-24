<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\BuildingRole;
use App\Enums\TicketStatus;
use App\Events\TicketCreated;
use App\Events\TicketUpdated;
use App\Models\Apartment;
use App\Models\Building;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use App\Repositories\Contracts\TicketRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class TicketService
{
    public function __construct(private readonly TicketRepositoryInterface $tickets)
    {
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
            ]);

            $this->storeAttachments($updatedTicket, $data['attachments'] ?? []);

            if ($fromStatus !== $updatedTicket->status) {
                $updatedTicket->statusHistory()->create([
                    'changed_by' => $actor->getKey(),
                    'from_status' => $fromStatus,
                    'note' => $data['status_note'] ?? 'Ticket status updated.',
                    'to_status' => $updatedTicket->status,
                ]);
            }

            $updatedTicket->load(['apartment', 'reporter', 'assignee', 'attachments', 'comments.user', 'statusHistory.actor']);

            event(new TicketUpdated($updatedTicket, $actor, $fromStatus, $updatedTicket->status, $data['status_note'] ?? null));

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

        event(new TicketUpdated($ticket->load(['reporter', 'assignee', 'apartment']), $actor, $ticket->status, $ticket->status, 'New ticket comment added.'));

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

    /**
     * @param array<int, UploadedFile> $attachments
     */
    private function storeAttachments(Ticket $ticket, array $attachments): void
    {
        foreach ($attachments as $attachment) {
            $path = $attachment->store('tickets/'.$ticket->getKey(), 'public');

            $ticket->attachments()->create([
                'disk' => 'public',
                'mime_type' => $attachment->getMimeType(),
                'original_name' => $attachment->getClientOriginalName(),
                'path' => $path,
                'size' => $attachment->getSize(),
            ]);
        }
    }
}