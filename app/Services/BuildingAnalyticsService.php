<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\BuildingRole;
use App\Enums\TicketStatus;
use App\Models\Announcement;
use App\Models\Building;
use App\Models\DeviceToken;
use App\Models\Invite;
use App\Models\Poll;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\Vote;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Read-only aggregation of a building's engagement metrics. Every figure is
 * derived from existing domain tables; results are cached briefly to keep the
 * super-admin dashboards cheap.
 */
final class BuildingAnalyticsService
{
    private const CACHE_TTL_MINUTES = 15;

    /**
     * @return array{
     *     tenant_count: int,
     *     registered_users: int,
     *     invited_tenants: int,
     *     accepted_invites: int,
     *     pending_invites: int,
     *     acceptance_rate: int,
     *     active_users: int,
     *     open_tickets: int,
     *     closed_tickets: int,
     *     total_tickets: int,
     *     announcements: int,
     *     polls: int,
     *     comments: int,
     *     avg_resolution_hours: float|null,
     *     push_ready_users: int,
     *     push_delivery_rate: int,
     *     last_activity_at: string|null,
     *     registration_rate: int
     * }
     */
    public function metrics(Building $building): array
    {
        $buildingId = (int) $building->getKey();

        return Cache::remember(
            "building:{$buildingId}:analytics",
            now()->addMinutes(self::CACHE_TTL_MINUTES),
            fn (): array => $this->compute($buildingId),
        );
    }

    public function forget(int $buildingId): void
    {
        Cache::forget("building:{$buildingId}:analytics");
    }

    /**
     * @return array<string, mixed>
     */
    private function compute(int $buildingId): array
    {
        $thirtyDaysAgo = Carbon::now()->subDays(30);

        $registeredUsers = DB::table('building_user')
            ->where('building_id', $buildingId)
            ->distinct('user_id')
            ->count('user_id');

        $tenantCount = DB::table('building_user')
            ->where('building_id', $buildingId)
            ->where('role', BuildingRole::Tenant->value)
            ->distinct('user_id')
            ->count('user_id');

        $invitedTenants = Invite::query()
            ->where('building_id', $buildingId)
            ->where('role', BuildingRole::Tenant->value)
            ->count();

        $acceptedInvites = Invite::query()
            ->where('building_id', $buildingId)
            ->where('role', BuildingRole::Tenant->value)
            ->whereNotNull('used_at')
            ->count();

        $pendingInvites = max(0, $invitedTenants - $acceptedInvites);
        $acceptanceRate = $invitedTenants > 0
            ? (int) round($acceptedInvites / $invitedTenants * 100)
            : 0;

        $ticketStatusCounts = Ticket::query()
            ->where('building_id', $buildingId)
            ->select('status', DB::raw('count(*) as aggregate'))
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $countFor = static function (TicketStatus $status) use ($ticketStatusCounts): int {
            return (int) ($ticketStatusCounts[$status->value] ?? 0);
        };

        $openTickets = $countFor(TicketStatus::New) + $countFor(TicketStatus::InProgress);
        $closedTickets = $countFor(TicketStatus::Resolved) + $countFor(TicketStatus::Cancelled);
        $totalTickets = (int) $ticketStatusCounts->sum();

        $announcements = Announcement::query()
            ->where('building_id', $buildingId)
            ->whereNotNull('published_at')
            ->count();

        $polls = Poll::query()->where('building_id', $buildingId)->count();

        $comments = TicketComment::query()
            ->whereIn('ticket_id', Ticket::query()->where('building_id', $buildingId)->select('id'))
            ->count();

        $avgResolutionHours = $this->averageResolutionHours($buildingId);

        $activeUsers = $this->monthlyActiveUsers($buildingId, $thirtyDaysAgo);

        $pushReadyUsers = DeviceToken::query()
            ->whereIn('user_id', DB::table('building_user')->where('building_id', $buildingId)->select('user_id'))
            ->distinct('user_id')
            ->count('user_id');

        $pushDeliveryRate = $registeredUsers > 0
            ? (int) round($pushReadyUsers / $registeredUsers * 100)
            : 0;

        $registrationRate = $invitedTenants > 0
            ? (int) round(min($tenantCount, $invitedTenants) / $invitedTenants * 100)
            : ($tenantCount > 0 ? 100 : 0);

        return [
            'tenant_count' => $tenantCount,
            'registered_users' => $registeredUsers,
            'invited_tenants' => $invitedTenants,
            'accepted_invites' => $acceptedInvites,
            'pending_invites' => $pendingInvites,
            'acceptance_rate' => $acceptanceRate,
            'active_users' => $activeUsers,
            'open_tickets' => $openTickets,
            'closed_tickets' => $closedTickets,
            'total_tickets' => $totalTickets,
            'announcements' => $announcements,
            'polls' => $polls,
            'comments' => $comments,
            'avg_resolution_hours' => $avgResolutionHours,
            'push_ready_users' => $pushReadyUsers,
            'push_delivery_rate' => $pushDeliveryRate,
            'last_activity_at' => $this->lastActivityAt($buildingId),
            'registration_rate' => $registrationRate,
        ];
    }

    private function averageResolutionHours(int $buildingId): ?float
    {
        $rows = Ticket::query()
            ->where('building_id', $buildingId)
            ->whereNotNull('resolved_at')
            ->get(['created_at', 'resolved_at']);

        if ($rows->isEmpty()) {
            return null;
        }

        $totalHours = $rows->sum(
            static fn (Ticket $ticket): float => $ticket->created_at->floatDiffInHours($ticket->resolved_at),
        );

        return round($totalHours / $rows->count(), 1);
    }

    private function monthlyActiveUsers(int $buildingId, Carbon $since): int
    {
        $ticketUsers = Ticket::query()
            ->where('building_id', $buildingId)
            ->where('created_at', '>=', $since)
            ->whereNotNull('reported_by')
            ->distinct()
            ->pluck('reported_by');

        $commentUsers = TicketComment::query()
            ->whereIn('ticket_id', Ticket::query()->where('building_id', $buildingId)->select('id'))
            ->where('created_at', '>=', $since)
            ->whereNotNull('user_id')
            ->distinct()
            ->pluck('user_id');

        $voteUsers = Vote::query()
            ->whereIn('poll_id', Poll::query()->where('building_id', $buildingId)->select('id'))
            ->where('created_at', '>=', $since)
            ->whereNotNull('user_id')
            ->distinct()
            ->pluck('user_id');

        $tokenUsers = DeviceToken::query()
            ->whereIn('user_id', DB::table('building_user')->where('building_id', $buildingId)->select('user_id'))
            ->where('last_used_at', '>=', $since)
            ->distinct()
            ->pluck('user_id');

        return $ticketUsers
            ->merge($commentUsers)
            ->merge($voteUsers)
            ->merge($tokenUsers)
            ->filter()
            ->unique()
            ->count();
    }

    private function lastActivityAt(int $buildingId): ?string
    {
        $timestamps = [
            Ticket::query()->where('building_id', $buildingId)->max('created_at'),
            Announcement::query()->where('building_id', $buildingId)->max('published_at'),
            Poll::query()->where('building_id', $buildingId)->max('created_at'),
            DeviceToken::query()
                ->whereIn('user_id', DB::table('building_user')->where('building_id', $buildingId)->select('user_id'))
                ->max('last_used_at'),
        ];

        $latest = collect($timestamps)
            ->filter()
            ->map(static fn ($value): Carbon => Carbon::parse($value))
            ->sortDesc()
            ->first();

        return $latest?->toIso8601String();
    }
}
