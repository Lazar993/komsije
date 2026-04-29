<?php

declare(strict_types=1);

namespace App\Filament\Resources\Apartments;

use App\Filament\Resources\Apartments\Pages\CreateApartment;
use App\Filament\Resources\Apartments\Pages\EditApartment;
use App\Filament\Resources\Apartments\Pages\ListApartments;
use App\Filament\Resources\Apartments\Pages\ViewApartment;
use App\Models\Apartment;
use App\Models\Building;
use App\Models\User;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class ApartmentResource extends Resource
{
    protected static ?string $model = Apartment::class;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::HomeModern;

    protected static string | UnitEnum | null $navigationGroup = 'Portfolio';

    protected static ?string $recordTitleAttribute = 'number';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Apartment')->schema([
                Select::make('building_id')
                    ->label('Building')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->options(fn (): array => self::accessibleBuildingOptions()),
                TextInput::make('number')
                    ->required()
                    ->maxLength(50),
                TextInput::make('floor')
                    ->maxLength(50),
                // Toggle::make('available_for_marketplace'),
                // TextInput::make('marketplace_listing_reference')
                //     ->maxLength(255),
                Select::make('tenant_ids')
                    ->label('Tenants')
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->options(fn (Get $get): array => self::tenantOptionsForBuilding($get('building_id'))),
            ]),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('building.name'),
            TextEntry::make('number'),
            TextEntry::make('floor'),
            IconEntry::make('available_for_marketplace')
                ->boolean(),
            TextEntry::make('tenants.name')
                ->label('Tenants')
                ->listWithLineBreaks(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('number')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['building', 'tenants']))
            ->columns([
                TextColumn::make('building.name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('floor'),
                TextColumn::make('tenants.name')
                    ->label('Tenants')
                    ->badge()
                    ->separator(','),
                IconColumn::make('available_for_marketplace')
                    ->boolean(),
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

        if ($user !== null && ! $user->isSuperAdmin()) {
            $query->whereIn('id', $user->managedBuildingIds());
        }

        return $query->pluck('name', 'id')->all();
    }

    /**
     * @param mixed $buildingId
     * @return array<int, string>
     */
    public static function tenantOptionsForBuilding(mixed $buildingId): array
    {
        $query = User::query()
            ->where('is_super_admin', false)
            ->orderBy('name');

        if ($buildingId !== null && $buildingId !== '') {
            // Include every user attached to the building (tenants and managers)
            // so a manager can also be assigned as a tenant of an apartment.
            $query->whereHas('buildings', fn (Builder $builder): Builder => $builder
                ->where('buildings.id', (int) $buildingId));
        }

        return $query->pluck('name', 'id')->all();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListApartments::route('/'),
            'create' => CreateApartment::route('/create'),
            'view' => ViewApartment::route('/{record}'),
            'edit' => EditApartment::route('/{record}/edit'),
        ];
    }
}
