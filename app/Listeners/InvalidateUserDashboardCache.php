<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\TicketCreated;
use App\Events\TicketUpdated;
use App\Models\Ticket;
use App\Support\Cache\CacheKey;
use Illuminate\Support\Facades\Cache;

final class InvalidateUserDashboardCache
{
    public function handle(TicketCreated|TicketUpdated $event): void
    {
        $ticket = $event->ticket->loadMissing([
            'apartment.tenants:id',
            'building.managers:id',
        ]);

        foreach ($this->affectedUserIds($ticket) as $userId) {
            Cache::forget(CacheKey::userDashboard($userId));
        }
    }

    /**
     * @return list<int>
     */
    private function affectedUserIds(Ticket $ticket): array
    {
        return collect([
            (int) $ticket->reported_by,
            $ticket->assigned_to !== null ? (int) $ticket->assigned_to : null,
            ...$ticket->building->managers->pluck('id')->map(fn (mixed $id): int => (int) $id)->all(),
            ...$ticket->apartment?->tenants?->pluck('id')->map(fn (mixed $id): int => (int) $id)->all() ?? [],
        ])
            ->filter(fn (mixed $id): bool => $id !== null && $id > 0)
            ->unique()
            ->values()
            ->all();
    }
}