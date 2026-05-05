<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\PollCreated;
use App\Notifications\PollCreatedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

final class NotifyTenantsOfNewPoll implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(PollCreated $event): void
    {
        $poll = $event->poll->loadMissing('building.tenants');

        if ($poll->building->tenants->isEmpty()) {
            return;
        }

        Notification::send($poll->building->tenants, new PollCreatedNotification($poll));
    }
}
