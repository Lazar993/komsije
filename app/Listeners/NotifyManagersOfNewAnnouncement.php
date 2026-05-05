<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\AnnouncementCreated;
use App\Notifications\AnnouncementCreatedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

final class NotifyManagersOfNewAnnouncement implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(AnnouncementCreated $event): void
    {
        $announcement = $event->announcement->loadMissing('building.managers', 'author');

        if ($announcement->published_at !== null) {
            return;
        }

        if ($announcement->author !== null && $announcement->author->isBuildingAdmin($announcement->building_id)) {
            return;
        }

        $recipients = $announcement->building->managers
            ->where('id', '!=', $announcement->author_id)
            ->values();

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new AnnouncementCreatedNotification($announcement));
    }
}