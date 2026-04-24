<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\TicketStatus;
use App\Models\Building;
use App\Models\Ticket;
use App\Models\User;
use App\Support\Cache\CacheKey;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use stdClass;

final class DashboardService
{
    /**
     * @return array{total_tickets: int, active_tickets: int, resolved_tickets: int, recent_tickets: Collection<int, stdClass>}
     */
    public function getForUser(User $user, Building $building): array
    {
        $payload = Cache::remember(CacheKey::userDashboard((int) $user->getKey()), now()->addMinutes(5), fn (): array => $this->buildPayload($user));

        $buildingPayload = $payload['buildings'][(int) $building->getKey()] ?? $this->emptyBuildingPayload();

        return [
            'total_tickets' => (int) $buildingPayload['total_tickets'],
            'active_tickets' => (int) $buildingPayload['active_tickets'],
            'resolved_tickets' => (int) $buildingPayload['resolved_tickets'],
            'recent_tickets' => $this->hydrateRecentTickets($buildingPayload['recent_tickets']),
        ];
    }

    /**
     * @return array{buildings: array<int, array{total_tickets: int, active_tickets: int, resolved_tickets: int, recent_tickets: list<array<string, mixed>>}>}
     */
    private function buildPayload(User $user): array
    {
        $accessibleBuildingIds = $this->accessibleBuildingIds($user);

        if ($accessibleBuildingIds === []) {
            return ['buildings' => []];
        }

        $adminBuildingIds = $user->isSuperAdmin()
            ? $accessibleBuildingIds
            : array_values(array_intersect($accessibleBuildingIds, $user->managedBuildingIds()));
        $tenantBuildingIds = array_values(array_diff($accessibleBuildingIds, $adminBuildingIds));

        $buildings = [];

        foreach ($accessibleBuildingIds as $buildingId) {
            $buildings[$buildingId] = $this->emptyBuildingPayload();
        }

        $this->mergeCounts($buildings, $this->statusCounts($adminBuildingIds, true, $user));
        $this->mergeCounts($buildings, $this->statusCounts($tenantBuildingIds, false, $user));

        $this->mergeRecentTickets($buildings, $adminBuildingIds, true, $user);
        $this->mergeRecentTickets($buildings, $tenantBuildingIds, false, $user);

        return ['buildings' => $buildings];
    }

    /**
     * @param array<int, array{total_tickets: int, active_tickets: int, resolved_tickets: int, recent_tickets: list<array<string, mixed>>}> $buildings
     * @param Collection<int, object> $counts
     */
    private function mergeCounts(array &$buildings, Collection $counts): void
    {
        foreach ($counts as $row) {
            $buildingId = (int) $row->building_id;
            $count = (int) $row->aggregate;
            $status = $row->status instanceof TicketStatus
                ? $row->status
                : TicketStatus::from((string) $row->status);

            $buildings[$buildingId]['total_tickets'] += $count;

            if ($status === TicketStatus::Resolved) {
                $buildings[$buildingId]['resolved_tickets'] += $count;

                continue;
            }

            $buildings[$buildingId]['active_tickets'] += $count;
        }
    }

    /**
     * @param array<int, array{total_tickets: int, active_tickets: int, resolved_tickets: int, recent_tickets: list<array<string, mixed>>}> $buildings
     * @param list<int> $buildingIds
     */
    private function mergeRecentTickets(array &$buildings, array $buildingIds, bool $includeAllTickets, User $user): void
    {
        if ($buildingIds === []) {
            return;
        }

        // Fetch all relevant tickets in a single query, then group in PHP.
        // The result is cached at the caller level, so this runs at most once per cache miss.
        $allTickets = $this->ticketQuery($buildingIds, $includeAllTickets, $user)
            ->with(['reporter:id,name', 'assignee:id,name', 'apartment:id,building_id,number'])
            ->latest()
            ->get();

        $grouped = $allTickets->groupBy('building_id');

        foreach ($buildingIds as $buildingId) {
            $buildings[$buildingId]['recent_tickets'] = ($grouped->get($buildingId) ?? collect())
                ->take(5)
                ->map(fn (Ticket $ticket): array => $this->serializeTicket($ticket))
                ->values()
                ->all();
        }
    }

