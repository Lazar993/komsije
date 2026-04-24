<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\AnnouncementPublished;
use App\Notifications\AnnouncementPublishedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

final class NotifyBuildingResidentsOfAnnouncement implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(AnnouncementPublished $event): void
    {
        $announcement = $event->announcement->loadMissing('building.tenants', 'author');

        Notification::send($announcement->building->tenants, new AnnouncementPublishedNotification($announcement));
    }
}