<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Building;
use App\Models\BuildingJoinRequest;
use App\Models\User;
use App\Policies\Concerns\ChecksBuildingStatus;

final class BuildingJoinRequestPolicy
{
    use ChecksBuildingStatus;

    public function viewAny(User $user): bool
    {
        return $user->isBuildingAdmin();
    }

    public function view(User $user, BuildingJoinRequest $joinRequest): bool
    {
        return $user->isBuildingAdmin((int) $joinRequest->building_id);
    }

    public function update(User $user, BuildingJoinRequest $joinRequest): bool
    {
        if (! $this->buildingAllowsWrites($joinRequest->building)) {
            return false;
        }

        return $user->isBuildingAdmin((int) $joinRequest->building_id);
    }

    public function approve(User $user, BuildingJoinRequest $joinRequest): bool
    {
        return $this->update($user, $joinRequest);
    }

    public function reject(User $user, BuildingJoinRequest $joinRequest): bool
    {
        return $this->update($user, $joinRequest);
    }

    public function create(User $user, ?Building $building = null): bool
    {
        if (! $this->buildingAllowsWrites($building)) {
            return false;
        }

        return $user->isBuildingAdmin($building?->getKey());
    }
}
