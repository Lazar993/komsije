<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\BuildingRole;
use App\Models\Apartment;
use App\Models\Building;
use App\Models\User;

final class ApartmentPolicy
{
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
        if ($building === null) {
            return $user->isBuildingAdmin();
        }

        return $user->isBuildingAdmin($building->getKey());
    }

    public function update(User $user, Apartment $apartment): bool
    {
        return $user->isBuildingAdmin($apartment->building_id);
    }
}