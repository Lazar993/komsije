<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\TicketCreated;
use App\Notifications\TicketCreatedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

final class NotifyManagersOfNewTicket implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(TicketCreated $event): void
    {
        $ticket = $event->ticket->loadMissing('building.managers', 'reporter', 'apartment');

        // Exclude the actor: a manager who creates a ticket on behalf of a
        // tenant (or for themselves) shouldn't get pushed by their own action.
        $recipients = $ticket->building->managers
            ->where('id', '!=', $ticket->reported_by)
            ->values();

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new TicketCreatedNotification($ticket));
    }
}