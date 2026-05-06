<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\TicketAssigned;
use App\Notifications\TicketAssignedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

final class NotifyAssigneeOfTicketAssignment implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(TicketAssigned $event): void
    {
        $assignee = $event->assignee;

        // Self-assignment shouldn't push the actor.
        if ($assignee->is($event->actor)) {
            return;
        }

        $assignee->notify(new TicketAssignedNotification($event->ticket, $event->actor));
    }
}
