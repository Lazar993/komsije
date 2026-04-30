<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\TicketUpdated;
use App\Notifications\TicketUpdatedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

final class NotifyTicketReporterOfUpdates implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(TicketUpdated $event): void
    {
        \Illuminate\Support\Facades\Log::info('DIAG NotifyTicketReporterOfUpdates handle()', [
            'ticket_id' => $event->ticket->getKey(),
            'actor_id' => $event->actor->getKey(),
            'pid' => getmypid(),
        ]);

        $ticket = $event->ticket->loadMissing('reporter', 'assignee', 'apartment', 'building');

        $recipients = Collection::make([
            $ticket->reporter,
            $ticket->assignee,
        ])->filter()->unique('id')->reject(fn ($user): bool => $user->is($event->actor));

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new TicketUpdatedNotification($ticket, $event->actor, $event->note));
    }
}