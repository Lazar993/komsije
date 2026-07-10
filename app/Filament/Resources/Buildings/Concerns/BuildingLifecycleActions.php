<?php

declare(strict_types=1);

namespace App\Filament\Resources\Buildings\Concerns;

use App\Models\Building;
use App\Services\BuildingLifecycleService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * Super-admin building lifecycle actions, reusable across the resource table
 * (record actions) and the ViewBuilding header. Visibility is authorised
 * through the BuildingPolicy abilities (super admin only).
 */
trait BuildingLifecycleActions
{
    /**
     * @return array<int, Action|ActionGroup>
     */
    public static function lifecycleActions(): array
    {
        return [
            ActionGroup::make([
                self::activateAction(),
                self::suspendAction(),
                self::extendTrialAction(),
                self::restartTrialAction(),
                self::changeExpirationAction(),
                self::archiveAction(),
            ])
                ->label(__('Lifecycle'))
                ->icon('heroicon-o-adjustments-horizontal')
                ->button()
                ->color('gray')
                ->visible(fn (): bool => Auth::user()?->isSuperAdmin() ?? false),
        ];
    }

    protected static function activateAction(): Action
    {
        return Action::make('activate')
            ->label(__('Activate subscription'))
            ->icon('heroicon-o-check-badge')
            ->color('success')
            ->visible(fn (Building $record): bool => ! $record->isActive() && Auth::user()?->can('activate', $record))
            ->schema([
                DatePicker::make('subscription_ends_at')
                    ->label(__('Subscription ends at (optional)'))
                    ->native(false)
                    ->minDate(now())
                    ->helperText(__('Leave empty for an open-ended subscription.')),
            ])
            ->action(function (Building $record, array $data): void {
                app(BuildingLifecycleService::class)->activate(
                    $record,
                    Auth::user(),
                    filled($data['subscription_ends_at'] ?? null) ? Carbon::parse($data['subscription_ends_at']) : null,
                );

                self::notify(__('Building activated.'));
            });
    }

    protected static function suspendAction(): Action
    {
        return Action::make('suspend')
            ->label(__('Suspend'))
            ->icon('heroicon-o-lock-closed')
            ->color('danger')
            ->requiresConfirmation()
            ->modalDescription(__('Residents and managers will keep read-only access but cannot create new activity.'))
            ->visible(fn (Building $record): bool => ! $record->isSuspended() && ! $record->isArchived() && Auth::user()?->can('suspend', $record))
            ->action(function (Building $record): void {
                app(BuildingLifecycleService::class)->suspend($record, Auth::user());

                self::notify(__('Building suspended.'));
            });
    }

    protected static function extendTrialAction(): Action
    {
        return Action::make('extendTrial')
            ->label(__('Extend trial'))
            ->icon('heroicon-o-clock')
            ->color('info')
            ->visible(fn (Building $record): bool => Auth::user()?->can('manageTrial', $record) ?? false)
            ->schema([
                TextInput::make('days')
                    ->label(__('Extend by (days)'))
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(365)
                    ->default(14)
                    ->required(),
            ])
            ->action(function (Building $record, array $data): void {
                app(BuildingLifecycleService::class)->extendTrial($record, (int) $data['days'], Auth::user());

                self::notify(__('Trial extended.'));
            });
    }

    protected static function restartTrialAction(): Action
    {
        return Action::make('restartTrial')
            ->label(__('Restart trial'))
            ->icon('heroicon-o-arrow-path')
            ->color('warning')
            ->requiresConfirmation()
            ->visible(fn (Building $record): bool => Auth::user()?->can('manageTrial', $record) ?? false)
            ->schema([
                TextInput::make('days')
                    ->label(__('Trial length (days)'))
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(365)
                    ->default(Building::TRIAL_DAYS)
                    ->required(),
            ])
            ->action(function (Building $record, array $data): void {
                app(BuildingLifecycleService::class)->restartTrial($record, Auth::user(), (int) $data['days']);

                self::notify(__('Trial restarted.'));
            });
    }

    protected static function changeExpirationAction(): Action
    {
        return Action::make('changeExpiration')
            ->label(__('Change expiration date'))
            ->icon('heroicon-o-calendar')
            ->color('info')
            ->visible(fn (Building $record): bool => Auth::user()?->can('manageTrial', $record) ?? false)
            ->schema([
                DatePicker::make('trial_ends_at')
                    ->label(__('New expiration date'))
                    ->native(false)
                    ->required()
                    ->default(fn (Building $record): ?Carbon => $record->trial_ends_at),
            ])
            ->action(function (Building $record, array $data): void {
                app(BuildingLifecycleService::class)->changeExpiration($record, Carbon::parse($data['trial_ends_at']), Auth::user());

                self::notify(__('Expiration date updated.'));
            });
    }

    protected static function archiveAction(): Action
    {
        return Action::make('archive')
            ->label(__('Archive'))
            ->icon('heroicon-o-archive-box')
            ->color('gray')
            ->requiresConfirmation()
            ->modalDescription(__('Archiving preserves all history but makes the building read-only.'))
            ->visible(fn (Building $record): bool => ! $record->isArchived() && Auth::user()?->can('archive', $record))
            ->action(function (Building $record): void {
                app(BuildingLifecycleService::class)->archive($record, Auth::user());

                self::notify(__('Building archived.'));
            });
    }

    private static function notify(string $message): void
    {
        Notification::make()->success()->title($message)->send();
    }
}
