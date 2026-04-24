<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Building;
use App\Models\User;
use App\Repositories\Contracts\BuildingRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class BuildingRepository implements BuildingRepositoryInterface
{
    public function paginateAccessible(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return $this->queryAccessible($user)
            ->withCount(['apartments', 'tickets', 'announcements'])
            ->paginate($perPage);
    }

    public function findAccessibleOrFail(User $user, int $buildingId): Building
    {
        return $this->queryAccessible($user)
            ->with(['managers', 'apartments.tenants'])
            ->withCount(['apartments', 'tickets', 'announcements'])
            ->findOrFail($buildingId);
    }

    public function create(array $data): Building
    {
        return Building::query()->create($data);
    }

    public function update(Building $building, array $data): Building
    {
        $building->fill($data)->save();

        return $building->refresh();
    }

    private function queryAccessible(User $user): Builder
    {
        $query = Building::query();

        if ($user->is_super_admin) {
            return $query;
        }

        return $query->whereHas('users', fn (Builder $builder): Builder => $builder->whereKey($user->getKey()));
    }
}