<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Building;
use App\Models\Poll;
use App\Models\User;

final class PollPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isBuildingAdmin();
    }

    public function view(User $user, Poll $poll): bool
    {
        return $user->belongsToBuilding((int) $poll->building_id);
    }

    public function create(User $user, ?Building $building = null): bool
    {
        if ($building === null) {
            return $user->isBuildingAdmin();
        }

        if (! $user->belongsToBuilding((int) $building->getKey())) {
            return false;
        }

        return $user->isBuildingAdmin((int) $building->getKey());
    }

    public function update(User $user, Poll $poll): bool
    {
        return $user->isBuildingAdmin((int) $poll->building_id);
    }

    public function delete(User $user, Poll $poll): bool
    {
        return $this->update($user, $poll);
    }

    public function vote(User $user, Poll $poll): bool
    {
        return $user->belongsToBuilding((int) $poll->building_id);
    }
}
