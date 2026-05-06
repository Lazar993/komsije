<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\AnnouncementCreated;
use App\Events\AnnouncementImportantUpdated;
use App\Events\AnnouncementPublished;
use App\Models\Building;
use App\Support\Cache\CacheKey;
use Illuminate\Support\Facades\Cache;

final class InvalidateBuildingAnnouncementsCache
{
    public function handle(AnnouncementCreated|AnnouncementPublished|AnnouncementImportantUpdated $event): void
    {
        $buildingId = (int) $event->announcement->building_id;

        Cache::forget(CacheKey::buildingAnnouncements($buildingId));

        // Invalidate per-user unread badge caches so the nav badge updates immediately.
        $userIds = Building::query()
            ->find($buildingId)
            ?->users()
            ->pluck('users.id')
            ->all() ?? [];

        foreach ($userIds as $userId) {
            Cache::forget(CacheKey::userUnreadAnnouncements((int) $userId, $buildingId));
        }
    }
}