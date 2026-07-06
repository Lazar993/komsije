<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\TicketStatusChanged;
use App\Listeners\Concerns\NotifiesTicketAudience;
use App\Notifications\TicketStatusChangedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

final class NotifyParticipantsOfTicketStatusChange implements ShouldQueue
{
    use InteractsWithQueue;
    use NotifiesTicketAudience;

    public function handle(TicketStatusChanged $event): void
    {
        $ticket = $event->ticket->loadMissing('reporter', 'assignee');

        // Participants (reporter + assignee) are always notified. Public tickets
        // are broadcast to every resident of the building so the whole building
        // is kept informed of any change.
        $recipients = $this->ticketChangeRecipients($ticket, $event->actor, [$ticket->reporter, $ticket->assignee]);

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new TicketStatusChangedNotification(
            $ticket,
            $event->actor,
            $event->fromStatus,
            $event->toStatus,
            $event->note,
        ));
    }
}
