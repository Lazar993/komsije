<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\PollEndingSoon;
use App\Models\User;
use App\Models\Vote;
use App\Notifications\PollEndingSoonNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

final class NotifyTenantsOfPollEndingSoon implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(PollEndingSoon $event): void
    {
        $poll = $event->poll->loadMissing('building.tenants');

        $votedUserIds = Vote::query()
            ->where('poll_id', $poll->getKey())
            ->pluck('user_id')
            ->all();

        $recipients = $poll->building->tenants
            ->reject(fn (User $u): bool => in_array($u->getKey(), $votedUserIds, true))
            ->values();

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new PollEndingSoonNotification($poll));
    }
}