    /**
     * @param list<int> $buildingIds
     * @return Collection<int, object>
     */
    private function statusCounts(array $buildingIds, bool $includeAllTickets, User $user): Collection
    {
        if ($buildingIds === []) {
            return collect();
        }

        return $this->ticketQuery($buildingIds, $includeAllTickets, $user)
            ->selectRaw('building_id, status, COUNT(*) as aggregate')
            ->groupBy('building_id', 'status')
            ->get();
    }

    /**
     * @param list<int> $buildingIds
     */
    private function ticketQuery(array $buildingIds, bool $includeAllTickets, User $user): Builder
    {
        return Ticket::query()
            ->whereIn('building_id', $buildingIds)
            ->when(! $includeAllTickets, function (Builder $query) use ($user): Builder {
                return $query->where(function (Builder $scopedQuery) use ($user): void {
                    $scopedQuery
                        ->where('reported_by', $user->getKey())
                        ->orWhereHas('apartment.tenants', fn (Builder $tenantQuery): Builder => $tenantQuery->whereKey($user->getKey()));
                });
            });
    }

    /**
     * @return list<int>
     */
    private function accessibleBuildingIds(User $user): array
    {
        if ($user->isSuperAdmin()) {
            return Building::query()->pluck('id')->map(fn (mixed $id): int => (int) $id)->all();
        }

        return $user->buildings()->pluck('buildings.id')->map(fn (mixed $id): int => (int) $id)->all();
    }

    /**
     * @return array{total_tickets: int, active_tickets: int, resolved_tickets: int, recent_tickets: list<array<string, mixed>>}
     */
    private function emptyBuildingPayload(): array
    {
        return [
            'total_tickets' => 0,
            'active_tickets' => 0,
            'resolved_tickets' => 0,
            'recent_tickets' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeTicket(Ticket $ticket): array
    {
        return [
            'id' => (int) $ticket->getKey(),
            'title' => $ticket->title,
            'description' => $ticket->description,
            'status' => $ticket->status->value,
            'created_at' => $ticket->created_at?->toIso8601String(),
            'reporter' => $ticket->reporter !== null
                ? ['id' => (int) $ticket->reporter->getKey(), 'name' => $ticket->reporter->name]
                : null,
            'assignee' => $ticket->assignee !== null
                ? ['id' => (int) $ticket->assignee->getKey(), 'name' => $ticket->assignee->name]
                : null,
            'apartment' => $ticket->apartment !== null
                ? ['id' => (int) $ticket->apartment->getKey(), 'number' => $ticket->apartment->number]
                : null,
        ];
    }

    /**
     * @param list<array<string, mixed>> $tickets
     * @return Collection<int, stdClass>
     */
    private function hydrateRecentTickets(array $tickets): Collection
    {
        return collect($tickets)->map(function (array $ticket): stdClass {
            return (object) [
                'id' => (int) $ticket['id'],
                'title' => (string) $ticket['title'],
                'description' => (string) $ticket['description'],
                'status' => TicketStatus::from((string) $ticket['status']),
                'created_at' => isset($ticket['created_at']) ? CarbonImmutable::parse((string) $ticket['created_at']) : null,
                'reporter' => isset($ticket['reporter']) && is_array($ticket['reporter'])
                    ? (object) $ticket['reporter']
                    : null,
                'assignee' => isset($ticket['assignee']) && is_array($ticket['assignee'])
                    ? (object) $ticket['assignee']
                    : null,
                'apartment' => isset($ticket['apartment']) && is_array($ticket['apartment'])
                    ? (object) $ticket['apartment']
                    : null,
            ];
        })->values();
    }
}