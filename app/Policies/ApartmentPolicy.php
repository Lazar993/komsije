<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\BuildingRole;
use App\Models\Apartment;
use App\Models\Building;
use App\Models\User;
use App\Policies\Concerns\ChecksBuildingStatus;

final class ApartmentPolicy
{
    use ChecksBuildingStatus;

    public function viewAny(User $user): bool
    {
        return $user->buildings()->exists() || $user->is_super_admin;
    }

    public function view(User $user, Apartment $apartment): bool
    {
        return $user->belongsToBuilding($apartment->building_id);
    }

    public function create(User $user, ?Building $building = null): bool
    {
        if (! $this->buildingAllowsWrites($building)) {
            return false;
        }

        if ($building === null) {
            return $user->isBuildingAdmin();
        }

        return $user->isBuildingAdmin($building->getKey());
    }

    public function update(User $user, Apartment $apartment): bool
    {
        if (! $this->buildingAllowsWrites($apartment->building)) {
            return false;
        }

        return $user->isBuildingAdmin($apartment->building_id);
    }

    public function delete(User $user, Apartment $apartment): bool
    {
        return $this->update($user, $apartment);
    }
}