<?php

declare(strict_types=1);

namespace App\Events;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class TicketUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Ticket $ticket,
        public readonly User $actor,
        public readonly TicketStatus $fromStatus,
        public readonly TicketStatus $toStatus,
        public readonly ?string $note = null,
    ) {
        \Illuminate\Support\Facades\Log::info('DIAG TicketUpdated dispatched', [
            'ticket_id' => $ticket->getKey(),
            'actor_id' => $actor->getKey(),
            'note' => $note,
            'trace' => collect(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 8))
                ->map(fn ($f) => ($f['class'] ?? '').'::'.($f['function'] ?? '').'@'.($f['line'] ?? '?'))
                ->all(),
        ]);
    }
}