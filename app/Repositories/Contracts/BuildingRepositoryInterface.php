<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Building;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface BuildingRepositoryInterface
{
    public function paginateAccessible(User $user, int $perPage = 15): LengthAwarePaginator;

    public function findAccessibleOrFail(User $user, int $buildingId): Building;

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): Building;

    /**
     * @param array<string, mixed> $data
     */
    public function update(Building $building, array $data): Building;
}