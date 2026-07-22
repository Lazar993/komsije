<?php

declare(strict_types=1);

namespace App\Filament\Resources\BuildingJoinRequests;

use App\Enums\BuildingJoinRequestStatus;
use App\Filament\Resources\BuildingJoinRequests\Pages\ListBuildingJoinRequests;
use App\Filament\Resources\BuildingJoinRequests\Pages\ViewBuildingJoinRequest;
use App\Models\Building;
use App\Models\BuildingJoinRequest;
use App\Services\BuildingJoinRequestService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class BuildingJoinRequestResource extends Resource
{
    protected static ?string $model = BuildingJoinRequest::class;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::UserPlus;

    protected static string | UnitEnum | null $navigationGroup = 'Portfolio';

    protected static ?string $recordTitleAttribute = 'email';

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            \Filament\Infolists\Components\TextEntry::make('building.name')->label(__('Building')),
            \Filament\Infolists\Components\TextEntry::make('first_name')->label(__('First Name')),
            \Filament\Infolists\Components\TextEntry::make('last_name')->label(__('Last Name')),
            \Filament\Infolists\Components\TextEntry::make('apartment_number')->label(__('Apartment')),
            \Filament\Infolists\Components\TextEntry::make('email')->label(__('Email')),
            \Filament\Infolists\Components\TextEntry::make('phone')->label(__('Phone'))->placeholder('-'),
            \Filament\Infolists\Components\TextEntry::make('status')
                ->label(__('Status'))
                ->badge()
                ->formatStateUsing(fn (BuildingJoinRequestStatus $state): string => $state->label()),
            \Filament\Infolists\Components\TextEntry::make('created_at')->label(__('Created'))->since(),
            \Filament\Infolists\Components\TextEntry::make('approved_at')->label(__('Approved at'))->dateTime()->placeholder('-'),
            \Filament\Infolists\Components\TextEntry::make('rejected_at')->label(__('Rejected at'))->dateTime()->placeholder('-'),
            \Filament\Infolists\Components\TextEntry::make('rejection_reason')->label(__('Rejection reason'))->placeholder('-'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('email')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['building', 'approver']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('building.name')
                    ->label(__('Building'))
                    ->sortable(),
                TextColumn::make('first_name')
                    ->label(__('Name'))
                    ->formatStateUsing(fn (BuildingJoinRequest $record): string => $record->fullName())
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query
                        ->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")),
                TextColumn::make('apartment_number')
                    ->label(__('Apartment'))
                    ->searchable(),
                TextColumn::make('email')
                    ->label(__('Email'))
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (BuildingJoinRequestStatus $state): string => $state->label()),
                TextColumn::make('created_at')
                    ->label(__('Date'))
                    ->since(),
            ])
            ->filters([
                SelectFilter::make('building_id')
                    ->label(__('Building'))
                    ->options(self::accessibleBuildingOptions())
                    ->searchable(),
                SelectFilter::make('status')
                    ->options(collect(BuildingJoinRequestStatus::cases())
                        ->mapWithKeys(fn (BuildingJoinRequestStatus $status): array => [$status->value => $status->label()])
                        ->all())
                    ->default(BuildingJoinRequestStatus::Pending->value),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label(__('Approve'))
                    ->icon(Heroicon::CheckCircle)
                    ->color('success')
                    ->visible(fn (BuildingJoinRequest $record): bool => $record->status === BuildingJoinRequestStatus::Pending)
                    ->requiresConfirmation()
                    ->action(function (BuildingJoinRequest $record): void {
                        app(BuildingJoinRequestService::class)->approve($record, Auth::user());

                        Notification::make()
                            ->success()
                            ->title(__('Resident request approved'))
                            ->body(__('Invitation email has been sent using the existing invite flow.'))
                            ->send();
                    }),
                Action::make('reject')
                    ->label(__('Reject'))
                    ->icon(Heroicon::XCircle)
                    ->color('danger')
                    ->visible(fn (BuildingJoinRequest $record): bool => $record->status === BuildingJoinRequestStatus::Pending)
                    ->schema([
                        Textarea::make('reason')
                            ->label(__('Reason (optional)'))
                            ->rows(3)
                            ->maxLength(500),
                    ])
                    ->action(function (BuildingJoinRequest $record, array $data): void {
                        app(BuildingJoinRequestService::class)->reject($record, Auth::user(), $data['reason'] ?? null);

                        Notification::make()
                            ->success()
                            ->title(__('Resident request rejected'))
                            ->send();
                    }),
                ViewAction::make(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        if ($user === null || $user->is_super_admin) {
            return $query;
        }

        return $query->whereIn('building_id', $user->managedBuildingIds());
    }

    /**
     * @return array<int, string>
     */
    public static function accessibleBuildingOptions(): array
    {
        $user = Auth::user();
        $query = Building::query()->orderBy('name');

        if ($user !== null && ! $user->is_super_admin) {
            $query->whereIn('id', $user->managedBuildingIds());
        }

        return $query->pluck('name', 'id')->all();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBuildingJoinRequests::route('/'),
            'view' => ViewBuildingJoinRequest::route('/{record}'),
        ];
    }
}
