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

    /*
    |--------------------------------------------------------------------------
    | Subscription lifecycle - super admin only.
    |
    | Gate::before grants super admins every ability, so these methods only
    | ever execute for non-super-admins and therefore always deny. Managers
    | can never modify subscription status.
    |--------------------------------------------------------------------------
    */

    public function delete(User $user, Building $building): bool
    {
        return false;
    }

    public function activate(User $user, Building $building): bool
    {
        return false;
    }

    public function suspend(User $user, Building $building): bool
    {
        return false;
    }

    public function archive(User $user, Building $building): bool
    {
        return false;
    }

    public function manageTrial(User $user, Building $building): bool
    {
        return false;
    }

    public function viewAuditLog(User $user, Building $building): bool
    {
        return false;
    }

    public function viewOnboardingQr(User $user, Building $building): bool
    {
        return $user->isBuildingAdmin($building->getKey());
    }

    public function regenerateOnboardingToken(User $user, Building $building): bool
    {
        unset($user, $building);

        return false;
    }
}