<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use App\Repositories\Contracts\TicketRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class TicketRepository implements TicketRepositoryInterface
{
    public function paginateForBuilding(int $buildingId, array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return Ticket::query()
            ->where('building_id', $buildingId)
            ->with(['apartment', 'reporter', 'assignee', 'attachments'])
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($filters['priority'] ?? null, fn ($query, string $priority) => $query->where('priority', $priority))
            ->when($filters['assigned_to'] ?? null, fn ($query, int $assigneeId) => $query->where('assigned_to', $assigneeId))
            ->latest()
            ->paginate($perPage);
    }

    public function paginateForBuildingAndUser(int $buildingId, User $user, array $filters, int $perPage = 10): LengthAwarePaginator
    {
        return Ticket::query()
            ->where('building_id', $buildingId)
            ->with(['apartment', 'reporter', 'assignee'])
            ->when($filters['status'] ?? null, fn (Builder $query, string $status): Builder => $query->where('status', $status))
            ->when($filters['priority'] ?? null, fn (Builder $query, string $priority): Builder => $query->where('priority', $priority))
            ->when($filters['assigned_to'] ?? null, fn (Builder $query, int $assigneeId): Builder => $query->where('assigned_to', $assigneeId))
            ->when(
                ! $user->isBuildingAdmin($buildingId),
                fn (Builder $query): Builder => $query->where(
                    fn (Builder $scopedQuery): Builder => $scopedQuery
                        ->where('reported_by', $user->getKey())
                        ->orWhereHas('apartment.tenants', fn (Builder $q): Builder => $q->whereKey($user->getKey())),
                ),
            )
            ->latest()
            ->paginate($perPage);
    }

    public function create(array $data): Ticket
    {
        return Ticket::query()->create($data);
    }

    public function update(Ticket $ticket, array $data): Ticket
    {
        $ticket->fill($data)->save();

        return $ticket->refresh();
    }

    public function addComment(Ticket $ticket, array $data): TicketComment
    {
        return $ticket->comments()->create($data);
    }
}