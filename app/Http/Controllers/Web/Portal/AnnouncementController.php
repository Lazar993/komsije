<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Portal;

use App\Http\Requests\Announcement\StoreAnnouncementRequest;
use App\Http\Requests\Announcement\UpdateAnnouncementRequest;
use App\Models\Announcement;
use App\Services\AnnouncementService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class AnnouncementController extends PortalController
{
    public function __construct(
        private readonly AnnouncementService $announcementService,
        private readonly TenantContext $tenantContext,
    ) {
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Announcement::class);

        $building = $this->tenantContext->building();
        $includeDrafts = $request->user()->isBuildingAdmin($building->getKey());

        $announcements = Announcement::query()
            ->where('building_id', $building->getKey())
            ->with('author')
            ->withCount('reads')
            ->withExists([
                'reads as is_read' => fn ($query) => $query->where('user_id', $request->user()->getKey()),
            ])
            ->when(! $includeDrafts, fn ($query) => $query->whereNotNull('published_at'))
            ->latest('published_at')
            ->latest('created_at')
            ->paginate(10)
            ->withQueryString();

        if ($request->ajax()) {
            return view('portal.announcements.partials.results', [
                'announcements' => $announcements,
                'currentBuilding' => $building,
            ]);
        }

        return $this->portalView($request, 'portal.announcements.index', [
            'announcements' => $announcements,
        ]);
    }

    public function create(Request $request): View
    {
        $building = $this->tenantContext->building();
        $this->authorize('create', [Announcement::class, $building]);

        return $this->portalView($request, 'portal.announcements.create');
    }

    public function store(StoreAnnouncementRequest $request): RedirectResponse
    {
        $building = $this->tenantContext->building();
        $this->authorize('create', [Announcement::class, $building]);

        $announcement = $this->announcementService->create($building, $request->user(), array_merge($request->validated(), [
            'building_id' => $building->getKey(),
        ]));

        return redirect()
            ->route('portal.announcements.show', $announcement)
            ->with('status', __('Announcement saved.'));
    }

    public function show(Request $request, Announcement $announcement): View
    {
        abort_if($announcement->building_id !== $this->tenantContext->buildingId(), 404);
        $this->authorize('view', $announcement);

        $announcement->load('author')->loadCount('reads');

        if ($request->user()->can('markAsRead', $announcement)) {
            $this->announcementService->markAsRead($announcement, $request->user());
            $announcement->loadCount('reads');
        }

        return $this->portalView($request, 'portal.announcements.show', [
            'announcement' => $announcement,
        ]);
    }

    public function edit(Request $request, Announcement $announcement): View
    {
        abort_if($announcement->building_id !== $this->tenantContext->buildingId(), 404);
        $this->authorize('update', $announcement);

        return $this->portalView($request, 'portal.announcements.edit', [
            'announcement' => $announcement,
        ]);
    }

    public function update(UpdateAnnouncementRequest $request, Announcement $announcement): RedirectResponse
    {
        abort_if($announcement->building_id !== $this->tenantContext->buildingId(), 404);
        $this->authorize('update', $announcement);

        $announcement = $this->announcementService->update($announcement, array_merge($request->validated(), [
            'building_id' => $this->tenantContext->buildingId(),
        ]));

        return redirect()
            ->route('portal.announcements.show', $announcement)
            ->with('status', __('Announcement updated.'));
    }
}