<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\PollClosed;
use App\Notifications\PollClosedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

final class NotifyTenantsOfPollClosed implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(PollClosed $event): void
    {
        $poll = $event->poll->loadMissing('building.tenants');

        if ($poll->building->tenants->isEmpty()) {
            return;
        }

        Notification::send($poll->building->tenants, new PollClosedNotification($poll));
    }
}
