<?php

declare(strict_types=1);

namespace App\Filament\Resources\Finances;

use App\Filament\Concerns\TranslatesFilamentLabels;
use App\Filament\Resources\Finances\VendorResource\Pages\CreateVendor;
use App\Filament\Resources\Finances\VendorResource\Pages\EditVendor;
use App\Filament\Resources\Finances\VendorResource\Pages\ListVendors;
use App\Filament\Resources\Finances\VendorResource\Pages\ViewVendor;
use App\Models\Building;
use App\Models\Vendor;
use App\Rules\BuildingAcceptsWrites;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class VendorResource extends Resource
{
    use TranslatesFilamentLabels;

    protected static ?string $model = Vendor::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::BuildingStorefront;

    protected static string|UnitEnum|null $navigationGroup = 'Finansije';

    protected static ?string $navigationLabel = 'Vendors / Contractors';

    protected static ?string $modelLabel = 'Vendor / Contractor';

    protected static ?string $pluralModelLabel = 'Vendors / Contractors';

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('Vendor / Contractor'))->schema([
                Select::make('building_id')
                    ->label(__('Building'))
                    ->required()
                    ->searchable()
                    ->preload()
                    ->rules([new BuildingAcceptsWrites])
                    ->options(fn (): array => Building::writableSelectOptions(Auth::user())),
                TextInput::make('name')
                    ->label(__('Name'))
                    ->required()
                    ->maxLength(255),
                TextInput::make('pib')
                    ->label(__('Tax ID (PIB)'))
                    ->maxLength(32),
                TextInput::make('registration_number')
                    ->label(__('Registration number'))
                    ->maxLength(64),
                TextInput::make('address')
                    ->label(__('Address'))
                    ->maxLength(255),
                TextInput::make('phone')
                    ->label(__('Phone'))
                    ->tel()
                    ->maxLength(64),
                TextInput::make('email')
                    ->label(__('Email'))
                    ->email()
                    ->maxLength(255),
                Textarea::make('note')
                    ->label(__('Note'))
                    ->rows(3)
                    ->maxLength(2000)
                    ->columnSpanFull(),
                Toggle::make('is_active')
                    ->label(__('Active'))
                    ->default(true),
            ])->columns(2),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('building.name')->label(__('Building')),
            TextEntry::make('name')->label(__('Name')),
            TextEntry::make('pib')->label(__('Tax ID (PIB)'))->placeholder('-'),
            TextEntry::make('registration_number')->label(__('Registration number'))->placeholder('-'),
            TextEntry::make('address')->label(__('Address'))->placeholder('-'),
            TextEntry::make('phone')->label(__('Phone'))->placeholder('-'),
            TextEntry::make('email')->label(__('Email'))->placeholder('-'),
            IconEntry::make('is_active')->label(__('Active'))->boolean(),
            TextEntry::make('note')->label(__('Note'))->placeholder('-')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('building'))
            ->columns([
                TextColumn::make('name')
                    ->label(__('Name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('building.name')
                    ->label(__('Building'))
                    ->sortable(),
                TextColumn::make('pib')
                    ->label(__('Tax ID (PIB)'))
                    ->searchable()
                    ->placeholder('-'),
                TextColumn::make('phone')
                    ->label(__('Phone'))
                    ->placeholder('-'),
                TextColumn::make('email')
                    ->label(__('Email'))
                    ->placeholder('-'),
                IconColumn::make('is_active')
                    ->label(__('Active'))
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('building_id')
                    ->label(__('Building'))
                    ->options(fn (): array => static::accessibleBuildingOptions()),
                TernaryFilter::make('is_active')
                    ->label(__('Active')),
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
            'index' => ListVendors::route('/'),
            'create' => CreateVendor::route('/create'),
            'view' => ViewVendor::route('/{record}'),
            'edit' => EditVendor::route('/{record}/edit'),
        ];
    }
}
