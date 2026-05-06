<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a ticket gets a new assignee (or the assignee changes).
 * Targeted only at the new assignee — much more actionable than the
 * old generic "ticket updated" push.
 */
final class TicketAssigned
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Ticket $ticket,
        public readonly User $actor,
        public readonly User $assignee,
    ) {
    }
}
