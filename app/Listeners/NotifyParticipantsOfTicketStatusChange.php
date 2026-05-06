<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\TicketStatusChanged;
use App\Notifications\TicketStatusChangedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

final class NotifyParticipantsOfTicketStatusChange implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(TicketStatusChanged $event): void
    {
        $ticket = $event->ticket->loadMissing('reporter', 'assignee');

        $recipients = Collection::make([$ticket->reporter, $ticket->assignee])
            ->filter()
            ->unique('id')
            ->reject(fn ($user): bool => $user->is($event->actor));

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
