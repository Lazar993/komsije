<?php

declare(strict_types=1);

namespace App\Filament\Resources\Buildings;

use App\Filament\Resources\Buildings\RelationManagers\ApartmentsRelationManager;
use App\Filament\Resources\Buildings\RelationManagers\AuditLogsRelationManager;
use App\Filament\Resources\Buildings\Concerns\BuildingLifecycleActions;
use App\Filament\Resources\Buildings\Pages\CreateBuilding;
use App\Filament\Resources\Buildings\Pages\EditBuilding;
use App\Filament\Resources\Buildings\Pages\ListBuildings;
use App\Filament\Resources\Buildings\Pages\ViewBuilding;
use App\Enums\BuildingStatus;
use App\Models\Building;
use App\Models\User;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class BuildingResource extends Resource
{
    use BuildingLifecycleActions;

    protected static ?string $model = Building::class;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::BuildingOffice2;

    protected static string | UnitEnum | null $navigationGroup = 'Portfolio';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Building details')->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('address')
                    ->required()
                    ->maxLength(255),
                Select::make('manager_ids')
                    ->label('Building admins')
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->visible(fn (): bool => Auth::user()?->isSuperAdmin() ?? false)
                    ->options(fn (): array => User::query()
                        ->where('is_super_admin', false)
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all()),
            ]),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('Building details'))->schema([
                TextEntry::make('name'),
                TextEntry::make('address'),
                TextEntry::make('billing_customer_reference')
                    ->label(__('Billing reference'))
                    ->placeholder('-'),
                TextEntry::make('managers.name')
                    ->label(__('Admins'))
                    ->listWithLineBreaks(),
            ])->columns(2),
            Section::make(__('Subscription lifecycle'))->schema([
                TextEntry::make('status')
                    ->badge()
                    ->formatStateUsing(fn (BuildingStatus $state): string => $state->label())
                    ->color(fn (BuildingStatus $state): string => $state->color())
                    ->icon(fn (BuildingStatus $state): string => $state->icon()),
                TextEntry::make('trial_ends_at')
                    ->label(__('Trial ends'))
                    ->dateTime()
                    ->placeholder('-')
                    ->helperText(fn (Building $record): ?string => $record->isTrial() && $record->daysRemaining() !== null
                        ? __(':days days remaining', ['days' => max(0, (int) $record->daysRemaining())])
                        : null),
                TextEntry::make('subscription_started_at')
                    ->label(__('Subscription started'))
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('subscription_ends_at')
                    ->label(__('Subscription ends'))
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('suspended_at')
                    ->label(__('Suspended at'))
                    ->dateTime()
                    ->placeholder('-')
                    ->visible(fn (Building $record): bool => $record->suspended_at !== null),
                TextEntry::make('archived_at')
                    ->label(__('Archived at'))
                    ->dateTime()
                    ->placeholder('-')
                    ->visible(fn (Building $record): bool => $record->archived_at !== null),
            ])->columns(2),
            Section::make(__('Engagement & health'))->schema([
                TextEntry::make('health_score')
                    ->label(__('Health score'))
                    ->badge()
                    ->state(fn (Building $record): string => app(\App\Services\BuildingHealthScoreService::class)->score($record)['score'] . ' / 100')
                    ->color(fn (Building $record): string => app(\App\Services\BuildingHealthScoreService::class)->score($record)['color']),
                TextEntry::make('health_rating')
                    ->label(__('Rating'))
                    ->state(fn (Building $record): string => app(\App\Services\BuildingHealthScoreService::class)->score($record)['rating']),
                TextEntry::make('tenant_count')
                    ->label(__('Registered tenants'))
                    ->state(fn (Building $record): int => app(\App\Services\BuildingAnalyticsService::class)->metrics($record)['tenant_count']),
                TextEntry::make('active_users')
                    ->label(__('Active users (30d)'))
                    ->state(fn (Building $record): int => app(\App\Services\BuildingAnalyticsService::class)->metrics($record)['active_users']),
                TextEntry::make('acceptance_rate')
                    ->label(__('Invite acceptance'))
                    ->state(fn (Building $record): string => app(\App\Services\BuildingAnalyticsService::class)->metrics($record)['acceptance_rate'] . '%'),
                TextEntry::make('open_tickets')
                    ->label(__('Open tickets'))
                    ->state(fn (Building $record): int => app(\App\Services\BuildingAnalyticsService::class)->metrics($record)['open_tickets']),
                TextEntry::make('closed_tickets')
                    ->label(__('Closed tickets'))
                    ->state(fn (Building $record): int => app(\App\Services\BuildingAnalyticsService::class)->metrics($record)['closed_tickets']),
                TextEntry::make('announcements')
                    ->label(__('Announcements'))
                    ->state(fn (Building $record): int => app(\App\Services\BuildingAnalyticsService::class)->metrics($record)['announcements']),
                TextEntry::make('polls')
                    ->label(__('Polls'))
                    ->state(fn (Building $record): int => app(\App\Services\BuildingAnalyticsService::class)->metrics($record)['polls']),
                TextEntry::make('avg_resolution_hours')
                    ->label(__('Avg. ticket resolution'))
                    ->state(function (Building $record): string {
                        $hours = app(\App\Services\BuildingAnalyticsService::class)->metrics($record)['avg_resolution_hours'];

                        return $hours === null ? '-' : __(':hours h', ['hours' => $hours]);
                    }),
                TextEntry::make('push_delivery_rate')
                    ->label(__('Push reachability'))
                    ->state(fn (Building $record): string => app(\App\Services\BuildingAnalyticsService::class)->metrics($record)['push_delivery_rate'] . '%'),
                TextEntry::make('last_activity_at')
                    ->label(__('Last activity'))
                    ->state(function (Building $record): string {
                        $at = app(\App\Services\BuildingAnalyticsService::class)->metrics($record)['last_activity_at'];

                        return $at === null ? '-' : \Illuminate\Support\Carbon::parse($at)->diffForHumans();
                    }),
            ])->columns(3)
                ->visible(fn (): bool => Auth::user()?->isSuperAdmin() ?? false),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('managers')->withCount(['apartments', 'tickets']))
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('address')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (BuildingStatus $state): string => $state->label())
                    ->color(fn (BuildingStatus $state): string => $state->color())
                    ->icon(fn (BuildingStatus $state): string => $state->icon())
                    ->sortable(),
                TextColumn::make('trial_ends_at')
                    ->label(__('Trial ends'))
                    ->date()
                    ->placeholder('-')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('managers.name')
                    ->label(__('Admins'))
                    ->badge()
                    ->separator(',')
                    ->limitList(2),
                TextColumn::make('apartments_count')
                    ->counts('apartments')
                    ->label(__('Apartments')),
                TextColumn::make('tickets_count')
                    ->counts('tickets')
                    ->label(__('Tickets')),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('Status'))
                    ->options(BuildingStatus::options()),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                ...self::lifecycleActions(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        if ($user === null || $user->is_super_admin) {
            return $query;
        }

        return $query->whereHas('users', fn (Builder $builder): Builder => $builder->whereKey($user->getKey()));
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBuildings::route('/'),
            'create' => CreateBuilding::route('/create'),
            'view' => ViewBuilding::route('/{record}'),
            'edit' => EditBuilding::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            ApartmentsRelationManager::class,
            AuditLogsRelationManager::class,
        ];
    }
}
