<?php

declare(strict_types=1);

namespace App\Filament\Resources\Buildings;

use App\Filament\Resources\Buildings\RelationManagers\ApartmentsRelationManager;
use App\Filament\Resources\Buildings\Pages\CreateBuilding;
use App\Filament\Resources\Buildings\Pages\EditBuilding;
use App\Filament\Resources\Buildings\Pages\ListBuildings;
use App\Filament\Resources\Buildings\Pages\ViewBuilding;
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
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class BuildingResource extends Resource
{
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
            TextEntry::make('name'),
            TextEntry::make('address'),
            TextEntry::make('billing_customer_reference'),
            TextEntry::make('managers.name')
                ->label('Admins')
                ->listWithLineBreaks(),
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
                    ->searchable(),
                TextColumn::make('managers.name')
                    ->label('Admins')
                    ->badge()
                    ->separator(','),
                TextColumn::make('apartments_count')
                    ->counts('apartments')
                    ->label('Apartments'),
                TextColumn::make('tickets_count')
                    ->counts('tickets')
                    ->label('Tickets'),
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
        ];
    }
}
