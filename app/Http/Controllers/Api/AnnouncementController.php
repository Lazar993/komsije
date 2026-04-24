<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\BuildingRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Announcement\StoreAnnouncementRequest;
use App\Http\Requests\Announcement\UpdateAnnouncementRequest;
use App\Http\Resources\AnnouncementResource;
use App\Models\Announcement;
use App\Repositories\Contracts\AnnouncementRepositoryInterface;
use App\Services\AnnouncementService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class AnnouncementController extends Controller
{
    public function __construct(
        private readonly AnnouncementRepositoryInterface $announcements,
        private readonly AnnouncementService $announcementService,
        private readonly TenantContext $tenantContext,
    ) {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Announcement::class);

        $includeDrafts = $request->user()->hasBuildingRole($this->tenantContext->buildingId(), BuildingRole::PropertyManager)
            && $request->boolean('include_drafts');

        return AnnouncementResource::collection(
            $this->announcements->paginateForBuilding(
                $this->tenantContext->buildingId(),
                $includeDrafts,
                (int) $request->integer('per_page', 15),
            ),
        );
    }

    public function store(StoreAnnouncementRequest $request): AnnouncementResource
    {
        $this->authorize('create', [Announcement::class, $this->tenantContext->building()]);

        return new AnnouncementResource($this->announcementService->create($this->tenantContext->building(), $request->user(), $request->validated()));
    }

    public function show(Announcement $announcement): AnnouncementResource
    {
        abort_if($announcement->building_id !== $this->tenantContext->buildingId(), 404);
        $this->authorize('view', $announcement);

        return new AnnouncementResource($announcement->load('author')->loadCount('reads'));
    }

    public function update(UpdateAnnouncementRequest $request, Announcement $announcement): AnnouncementResource
    {
        abort_if($announcement->building_id !== $this->tenantContext->buildingId(), 404);
        $this->authorize('update', $announcement);

        return new AnnouncementResource($this->announcementService->update($announcement, $request->validated()));
    }

    public function markAsRead(Request $request, Announcement $announcement)
    {
        abort_if($announcement->building_id !== $this->tenantContext->buildingId(), 404);
        $this->authorize('markAsRead', $announcement);

        $this->announcementService->markAsRead($announcement, $request->user());

        return response()->json(status: 204);
    }
}