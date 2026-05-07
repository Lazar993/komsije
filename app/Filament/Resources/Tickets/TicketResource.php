<?php

declare(strict_types=1);

namespace App\Filament\Resources\Tickets;

use App\Enums\BuildingRole;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\TicketVisibility;
use App\Filament\Resources\Tickets\Pages\CreateTicket;
use App\Filament\Resources\Tickets\Pages\EditTicket;
use App\Filament\Resources\Tickets\Pages\ListTickets;
use App\Filament\Resources\Tickets\Pages\ViewTicket;
use App\Models\Apartment;
use App\Models\Building;
use App\Models\Ticket;
use App\Models\User;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class TicketResource extends Resource
{
    protected static ?string $model = Ticket::class;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::WrenchScrewdriver;

    protected static string | UnitEnum | null $navigationGroup = 'Operations';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Ticket')->schema([
                Select::make('building_id')
                    ->required()
                    ->label('Building')
                    ->searchable()
                    ->preload()
                    ->options(fn (): array => self::accessibleBuildingOptions())
                    ->live(),
                Select::make('apartment_id')
                    ->label('Apartment')
                    ->searchable()
                    ->options(fn (Get $get): array => Apartment::query()
                        ->when($get('building_id'), fn ($query, $buildingId) => $query->where('building_id', $buildingId))
                        ->orderBy('number')
                        ->pluck('number', 'id')
                        ->all()),
                Select::make('assigned_to')
                    ->label('Assigned manager')
                    ->searchable()
                    ->options(fn (Get $get): array => User::query()
                        ->when(
                            $get('building_id'),
                            fn (Builder $query, mixed $buildingId): Builder => $query->whereHas('buildings', fn (Builder $builder): Builder => $builder
                                ->where('buildings.id', $buildingId)
                                ->where('building_user.role', BuildingRole::PropertyManager->value)),
                        )
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all()),
                Select::make('priority')
                    ->required()
                    ->options(self::priorityOptions()),
                Select::make('status')
                    ->required()
                    ->options(self::statusOptions())
                    ->default(TicketStatus::New->value),
                Select::make('visibility')
                    ->required()
                    ->options(self::visibilityOptions())
                    ->default(TicketVisibility::Private->value)
                    ->helperText('Public tickets are visible to all residents of the same building, anonymized.'),
                Textarea::make('title')
                    ->rows(2)
                    ->required()
                    ->maxLength(255),
                Textarea::make('description')
                    ->required()
                    ->rows(5)
                    ->maxLength(5000),
                Textarea::make('status_note')
                    ->rows(2)
                    ->maxLength(255),
            ]),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('title'),
            TextEntry::make('building.name'),
            TextEntry::make('apartment.number'),
            TextEntry::make('status')
                ->badge(),
            TextEntry::make('priority')
                ->badge(),
            TextEntry::make('visibility')
                ->badge(),
            TextEntry::make('affected_count')
                ->label('Affected residents'),
            TextEntry::make('reporter.name'),
            TextEntry::make('assignee.name'),
            TextEntry::make('description'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['building', 'apartment', 'assignee', 'reporter']))
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('building.name')
                    ->sortable(),
                TextColumn::make('apartment.number'),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('priority')
                    ->badge(),
                TextColumn::make('visibility')
                    ->badge()
                    ->label('Visibility'),
                TextColumn::make('affected_count')
                    ->label('Affected')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('assignee.name')
                    ->label('Manager'),
                TextColumn::make('updated_at')
                    ->since(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(self::statusOptions()),
                SelectFilter::make('priority')
                    ->options(self::priorityOptions()),
                SelectFilter::make('visibility')
                    ->options(self::visibilityOptions()),
                SelectFilter::make('building_id')
                    ->label('Building')
                    ->options(self::accessibleBuildingOptions()),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
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

        $buildingIds = $user->buildings()->pluck('buildings.id');

        return $query->whereIn('building_id', $buildingIds);
    }

    /**
     * @return array<int, string>
     */
    public static function accessibleBuildingOptions(): array
    {
        $user = Auth::user();
        $query = Building::query()->orderBy('name');

        if ($user !== null && ! $user->is_super_admin) {
            $query->whereHas('users', fn (Builder $builder): Builder => $builder->whereKey($user->getKey()));
        }

        return $query->pluck('name', 'id')->all();
    }

    /**
     * @return array<string, string>
     */
    public static function statusOptions(): array
    {
        return collect(TicketStatus::cases())->mapWithKeys(fn (TicketStatus $status): array => [$status->value => $status->label()])->all();
    }

    /**
     * @return array<string, string>
     */
    public static function priorityOptions(): array
    {
        return collect(TicketPriority::cases())->mapWithKeys(fn (TicketPriority $priority): array => [$priority->value => $priority->label()])->all();
    }

    /**
     * @return array<string, string>
     */
    public static function visibilityOptions(): array
    {
        return collect(TicketVisibility::cases())->mapWithKeys(fn (TicketVisibility $visibility): array => [$visibility->value => $visibility->label()])->all();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTickets::route('/'),
            'create' => CreateTicket::route('/create'),
            'view' => ViewTicket::route('/{record}'),
            'edit' => EditTicket::route('/{record}/edit'),
        ];
    }
}
