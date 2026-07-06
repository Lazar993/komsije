<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\TicketResolved;
use App\Listeners\Concerns\NotifiesTicketAudience;
use App\Notifications\TicketResolvedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

final class NotifyParticipantsOfTicketResolution implements ShouldQueue
{
    use InteractsWithQueue;
    use NotifiesTicketAudience;

    public function handle(TicketResolved $event): void
    {
        $ticket = $event->ticket->loadMissing('reporter', 'assignee');

        // Participants (reporter + assignee) are always notified. Public tickets
        // are broadcast to every resident of the building.
        $recipients = $this->ticketChangeRecipients($ticket, $event->actor, [$ticket->reporter, $ticket->assignee]);

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new TicketResolvedNotification($ticket, $event->actor, $event->note));
    }
}
