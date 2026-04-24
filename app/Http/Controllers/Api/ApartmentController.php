<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Apartment\StoreApartmentRequest;
use App\Http\Requests\Apartment\UpdateApartmentRequest;
use App\Http\Resources\ApartmentResource;
use App\Models\Apartment;
use App\Repositories\Contracts\ApartmentRepositoryInterface;
use App\Services\ApartmentService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class ApartmentController extends Controller
{
    public function __construct(
        private readonly ApartmentRepositoryInterface $apartments,
        private readonly ApartmentService $apartmentService,
        private readonly TenantContext $tenantContext,
    ) {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Apartment::class);

        return ApartmentResource::collection(
            $this->apartments->paginateForBuilding(
                $this->tenantContext->buildingId(),
                $request->only('search'),
                (int) $request->integer('per_page', 15),
            ),
        );
    }

    public function store(StoreApartmentRequest $request): ApartmentResource
    {
        $this->authorize('create', [Apartment::class, $this->tenantContext->building()]);

        return new ApartmentResource($this->apartmentService->create($this->tenantContext->building(), $request->validated()));
    }

    public function show(Apartment $apartment): ApartmentResource
    {
        $this->authorize('view', $apartment);
        abort_if($apartment->building_id !== $this->tenantContext->buildingId(), 404);

        return new ApartmentResource($apartment->load('tenants'));
    }

    public function update(UpdateApartmentRequest $request, Apartment $apartment): ApartmentResource
    {
        $this->authorize('update', $apartment);
        abort_if($apartment->building_id !== $this->tenantContext->buildingId(), 404);

        return new ApartmentResource($this->apartmentService->update($apartment->load('building'), $request->validated()));
    }
}