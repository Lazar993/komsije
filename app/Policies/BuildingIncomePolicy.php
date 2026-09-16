<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Building;
use App\Models\BuildingIncome;
use App\Models\User;
use App\Policies\Concerns\ChecksBuildingStatus;

final class BuildingIncomePolicy
{
    use ChecksBuildingStatus;

    public function viewAny(User $user): bool
    {
        return $user->isBuildingAdmin();
    }

    public function view(User $user, BuildingIncome $income): bool
    {
        return $user->isBuildingAdmin($income->building_id);
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

    public function update(User $user, BuildingIncome $income): bool
    {
        if (! $this->buildingAllowsWrites($income->building)) {
            return false;
        }

        return $user->isBuildingAdmin($income->building_id);
    }

    public function delete(User $user, BuildingIncome $income): bool
    {
        if (! $this->buildingAllowsWrites($income->building)) {
            return false;
        }

        return $user->isBuildingAdmin($income->building_id);
    }
}
