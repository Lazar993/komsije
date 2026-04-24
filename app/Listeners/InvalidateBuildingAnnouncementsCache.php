<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\AnnouncementCreated;
use App\Events\AnnouncementPublished;
use App\Support\Cache\CacheKey;
use Illuminate\Support\Facades\Cache;

final class InvalidateBuildingAnnouncementsCache
{
    public function handle(AnnouncementCreated|AnnouncementPublished $event): void
    {
        Cache::forget(CacheKey::buildingAnnouncements((int) $event->announcement->building_id));
    }
}