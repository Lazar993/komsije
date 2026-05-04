<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Filament\Resources\Tickets\TicketResource;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\Concerns\ResolvesChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class TicketUpdatedNotification extends Notification implements ShouldQueue
{
    use Queueable;
    use ResolvesChannels;

    public function __construct(
        private readonly Ticket $ticket,
        private readonly User $actor,
        private readonly ?string $note,
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return $this->resolveChannels($notifiable, category: 'tickets');
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject(__('Maintenance ticket updated'))
            ->line(__(':actor updated the ticket ":title".', ['actor' => $this->actor->name, 'title' => $this->ticket->title]))
            ->line($this->note ?? __('Open the ticket to review the latest status and discussion.'))
            ->action(__('Open ticket'), TicketResource::getUrl('view', ['record' => $this->ticket]));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'actor_id' => $this->actor->getKey(),
            'building_id' => $this->ticket->building_id,
            'message' => $this->note ?? __('A maintenance ticket was updated.'),
            'ticket_id' => $this->ticket->getKey(),
            'title' => $this->ticket->title,
            'type' => 'ticket_updated',
        ];
    }

    /**
     * @return array{title: string, body: string, data: array<string, scalar|null>}
     */
    public function toFcm(object $notifiable): array
    {
        $body = $this->note !== null && $this->note !== ''
            ? $this->note
            : __(':actor updated this ticket.', ['actor' => $this->actor->name]);

        return [
            'title' => $this->ticket->title,
            'body' => $body,
            'data' => [
                'type' => 'ticket_updated',
                'ticket_id' => $this->ticket->getKey(),
                'building_id' => $this->ticket->building_id,
                'url' => route('portal.tickets.show', $this->ticket, false),
            ],
        ];
    }
}