<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\TicketStatus;
use App\Models\Announcement;
use App\Models\Building;
use App\Models\Poll;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Carbon;

final class ManagementStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    protected string $view = 'filament.widgets.management-stats-overview';

    /**
     * Selected look-back window in days, or "all" for no time limit.
     */
    public string $range = '7';

    /**
     * Selected building id to scope by, or "all" for every managed building.
     */
    public string $building = 'all';

    public static function canView(): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof User && $user->isBuildingAdmin();
    }

    /**
     * @return array<string, string>
     */
    public function getRangeOptions(): array
    {
        return [
            '3' => __('Last 3 days'),
            '7' => __('Last 7 days'),
            '30' => __('Last 30 days'),
            'all' => __('All time'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function getBuildingOptions(): array
    {
        $buildings = Building::query()
            ->whereIn('id', $this->managedBuildingIds())
            ->orderBy('name')
            ->pluck('name', 'id')
            ->map(fn ($name): string => (string) $name)
            ->toArray();

        return ['all' => __('All buildings')] + $buildings;
    }

    public function hasMultipleBuildings(): bool
    {
        return count($this->managedBuildingIds()) > 1;
    }

    public function getRangeDescription(): string
    {
        return match ($this->range) {
            '3' => __('Activity from the last 3 days'),
            '30' => __('Activity from the last 30 days'),
            'all' => __('Activity across all time'),
            default => __('Activity from the last 7 days'),
        };
    }

    /**
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        $buildingIds = $this->scopedBuildingIds();
        $since = $this->rangeStart();

        if ($buildingIds === []) {
            return [
                Stat::make(__('Managed buildings'), 0)
                    ->description(__('You do not manage any buildings yet.'))
                    ->color('gray')
                    ->icon(Heroicon::OutlinedBuildingOffice2),
            ];
        }

        $newTickets = Ticket::query()
            ->whereIn('building_id', $buildingIds)
            ->when($since, fn ($query) => $query->where('created_at', '>=', $since))
            ->count();

        $resolvedTickets = Ticket::query()
            ->whereIn('building_id', $buildingIds)
            ->where('status', TicketStatus::Resolved->value)
            ->when($since, fn ($query) => $query->where('resolved_at', '>=', $since))
            ->count();

        $openTickets = Ticket::query()
            ->whereIn('building_id', $buildingIds)
            ->whereIn('status', [TicketStatus::New->value, TicketStatus::InProgress->value])
            ->count();

        $announcements = Announcement::query()
            ->whereIn('building_id', $buildingIds)
            ->when($since, fn ($query) => $query->where('created_at', '>=', $since))
            ->count();

        $polls = Poll::query()
            ->whereIn('building_id', $buildingIds)
            ->when($since, fn ($query) => $query->where('created_at', '>=', $since))
            ->count();

        $comments = TicketComment::query()
            ->whereHas('ticket', fn ($query) => $query->whereIn('building_id', $buildingIds))
            ->when($since, fn ($query) => $query->where('created_at', '>=', $since))
            ->count();

        $notifications = $this->notificationsCount($buildingIds, $since);

        return [
            Stat::make(__('New tickets'), $newTickets)
                ->description(__('Reported in this period'))
                ->descriptionIcon(Heroicon::OutlinedExclamationTriangle)
                ->color($newTickets > 0 ? 'warning' : 'success')
                ->icon(Heroicon::OutlinedTicket),

            Stat::make(__('Resolved tickets'), $resolvedTickets)
                ->description(__('Marked as resolved in this period'))
                ->descriptionIcon(Heroicon::OutlinedCheckCircle)
                ->color('success')
                ->icon(Heroicon::OutlinedCheckBadge),

            Stat::make(__('Open tickets'), $openTickets)
                ->description(__('Currently awaiting resolution'))
                ->color($openTickets > 0 ? 'danger' : 'success')
                ->icon(Heroicon::OutlinedWrenchScrewdriver),

            Stat::make(__('Announcements'), $announcements)
                ->description(__('Published in this period'))
                ->color('info')
                ->icon(Heroicon::OutlinedMegaphone),

            Stat::make(__('Polls'), $polls)
                ->description(__('Created in this period'))
                ->color('info')
                ->icon(Heroicon::OutlinedChartBar),

            Stat::make(__('Comments'), $comments)
                ->description(__('Ticket replies in this period'))
                ->color('gray')
                ->icon(Heroicon::OutlinedChatBubbleLeftRight),

            Stat::make(__('Notifications'), $notifications)
                ->description(__('Sent to residents in this period'))
                ->color('primary')
                ->icon(Heroicon::OutlinedBell),
        ];
    }

    /**
     * @return list<int>
     */
    private function managedBuildingIds(): array
    {
        $user = Filament::auth()->user();

        if (! $user instanceof User) {
            return [];
        }

        return $user->managedBuildingIds();
    }

    /**
     * The managed building ids narrowed down by the selected building filter.
     *
     * @return list<int>
     */
    private function scopedBuildingIds(): array
    {
        $managed = $this->managedBuildingIds();

        if ($this->building === 'all') {
            return $managed;
        }

        $selected = (int) $this->building;

        return in_array($selected, $managed, true) ? [$selected] : $managed;
    }

    private function rangeStart(): ?Carbon
    {
        return match ($this->range) {
            '3' => now()->subDays(3),
            '30' => now()->subDays(30),
            'all' => null,
            default => now()->subDays(7),
        };
    }

    /**
     * @param  list<int>  $buildingIds
     */
    private function notificationsCount(array $buildingIds, ?Carbon $since): int
    {
        $userIds = User::query()
            ->whereHas('buildings', fn ($query) => $query->whereIn('buildings.id', $buildingIds))
            ->pluck('id');

        if ($userIds->isEmpty()) {
            return 0;
        }

        return DatabaseNotification::query()
            ->where('notifiable_type', (new User)->getMorphClass())
            ->whereIn('notifiable_id', $userIds)
            ->when($since, fn ($query) => $query->where('created_at', '>=', $since))
            ->count();
    }
}
