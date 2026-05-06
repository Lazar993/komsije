<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\AnnouncementImportantUpdated;
use App\Notifications\AnnouncementImportantUpdatedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

final class NotifyResidentsOfImportantAnnouncementUpdate implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(AnnouncementImportantUpdated $event): void
    {
        $announcement = $event->announcement->loadMissing('building.tenants', 'author');

        $recipients = $announcement->building->tenants;

        if ($event->actor !== null) {
            $recipients = $recipients->reject(fn ($u): bool => $u->is($event->actor))->values();
        }

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new AnnouncementImportantUpdatedNotification($announcement));
    }
}
