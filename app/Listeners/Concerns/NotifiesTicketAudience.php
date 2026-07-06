<?php

declare(strict_types=1);

namespace App\Listeners\Concerns;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Collection;

trait NotifiesTicketAudience
{
    /**
     * Resolve who should be notified about a change to a ticket.
     *
     * Private tickets stay between the given participants (reporter, assignee,
     * previous commenters, ...). Public tickets are building-wide: every
     * resident is notified about any change so the whole building stays
     * informed. The actor who made the change is always excluded.
     *
     * @param  iterable<int, User|null>  $participants
     * @return Collection<int, User>
     */
    protected function ticketChangeRecipients(Ticket $ticket, User $actor, iterable $participants = []): Collection
    {
        $recipients = Collection::make($participants);

        if ($ticket->isPublic()) {
            $ticket->loadMissing('building.tenants');
            $recipients = $recipients->merge($ticket->building?->tenants ?? []);
        }

        return $recipients
            ->filter()
            ->unique(fn (User $user): int => (int) $user->getKey())
            ->reject(fn (User $user): bool => $user->is($actor))
            ->values();
    }
}
