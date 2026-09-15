<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Portal;

use App\Services\NotificationFeedService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class NotificationController extends PortalController
{
    public function __construct(private readonly NotificationFeedService $feed)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $accessibleBuildings = $this->accessibleBuildings($request->user());
        $currentBuilding = $this->resolveCurrentBuilding($request, $accessibleBuildings);

        if ($currentBuilding === null) {
            return response()->json([
                'html' => '',
                'has_more' => false,
                'next_page' => null,
            ]);
        }

        $feed = $this->feed->feed(
            $request->user(),
            $currentBuilding,
            (int) $request->integer('page', 1),
        );

        return response()->json([
            'html' => view('portal.notifications.partials.items', ['items' => $feed['items']])->render(),
            'has_more' => $feed['has_more'],
            'next_page' => $feed['next_page'],
        ]);
    }

    public function markAsRead(Request $request, string $notification): JsonResponse
    {
        $request->user()->notifications()
            ->whereKey($notification)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['ok' => true]);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $currentBuilding = $this->resolveCurrentBuilding($request);

        $query = $request->user()->unreadNotifications();

        if ($currentBuilding !== null) {
            $query->where('data->building_id', $currentBuilding->getKey());
        }

        $query->update(['read_at' => now()]);

        return response()->json(['ok' => true]);
    }
}
