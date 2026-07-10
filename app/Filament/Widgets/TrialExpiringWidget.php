<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Filament\Resources\Buildings\BuildingResource;
use App\Models\Building;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Clickable list of trials expiring soon, narrowable by 7 / 5 / 3 / 1 day
 * windows. Super admin only. Selecting a row opens the building.
 */
final class TrialExpiringWidget extends TableWidget
{
    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof User && $user->isSuperAdmin();
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('Trials expiring soon'))
            ->description(__('Buildings whose free trial is about to end.'))
            ->query(fn (): Builder => Building::query()
                ->trialExpiringWithin(7)
                ->with('managers'))
            ->defaultSort('trial_ends_at', 'asc')
            ->emptyStateHeading(__('No trials expiring soon'))
            ->emptyStateDescription(__('You are all caught up. Nothing needs attention right now.'))
            ->emptyStateIcon('heroicon-o-check-circle')
            ->filters([
                SelectFilter::make('window')
                    ->label(__('Expiring within'))
                    ->options([
                        '7' => __('7 days'),
                        '5' => __('5 days'),
                        '3' => __('3 days'),
                        '1' => __('1 day'),
                    ])
                    ->default('7')
                    ->selectablePlaceholder(false)
                    ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                        ? $query->where('trial_ends_at', '<=', Carbon::now()->addDays((int) $data['value']))
                        : $query),
            ], layout: FiltersLayout::AboveContent)
            ->columns([
                TextColumn::make('name')
                    ->label(__('Building'))
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('managers.name')
                    ->label(__('Managers'))
                    ->badge()
                    ->separator(',')
                    ->limitList(2),
                TextColumn::make('trial_ends_at')
                    ->label(__('Trial ends'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('days_remaining')
                    ->label(__('Days left'))
                    ->badge()
                    ->state(fn (Building $record): int => max(0, (int) $record->daysRemaining()))
                    ->color(fn (Building $record): string => match (true) {
                        (int) $record->daysRemaining() <= 1 => 'danger',
                        (int) $record->daysRemaining() <= 3 => 'warning',
                        default => 'info',
                    }),
            ])
            ->recordUrl(fn (Building $record): string => BuildingResource::getUrl('view', ['record' => $record]))
            ->paginated([5, 10, 25]);
    }
}
