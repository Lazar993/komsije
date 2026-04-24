<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Apartment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ApartmentRepositoryInterface
{
    /**
     * @param array<string, mixed> $filters
     */
    public function paginateForBuilding(int $buildingId, array $filters, int $perPage = 15): LengthAwarePaginator;

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): Apartment;

    /**
     * @param array<string, mixed> $data
     */
    public function update(Apartment $apartment, array $data): Apartment;
}