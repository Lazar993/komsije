<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\TicketResolved;
use App\Notifications\TicketResolvedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

final class NotifyParticipantsOfTicketResolution implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(TicketResolved $event): void
    {
        $ticket = $event->ticket->loadMissing('reporter', 'assignee', 'affectedUsers');

        $recipients = Collection::make([$ticket->reporter, $ticket->assignee])
            ->merge($ticket->isPublic() ? $ticket->affectedUsers : [])
            ->filter()
            ->unique('id')
            ->reject(fn ($user): bool => $user->is($event->actor));

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new TicketResolvedNotification($ticket, $event->actor, $event->note));
    }
}
