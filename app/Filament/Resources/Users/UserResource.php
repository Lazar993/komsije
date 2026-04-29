<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users;

use App\Enums\BuildingRole;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Pages\ViewUser;
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
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::Users;

    protected static string | UnitEnum | null $navigationGroup = 'Administration';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('User')->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255),
                TextInput::make('password')
                    ->password()
                    ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? Hash::make($state) : null)
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->required(fn (string $operation): bool => $operation === 'create'),
                Toggle::make('is_super_admin')
                    ->visible(fn (): bool => Auth::user()?->isSuperAdmin() ?? false),
                Select::make('manager_building_ids')
                    ->label('Admin buildings')
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->visible(fn (): bool => Auth::user()?->isSuperAdmin() ?? false)
                    ->options(fn (): array => self::accessibleBuildingOptions()),
                Select::make('tenant_building_ids')
                    ->label('Tenant buildings')
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->options(fn (): array => self::accessibleBuildingOptions()),
                Select::make('apartment_ids')
                    ->label('Apartments')
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->options(fn (): array => self::accessibleApartmentOptions()),
            ]),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('name'),
            TextEntry::make('email'),
            IconEntry::make('is_super_admin')->boolean(),
            TextEntry::make('buildings.role')
                ->label('Building roles')
                ->state(function (User $record): string {
                    if ($record->isSuperAdmin()) {
                        return 'Super Admin';
                    }

                    return $record->buildings
                        ->map(function (Building $building) use ($record): string {
                            $role = $record->buildingRole($building->getKey());

                            return sprintf('%s: %s', $building->name, $role?->label() ?? 'Unknown');
                        })
                        ->join(', ');
                }),
            TextEntry::make('buildings.name')
                ->label('Buildings')
                ->listWithLineBreaks(),
            TextEntry::make('apartments.number')
                ->label('Apartments')
                ->listWithLineBreaks(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['buildings', 'apartments']))
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->searchable(),
                TextColumn::make('access_role')
                    ->label('Role')
                    ->state(function (User $record): string {
                        if ($record->isSuperAdmin()) {
                            return 'Super Admin';
                        }

                        return $record->isBuildingAdmin() ? 'Admin' : 'Tenant';
                    })
                    ->badge(),
                TextColumn::make('buildings.name')
                    ->label('Buildings')
                    ->badge()
                    ->separator(','),
                IconColumn::make('is_super_admin')
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

        if ($user === null || $user->isSuperAdmin()) {
            return $query;
        }

        $buildingIds = $user->managedBuildingIds();

        return $query->where(function (Builder $builder) use ($buildingIds, $user): void {
            $builder
                ->whereKey($user->getKey())
                ->orWhereHas('buildings', fn (Builder $buildingQuery): Builder => $buildingQuery->whereIn('buildings.id', $buildingIds));
        });
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
     * @return array<int, string>
     */
    public static function accessibleApartmentOptions(): array
    {
        $user = Auth::user();
        $query = Apartment::query()->with('building')->orderBy('number');

        if ($user !== null && ! $user->isSuperAdmin()) {
            $query->whereIn('building_id', $user->managedBuildingIds());
        }

        return $query->get()->mapWithKeys(fn (Apartment $apartment): array => [
            $apartment->getKey() => sprintf('%s • %s', $apartment->building->name, $apartment->number),
        ])->all();
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function syncRelationships(User $user, array $data): void
    {
        $actor = Auth::user();
        $managerBuildingIds = collect($data['manager_building_ids'] ?? [])->map(fn (mixed $id): int => (int) $id)->unique();
        $tenantBuildingIds = collect($data['tenant_building_ids'] ?? [])->map(fn (mixed $id): int => (int) $id)->unique();
        $apartmentIds = collect($data['apartment_ids'] ?? [])->map(fn (mixed $id): int => (int) $id)->unique()->values();

        if ($actor !== null && ! $actor->isSuperAdmin()) {
            $allowedBuildingIds = collect($actor->managedBuildingIds());

            $managerBuildingIds = collect();
            $tenantBuildingIds = $tenantBuildingIds->intersect($allowedBuildingIds);
            $apartmentIds = $apartmentIds->intersect(
                Apartment::query()->whereIn('building_id', $allowedBuildingIds)->pluck('id')->map(fn ($id): int => (int) $id),
            )->values();
        }

        $tenantBuildingIds = $tenantBuildingIds->merge(
            Apartment::query()->whereIn('id', $apartmentIds)->pluck('building_id')->map(fn ($id): int => (int) $id),
        )->unique();

        // Build the desired set of (building_id, role) pairs. A user may hold
        // both the manager and tenant role in the same building.
        $desired = collect();

        foreach ($tenantBuildingIds as $buildingId) {
            $desired->push([(int) $buildingId, BuildingRole::Tenant->value]);
        }

        foreach ($managerBuildingIds as $buildingId) {
            $desired->push([(int) $buildingId, BuildingRole::PropertyManager->value]);
        }

        $existing = $user->buildings()
            ->get()
            ->map(function (Building $building): array {
                $role = $building->pivot?->role;
                $value = $role instanceof BuildingRole ? $role->value : $role;

                return [(int) $building->getKey(), $value];
            });

        $existingKeys = $existing->map(fn (array $pair): string => $pair[0] . '|' . $pair[1])->all();
        $desiredKeys = $desired->map(fn (array $pair): string => $pair[0] . '|' . $pair[1])->all();

        $toAttach = array_values(array_diff($desiredKeys, $existingKeys));
        $toDetach = array_values(array_diff($existingKeys, $desiredKeys));

        foreach ($toAttach as $key) {
            [$buildingId, $role] = explode('|', $key, 2);
            $user->buildings()->attach((int) $buildingId, ['role' => $role]);
        }

        foreach ($toDetach as $key) {
            [$buildingId, $role] = explode('|', $key, 2);
            $user->buildings()->newPivotStatement()
                ->where('user_id', $user->getKey())
                ->where('building_id', (int) $buildingId)
                ->where('role', $role)
                ->delete();
        }

        $user->apartments()->sync($apartmentIds->all());

        $affectedBuildingIds = collect($toAttach)->merge($toDetach)
            ->map(fn (string $key): int => (int) explode('|', $key, 2)[0])
            ->unique();

        foreach ($affectedBuildingIds as $buildingId) {
            $user->syncBuildingRole($buildingId);
        }

        $user->syncGlobalRoles($user->is_super_admin ? ['super_admin'] : []);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'view' => ViewUser::route('/{record}'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
