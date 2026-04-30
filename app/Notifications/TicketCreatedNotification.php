<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Filament\Resources\Tickets\TicketResource;
use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class TicketCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Ticket $ticket)
    {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail', \App\Notifications\Channels\FcmChannel::class];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject(__('New maintenance ticket reported'))
            ->line(__('A new ticket was reported in :building.', ['building' => $this->ticket->building->name]))
            ->line($this->ticket->title)
            ->action(__('Review ticket'), TicketResource::getUrl('view', ['record' => $this->ticket]));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'building_id' => $this->ticket->building_id,
            'message' => __('A new maintenance ticket was created.'),
            'ticket_id' => $this->ticket->getKey(),
            'title' => $this->ticket->title,
            'type' => 'ticket_created',
        ];
    }

    /**
     * @return array{title: string, body: string, data: array<string, scalar|null>}
     */
    public function toFcm(object $notifiable): array
    {
        return [
            'title' => $this->ticket->title,
            'body' => __('New ticket in :building', ['building' => $this->ticket->building->name]),
            'data' => [
                'type' => 'ticket_created',
                'ticket_id' => $this->ticket->getKey(),
                'building_id' => $this->ticket->building_id,
                'url' => route('portal.tickets.show', $this->ticket, false),
            ],
        ];
    }
}