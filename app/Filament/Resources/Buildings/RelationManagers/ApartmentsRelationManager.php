<?php

declare(strict_types=1);

namespace App\Filament\Resources\Buildings\RelationManagers;

use App\Filament\Resources\Apartments\ApartmentResource;
use App\Models\Apartment;
use App\Models\Building;
use App\Services\ApartmentService;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ApartmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'apartments';

    protected static ?string $title = 'Apartments';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Apartment')->schema([
                TextInput::make('number')
                    ->required()
                    ->maxLength(50),
                TextInput::make('floor')
                    ->maxLength(50),
                Select::make('tenant_ids')
                    ->label('Tenants')
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->options(fn (): array => ApartmentResource::tenantOptionsForBuilding($this->getOwnerRecord()->getKey())),
            ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('number')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('tenants'))
            ->defaultSort('number')
            ->columns([
                TextColumn::make('number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('floor'),
                TextColumn::make('tenants.name')
                    ->label('Tenants')
                    ->badge()
                    ->separator(','),
                // IconColumn::make('available_for_marketplace')
                //     ->boolean(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->using(fn (array $data): Apartment => app(ApartmentService::class)->create($this->getBuilding(), $data)),
            ])
            ->recordActions([
                EditAction::make()
                    ->mutateRecordDataUsing(function (array $data, Apartment $record): array {
                        $data['tenant_ids'] = $record->tenants()->pluck('users.id')->all();

                        return $data;
                    })
                    ->using(fn (Apartment $record, array $data): Apartment => app(ApartmentService::class)->update($record->load('building'), $data)),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        return (string) $ownerRecord->apartments()->count();
    }

    private function getBuilding(): Building
    {
        /** @var Building $building */
        $building = $this->getOwnerRecord();

        return $building;
    }
}