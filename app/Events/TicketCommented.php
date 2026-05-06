<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a new comment is added to a ticket.
 * Audience: reporter, current assignee, previous commenters — minus the actor.
 */
final class TicketCommented
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Ticket $ticket,
        public readonly TicketComment $comment,
        public readonly User $actor,
    ) {
    }
}
