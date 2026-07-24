<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\NeighborBoardPostStatus;
use App\Models\Building;
use App\Models\NeighborBoardPost;
use App\Models\User;
use App\Policies\Concerns\ChecksBuildingStatus;

final class NeighborBoardPostPolicy
{
    use ChecksBuildingStatus;

    public function viewAny(User $user): bool
    {
        return $user->buildings()->exists() || $user->is_super_admin;
    }

    public function view(User $user, NeighborBoardPost $post): bool
    {
        return $user->belongsToBuilding((int) $post->building_id);
    }

    public function create(User $user, ?Building $building = null): bool
    {
        if (! $this->buildingAllowsWrites($building)) {
            return false;
        }

        if ($building === null) {
            return $user->buildings()->exists();
        }

        return $user->belongsToBuilding((int) $building->getKey());
    }

    public function update(User $user, NeighborBoardPost $post): bool
    {
        if (! $this->buildingAllowsWrites($post->building)) {
            return false;
        }

        if ($user->isBuildingAdmin((int) $post->building_id)) {
            return true;
        }

        return (int) $post->author_id === (int) $user->getKey()
            && $post->status !== NeighborBoardPostStatus::Archived
            && $user->belongsToBuilding((int) $post->building_id);
    }

    public function delete(User $user, NeighborBoardPost $post): bool
    {
        if (! $this->buildingAllowsWrites($post->building)) {
            return false;
        }

        if ($user->isBuildingAdmin((int) $post->building_id)) {
            return true;
        }

        return (int) $post->author_id === (int) $user->getKey()
            && $user->belongsToBuilding((int) $post->building_id);
    }

    public function markResolved(User $user, NeighborBoardPost $post): bool
    {
        if (! $this->buildingAllowsWrites($post->building)) {
            return false;
        }

        return (int) $post->author_id === (int) $user->getKey()
            && $post->status === NeighborBoardPostStatus::Active
            && $user->belongsToBuilding((int) $post->building_id);
    }

    public function comment(User $user, NeighborBoardPost $post): bool
    {
        if (! $this->buildingAllowsWrites($post->building)) {
            return false;
        }

        if ((bool) $post->comments_locked) {
            return false;
        }

        if ($post->status === NeighborBoardPostStatus::Archived) {
            return false;
        }

        return $user->belongsToBuilding((int) $post->building_id);
    }

    public function archive(User $user, NeighborBoardPost $post): bool
    {
        if (! $this->buildingAllowsWrites($post->building)) {
            return false;
        }

        return $user->isBuildingAdmin((int) $post->building_id);
    }

    public function restoreArchived(User $user, NeighborBoardPost $post): bool
    {
        if (! $this->buildingAllowsWrites($post->building)) {
            return false;
        }

        return $user->isBuildingAdmin((int) $post->building_id);
    }

    public function pin(User $user, NeighborBoardPost $post): bool
    {
        return $this->archive($user, $post);
    }

    public function lockComments(User $user, NeighborBoardPost $post): bool
    {
        return $this->archive($user, $post);
    }
}
