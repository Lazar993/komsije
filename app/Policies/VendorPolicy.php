<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Building;
use App\Models\User;
use App\Models\Vendor;
use App\Policies\Concerns\ChecksBuildingStatus;

final class VendorPolicy
{
    use ChecksBuildingStatus;

    public function viewAny(User $user): bool
    {
        return $user->isBuildingAdmin();
    }

    public function view(User $user, Vendor $vendor): bool
    {
        return $user->isBuildingAdmin($vendor->building_id);
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

    public function update(User $user, Vendor $vendor): bool
    {
        if (! $this->buildingAllowsWrites($vendor->building)) {
            return false;
        }

        return $user->isBuildingAdmin($vendor->building_id);
    }

    public function delete(User $user, Vendor $vendor): bool
    {
        if (! $this->buildingAllowsWrites($vendor->building)) {
            return false;
        }

        return $user->isBuildingAdmin($vendor->building_id);
    }
}
