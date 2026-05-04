<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Portal;

use App\Http\Requests\Announcement\StoreAnnouncementRequest;
use App\Http\Requests\Announcement\UpdateAnnouncementRequest;
use App\Models\Announcement;
use App\Models\AnnouncementAttachment;
use App\Services\AnnouncementService;
use App\Support\Cache\CacheKey;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\StreamedResponse;
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
        $user = $request->user();
        $isAdmin = $user->isBuildingAdmin($building->getKey());
        $userId = (int) $user->getKey();

        $announcements = Announcement::query()
            ->where('building_id', $building->getKey())
            ->with('author')
            ->withCount(['reads', 'attachments'])
            ->withExists([
                'reads as is_read' => fn ($query) => $query->where('user_id', $userId),
            ])
            ->when(! $isAdmin, fn ($query) => $query->where(
                fn ($q) => $q->whereNotNull('published_at')->orWhere('author_id', $userId)
            ))
            ->orderByDesc('is_important')
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

        $isAdmin = $request->user()->isBuildingAdmin($building->getKey());
        $data = $request->validated();

        if (! $isAdmin) {
            // Tenants submit drafts that need admin approval and cannot self-flag as important.
            $data['published_at'] = null;
            $data['is_important'] = false;
        }

        $payload = array_merge($data, [
            'building_id' => $building->getKey(),
            'attachments' => $request->file('attachments', []),
        ]);

        $announcement = $this->announcementService->create($building, $request->user(), $payload);

        return redirect()
            ->route('portal.announcements.show', $announcement)
            ->with('status', $isAdmin
                ? __('Announcement saved.')
                : __('Vaša objava je poslata na odobrenje upravniku.'));
    }

    public function show(Request $request, Announcement $announcement): View
    {
        abort_if($announcement->building_id !== $this->tenantContext->buildingId(), 404);
        $this->authorize('view', $announcement);

        $announcement->load('author', 'attachments')->loadCount('reads');

        if ($request->user()->can('markAsRead', $announcement)) {
            $this->announcementService->markAsRead($announcement, $request->user());
            $announcement->loadCount('reads');

            Cache::forget(CacheKey::userUnreadAnnouncements(
                (int) $request->user()->getKey(),
                (int) $announcement->building_id,
            ));
        }

        return $this->portalView($request, 'portal.announcements.show', [
            'announcement' => $announcement,
        ]);
    }

    public function edit(Request $request, Announcement $announcement): View
    {
        abort_if($announcement->building_id !== $this->tenantContext->buildingId(), 404);
        $this->authorize('update', $announcement);

        $announcement->load('attachments');

        return $this->portalView($request, 'portal.announcements.edit', [
            'announcement' => $announcement,
        ]);
    }

    public function update(UpdateAnnouncementRequest $request, Announcement $announcement): RedirectResponse
    {
        abort_if($announcement->building_id !== $this->tenantContext->buildingId(), 404);
        $this->authorize('update', $announcement);

        $isAdmin = $request->user()->isBuildingAdmin($announcement->building_id);
        $data = $request->validated();

        if (! $isAdmin) {
            // Authors-only updates cannot change publish state or important flag.
            unset($data['published_at'], $data['is_important']);
        }

        $payload = array_merge($data, [
            'building_id' => $this->tenantContext->buildingId(),
            'attachments' => $request->file('attachments', []),
        ]);

        $announcement = $this->announcementService->update($announcement, $payload, $request->user());

        return redirect()
            ->route('portal.announcements.show', $announcement)
            ->with('status', __('Announcement updated.'));
    }

    public function approve(Request $request, Announcement $announcement): RedirectResponse
    {
        abort_if($announcement->building_id !== $this->tenantContext->buildingId(), 404);
        $this->authorize('approve', $announcement);

        if ($announcement->published_at !== null) {
            return redirect()->route('portal.announcements.show', $announcement);
        }

        $this->announcementService->update($announcement, [
            'published_at' => now(),
        ], $request->user());

        return redirect()
            ->route('portal.announcements.show', $announcement)
            ->with('status', __('Objava je odobrena i objavljena.'));
    }

    public function downloadAttachment(Request $request, Announcement $announcement, AnnouncementAttachment $attachment): StreamedResponse
    {
        abort_if($announcement->building_id !== $this->tenantContext->buildingId(), 404);
        abort_if($attachment->announcement_id !== $announcement->getKey(), 404);
        $this->authorize('view', $announcement);

        $disk = Storage::disk($attachment->disk);

        abort_unless($disk->exists($attachment->path), 404);

        $disposition = $request->query('download') === '1' ? 'attachment' : 'inline';
        $contentDisposition = HeaderUtils::makeDisposition($disposition, $attachment->original_name);

        return response()->stream(function () use ($disk, $attachment): void {
            $stream = $disk->readStream($attachment->path);

            if ($stream === null) {
                return;
            }

            fpassthru($stream);

            if (is_resource($stream)) {
                fclose($stream);
            }
        }, 200, [
            'Content-Type' => $attachment->mime_type ?? 'application/octet-stream',
            'Content-Length' => (string) ($attachment->size ?: $disk->size($attachment->path)),
            'Content-Disposition' => $contentDisposition,
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}