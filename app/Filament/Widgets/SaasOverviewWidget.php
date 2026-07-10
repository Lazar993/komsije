<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\BuildingStatus;
use App\Filament\Resources\Buildings\BuildingResource;
use App\Models\Building;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

/**
 * SaaS portfolio overview for super admins: building counts by lifecycle
 * status, new sign-ups this month, and trials expiring soon. Each card links
 * to the corresponding filtered building list.
 */
final class SaasOverviewWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 0;

    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof User && $user->isSuperAdmin();
    }

    /**
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        $counts = Building::query()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $countFor = static fn (BuildingStatus $status): int => (int) ($counts[$status->value] ?? 0);

        $total = (int) $counts->sum();

        $newThisMonth = Building::query()
            ->where('created_at', '>=', Carbon::now()->startOfMonth())
            ->count();

        $expiringSoon = Building::query()->trialExpiringWithin(7)->count();

        return [
            Stat::make(__('Buildings'), $total)
                ->description(__(':count new this month', ['count' => $newThisMonth]))
                ->descriptionIcon(Heroicon::OutlinedSparkles)
                ->color('primary')
                ->icon(Heroicon::OutlinedBuildingOffice2)
                ->url(BuildingResource::getUrl('index')),

            Stat::make(__('Trial'), $countFor(BuildingStatus::Trial))
                ->color('info')
                ->icon(Heroicon::OutlinedClock)
                ->url($this->statusUrl(BuildingStatus::Trial)),

            Stat::make(__('Active'), $countFor(BuildingStatus::Active))
                ->color('success')
                ->icon(Heroicon::OutlinedCheckBadge)
                ->url($this->statusUrl(BuildingStatus::Active)),

            Stat::make(__('Suspended'), $countFor(BuildingStatus::Suspended))
                ->color('danger')
                ->icon(Heroicon::OutlinedLockClosed)
                ->url($this->statusUrl(BuildingStatus::Suspended)),

            Stat::make(__('Archived'), $countFor(BuildingStatus::Archived))
                ->color('gray')
                ->icon(Heroicon::OutlinedArchiveBox)
                ->url($this->statusUrl(BuildingStatus::Archived)),

            Stat::make(__('Trial expiring soon'), $expiringSoon)
                ->description(__('Within the next 7 days'))
                ->color($expiringSoon > 0 ? 'warning' : 'gray')
                ->icon(Heroicon::OutlinedExclamationTriangle)
                ->url($this->statusUrl(BuildingStatus::Trial)),
        ];
    }

    private function statusUrl(BuildingStatus $status): string
    {
        return BuildingResource::getUrl('index', [
            'tableFilters' => ['status' => ['value' => $status->value]],
        ]);
    }
}
