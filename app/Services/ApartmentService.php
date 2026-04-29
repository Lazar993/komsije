<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\BuildingRole;
use App\Models\Apartment;
use App\Models\Building;
use App\Models\User;
use App\Repositories\Contracts\ApartmentRepositoryInterface;
use App\Support\Cache\CacheKey;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final class ApartmentService
{
    public function __construct(private readonly ApartmentRepositoryInterface $apartments)
    {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(Building $building, array $data): Apartment
    {
        return DB::transaction(function () use ($building, $data): Apartment {
            $apartment = $this->apartments->create([
                'available_for_marketplace' => $data['available_for_marketplace'] ?? false,
                'building_id' => $building->getKey(),
                'floor' => $data['floor'] ?? null,
                'marketplace_listing_reference' => $data['marketplace_listing_reference'] ?? null,
                'number' => $data['number'],
            ]);

            $this->syncTenants($building, $apartment, $data['tenant_ids'] ?? []);
            Cache::forget(CacheKey::building((int) $building->getKey()));

            return $apartment->load('tenants');
        });
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(Apartment $apartment, array $data): Apartment
    {
        return DB::transaction(function () use ($apartment, $data): Apartment {
            $updatedApartment = $this->apartments->update($apartment, [
                'available_for_marketplace' => $data['available_for_marketplace'] ?? $apartment->available_for_marketplace,
                'floor' => $data['floor'] ?? $apartment->floor,
                'marketplace_listing_reference' => $data['marketplace_listing_reference'] ?? $apartment->marketplace_listing_reference,
                'number' => $data['number'],
            ]);

            if (array_key_exists('tenant_ids', $data)) {
                $this->syncTenants($updatedApartment->building, $updatedApartment, $data['tenant_ids']);
            }

            Cache::forget(CacheKey::building((int) $updatedApartment->building_id));

            return $updatedApartment->load('tenants');
        });
    }

    public function assignTenant(Apartment $apartment, User $user): void
    {
        if ($user->isSuperAdmin()) {
            // Super admin is global by Gate::before and must never appear in
            // the building/apartment pivot tables.
            return;
        }

        $building = $apartment->relationLoaded('building') && $apartment->building !== null
            ? $apartment->building
            : $apartment->building()->firstOrFail();

        $this->syncTenantMembership($building, $apartment, $user);
    }

    /**
     * @param array<int, mixed> $tenantIds
     */
    private function syncTenants(Building $building, Apartment $apartment, array $tenantIds): void
    {
        $tenantIds = collect($tenantIds)
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values()
            ->all();

        // Strip super admin IDs out — they should never be apartment tenants.
        $superAdminIds = User::query()
            ->whereIn('id', $tenantIds)
            ->where('is_super_admin', true)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        $tenantIds = array_values(array_diff($tenantIds, $superAdminIds));

        $apartment->tenants()->sync($tenantIds);

        foreach ($tenantIds as $tenantId) {
            $user = User::query()->find($tenantId);

            if ($user !== null) {
                $this->syncTenantMembership($building, $apartment, $user);
            }
        }
    }

    private function syncTenantMembership(Building $building, Apartment $apartment, User $user): void
    {
        if ($user->isSuperAdmin()) {
            return;
        }

        $apartment->tenants()->syncWithoutDetaching([$user->getKey()]);

        $hasTenantRow = $building->users()
            ->wherePivot('role', BuildingRole::Tenant->value)
            ->whereKey($user->getKey())
            ->exists();

        if (! $hasTenantRow) {
            $building->users()->attach($user->getKey(), ['role' => BuildingRole::Tenant->value]);
        }

        $user->syncBuildingRole($building->getKey());
    }
}