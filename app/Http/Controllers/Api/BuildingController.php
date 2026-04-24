<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Building\StoreBuildingRequest;
use App\Http\Requests\Building\UpdateBuildingRequest;
use App\Http\Resources\BuildingResource;
use App\Models\Building;
use App\Repositories\Contracts\BuildingRepositoryInterface;
use App\Services\BuildingService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class BuildingController extends Controller
{
    public function __construct(
        private readonly BuildingRepositoryInterface $buildings,
        private readonly BuildingService $buildingService,
    ) {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Building::class);

        return BuildingResource::collection($this->buildings->paginateAccessible($request->user(), (int) $request->integer('per_page', 15)));
    }

    public function store(StoreBuildingRequest $request): BuildingResource
    {
        $this->authorize('create', Building::class);

        return new BuildingResource($this->buildingService->create($request->validated(), $request->user()));
    }

    public function show(Request $request, Building $building): BuildingResource
    {
        $this->authorize('view', $building);

        return new BuildingResource($this->buildings->findAccessibleOrFail($request->user(), (int) $building->getKey()));
    }

    public function update(UpdateBuildingRequest $request, Building $building): BuildingResource
    {
        $this->authorize('update', $building);

        return new BuildingResource($this->buildingService->update($building, $request->validated()));
    }
}