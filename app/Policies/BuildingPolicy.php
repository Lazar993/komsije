<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\BuildingRole;
use App\Models\Building;
use App\Models\User;

final class BuildingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->buildings()->exists() || $user->is_super_admin;
    }

    public function view(User $user, Building $building): bool
    {
        return $user->belongsToBuilding($building->getKey());
    }

    public function create(User $user): bool
    {
        return $user->is_super_admin;
    }

    public function update(User $user, Building $building): bool
    {
        return $user->isBuildingAdmin($building->getKey());
    }
}