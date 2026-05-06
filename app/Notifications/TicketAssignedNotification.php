<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Ticket;
use App\Models\User;
use App\Notifications\Concerns\ResolvesChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class TicketAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;
    use ResolvesChannels;

    public function __construct(
        private readonly Ticket $ticket,
        private readonly User $actor,
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
            ->subject(__('Ticket assigned to you'))
            ->line(__(':actor assigned the ticket ":title" to you.', ['actor' => $this->actor->name, 'title' => $this->ticket->title]))
            ->action(__('Open ticket'), route('portal.tickets.show', $this->ticket));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'actor_id' => $this->actor->getKey(),
            'building_id' => $this->ticket->building_id,
            'message' => __(':actor assigned this ticket to you.', ['actor' => $this->actor->name]),
            'ticket_id' => $this->ticket->getKey(),
            'title' => $this->ticket->title,
            'type' => 'ticket_assigned',
        ];
    }

    /**
     * @return array{title: string, body: string, data: array<string, scalar|null>}
     */
    public function toFcm(object $notifiable): array
    {
        return [
            'title' => __('Assigned to you'),
            'body' => __(':actor assigned ":title" to you.', ['actor' => $this->actor->name, 'title' => $this->ticket->title]),
            'data' => [
                'type' => 'ticket_assigned',
                'ticket_id' => $this->ticket->getKey(),
                'building_id' => $this->ticket->building_id,
                'url' => route('portal.tickets.show', $this->ticket, false),
            ],
        ];
    }
}
