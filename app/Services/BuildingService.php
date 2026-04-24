<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\BuildingRole;
use App\Models\Apartment;
use App\Models\Building;
use App\Models\User;
use App\Repositories\Contracts\BuildingRepositoryInterface;
use App\Support\Cache\CacheKey;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final class BuildingService
{
    public function __construct(private readonly BuildingRepositoryInterface $buildings)
    {
    }

    /**
     * @return array{id: int, name: string, address: string, apartments: list<array{id: int, number: string, floor: string|null}>}
     */
    public function getCachedData(int|Building $building): array
    {
        $buildingId = $building instanceof Building ? (int) $building->getKey() : $building;

        return Cache::remember(CacheKey::building($buildingId), now()->addMinutes(60), function () use ($buildingId): array {
            $building = Building::query()
                ->select(['id', 'name', 'address'])
                ->with(['apartments' => fn ($query) => $query
                    ->select(['id', 'building_id', 'number', 'floor'])
                    ->orderBy('number')])
                ->findOrFail($buildingId);

            return [
                'id' => (int) $building->getKey(),
                'name' => $building->name,
                'address' => $building->address,
                'apartments' => $building->apartments
                    ->map(fn (Apartment $apartment): array => [
                        'id' => (int) $apartment->getKey(),
                        'number' => $apartment->number,
                        'floor' => $apartment->floor,
                    ])
                    ->values()
                    ->all(),
            ];
        });
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data, User $actor): Building
    {
        return DB::transaction(function () use ($data, $actor): Building {
            $managerIds = collect($data['manager_ids'] ?? [])->map(fn (mixed $id): int => (int) $id)->all();
            $managerIds[] = (int) $actor->getKey();

            $building = $this->buildings->create([
                'address' => $data['address'],
                'billing_customer_reference' => $data['billing_customer_reference'] ?? null,
                'created_by' => $actor->getKey(),
                'name' => $data['name'],
            ]);

            $this->syncManagers($building, array_values(array_unique($managerIds)));
            Cache::forget(CacheKey::building((int) $building->getKey()));

            return $building->load('managers')->loadCount(['apartments', 'tickets', 'announcements']);
        });
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(Building $building, array $data): Building
    {
        return DB::transaction(function () use ($building, $data): Building {
            $updatedBuilding = $this->buildings->update($building, [
                'address' => $data['address'],
                'billing_customer_reference' => $data['billing_customer_reference'] ?? $building->billing_customer_reference,
                'name' => $data['name'],
            ]);

            if (array_key_exists('manager_ids', $data)) {
                $this->syncManagers($updatedBuilding, collect($data['manager_ids'])->map(fn (mixed $id): int => (int) $id)->all());
            }

            Cache::forget(CacheKey::building((int) $updatedBuilding->getKey()));

            return $updatedBuilding->load('managers')->loadCount(['apartments', 'tickets', 'announcements']);
        });
    }

    /**
     * @param list<int> $managerIds
     */
    private function syncManagers(Building $building, array $managerIds): void
    {
        $managerIds = array_values(array_unique($managerIds));
        $existingManagerIds = $building->managers()->pluck('users.id')->map(fn ($id): int => (int) $id)->all();

        foreach ($managerIds as $managerId) {
            $user = User::query()->find($managerId);

            if ($building->users()->whereKey($managerId)->exists()) {
                $building->users()->updateExistingPivot($managerId, ['role' => BuildingRole::PropertyManager->value]);

                $user?->syncBuildingRole($building->getKey(), BuildingRole::PropertyManager->permissionRoleName());

                continue;
            }

            $building->users()->attach($managerId, ['role' => BuildingRole::PropertyManager->value]);
            $user?->syncBuildingRole($building->getKey(), BuildingRole::PropertyManager->permissionRoleName());
        }

        $toDetach = array_diff($existingManagerIds, $managerIds);

        if ($toDetach !== []) {
            User::query()->whereIn('id', $toDetach)->get()->each(function (User $user) use ($building): void {
                $user->syncBuildingRole($building->getKey(), null);
            });
            $building->users()->detach($toDetach);
        }
    }
}