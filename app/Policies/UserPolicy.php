<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\BuildingRole;
use App\Models\User;

final class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isBuildingAdmin();
    }

    public function view(User $user, User $target): bool
    {
        if ($user->is($target)) {
            return true;
        }

        return $this->sharesManagedBuilding($user, $target);
    }

    public function create(User $user): bool
    {
        return $user->isBuildingAdmin();
    }

    public function update(User $user, User $target): bool
    {
        if ($user->is($target)) {
            return true;
        }

        if ($target->isSuperAdmin() || $target->isBuildingAdmin()) {
            return false;
        }

        return $this->sharesManagedBuilding($user, $target);
    }

    public function delete(User $user, User $target): bool
    {
        if ($user->is($target)) {
            return false;
        }

        if ($target->isSuperAdmin() || $target->isBuildingAdmin()) {
            return false;
        }

        return $this->sharesManagedBuilding($user, $target);
    }

    private function sharesManagedBuilding(User $user, User $target): bool
    {
        $managedBuildingIds = $user->managedBuildingIds();

        if ($managedBuildingIds === []) {
            return false;
        }

        return $target->buildings()
            ->whereIn('buildings.id', $managedBuildingIds)
            ->exists();
    }
}