<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Apartment;
use App\Repositories\Contracts\ApartmentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ApartmentRepository implements ApartmentRepositoryInterface
{
    public function paginateForBuilding(int $buildingId, array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return Apartment::query()
            ->where('building_id', $buildingId)
            ->with(['tenants'])
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $query->where('number', 'like', "%{$search}%");
            })
            ->paginate($perPage);
    }

    public function create(array $data): Apartment
    {
        return Apartment::query()->create($data);
    }

    public function update(Apartment $apartment, array $data): Apartment
    {
        $apartment->fill($data)->save();

        return $apartment->refresh();
    }
}