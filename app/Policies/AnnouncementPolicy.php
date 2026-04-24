<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\BuildingRole;
use App\Models\Announcement;
use App\Models\Building;
use App\Models\User;

final class AnnouncementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->buildings()->exists() || $user->is_super_admin;
    }

    public function view(User $user, Announcement $announcement): bool
    {
        if (! $user->belongsToBuilding($announcement->building_id)) {
            return false;
        }

        if ($announcement->published_at !== null) {
            return true;
        }

        return $user->hasBuildingRole($announcement->building_id, BuildingRole::PropertyManager);
    }

    public function create(User $user, ?Building $building = null): bool
    {
        if ($building === null) {
            return $user->isBuildingAdmin();
        }

        return $user->isBuildingAdmin($building->getKey());
    }

    public function update(User $user, Announcement $announcement): bool
    {
        return $user->isBuildingAdmin($announcement->building_id);
    }

    public function markAsRead(User $user, Announcement $announcement): bool
    {
        return $this->view($user, $announcement);
    }
}