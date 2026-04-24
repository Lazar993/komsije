<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Portal;

use App\Models\AnnouncementRead;
use App\Services\AnnouncementService;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class DashboardController extends PortalController
{
    public function __construct(
        private readonly AnnouncementService $announcementService,
        private readonly DashboardService $dashboardService,
    ) {
    }

    public function __invoke(Request $request): View
    {
        $accessibleBuildings = $this->accessibleBuildings($request->user());
        $currentBuilding = $this->resolveCurrentBuilding($request, $accessibleBuildings);

        $recentTickets = collect();
        $recentAnnouncements = collect();
        $dashboard = [
            'total_tickets' => 0,
            'active_tickets' => 0,
            'resolved_tickets' => 0,
            'recent_tickets' => collect(),
        ];

        if ($currentBuilding !== null) {
            $currentBuilding->loadCount(['apartments', 'tickets', 'announcements']);

            $dashboard = $this->dashboardService->getForUser($request->user(), $currentBuilding);
            $recentTickets = $dashboard['recent_tickets'];

            $recentAnnouncements = $this->announcementService->getLatestForBuilding(
                (int) $currentBuilding->getKey(),
                $request->user()->isBuildingAdmin($currentBuilding->getKey()),
            );

            $readAnnouncementIds = AnnouncementRead::query()
                ->where('user_id', $request->user()->getKey())
                ->whereIn('announcement_id', $recentAnnouncements->pluck('id')->all())
                ->pluck('announcement_id')
                ->map(fn (mixed $id): int => (int) $id)
                ->all();
            $readLookup = array_fill_keys($readAnnouncementIds, true);

            $recentAnnouncements = $recentAnnouncements->map(function (object $announcement) use ($readLookup): object {
                $announcement->is_read = isset($readLookup[$announcement->id]);

                return $announcement;
            });
        }

        return $this->portalView($request, 'portal.dashboard', [
            'accessibleBuildings' => $accessibleBuildings,
            'currentBuilding' => $currentBuilding,
            'dashboard' => $dashboard,
            'recentAnnouncements' => $recentAnnouncements,
            'recentTickets' => $recentTickets,
        ]);
    }
}