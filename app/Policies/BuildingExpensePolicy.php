<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Building;
use App\Models\BuildingExpense;
use App\Models\User;
use App\Policies\Concerns\ChecksBuildingStatus;

final class BuildingExpensePolicy
{
    use ChecksBuildingStatus;

    public function viewAny(User $user): bool
    {
        return $user->isBuildingAdmin();
    }

    public function view(User $user, BuildingExpense $expense): bool
    {
        return $user->isBuildingAdmin($expense->building_id);
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

    public function update(User $user, BuildingExpense $expense): bool
    {
        if (! $this->buildingAllowsWrites($expense->building)) {
            return false;
        }

        return $user->isBuildingAdmin($expense->building_id);
    }

    public function delete(User $user, BuildingExpense $expense): bool
    {
        if (! $this->buildingAllowsWrites($expense->building)) {
            return false;
        }

        return $user->isBuildingAdmin($expense->building_id);
    }
}
