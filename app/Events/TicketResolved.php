<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a ticket transitions to TicketStatus::Resolved.
 * The reporter gets a celebratory "your problem is fixed" push instead
 * of the generic "ticket updated" message.
 */
final class TicketResolved
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Ticket $ticket,
        public readonly User $actor,
        public readonly ?string $note,
    ) {
    }
}
