<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\TicketCommented;
use App\Models\TicketComment;
use App\Notifications\TicketCommentedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

final class NotifyParticipantsOfTicketComment implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(TicketCommented $event): void
    {
        $ticket = $event->ticket->loadMissing('reporter', 'assignee');

        // Audience: reporter, current assignee, anyone who has previously
        // commented on this ticket — minus the actor themselves.
        $previousCommenters = TicketComment::query()
            ->where('ticket_id', $ticket->getKey())
            ->where('id', '!=', $event->comment->getKey())
            ->with('user')
            ->get()
            ->pluck('user');

        $recipients = Collection::make([$ticket->reporter, $ticket->assignee])
            ->merge($previousCommenters)
            ->filter()
            ->unique('id')
            ->reject(fn ($user): bool => $user->is($event->actor));

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send(
            $recipients,
            new TicketCommentedNotification($ticket, $event->comment, $event->actor),
        );
    }
}
