<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\TicketCreated;
use App\Notifications\PublicTicketCreatedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

final class NotifyResidentsOfNewPublicTicket implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(TicketCreated $event): void
    {
        $ticket = $event->ticket;

        // Only public tickets are broadcast to the whole building. Private
        // tickets stay between the reporter and the managers.
        if (! $ticket->isPublic()) {
            return;
        }

        $ticket->loadMissing('building.tenants', 'reporter');

        // Notify every tenant except the reporter (who already knows). Managers
        // are handled separately by NotifyManagersOfNewTicket.
        $recipients = $ticket->building->tenants
            ->where('id', '!=', $ticket->reported_by)
            ->values();

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new PublicTicketCreatedNotification($ticket));
    }
}
