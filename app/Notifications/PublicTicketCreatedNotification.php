<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Ticket;
use App\Notifications\Concerns\ResolvesChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to every resident of a building when a new public ticket is created,
 * so the whole building is aware of the shared issue. Recipients are tenants,
 * so all links point at the tenant-facing portal (never /admin, which 403s).
 */
final class PublicTicketCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;
    use ResolvesChannels;

    public function __construct(private readonly Ticket $ticket)
    {
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
            ->subject(__('New public ticket in your building'))
            ->line(__('A new public ticket was reported in :building.', ['building' => $this->ticket->building->name]))
            ->line($this->ticket->title)
            ->action(__('View ticket'), route('portal.tickets.show', $this->ticket));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'building_id' => $this->ticket->building_id,
            'message' => __('A new public ticket was created in your building.'),
            'ticket_id' => $this->ticket->getKey(),
            'title' => $this->ticket->title,
            'type' => 'public_ticket_created',
        ];
    }

    /**
     * @return array{title: string, body: string, data: array<string, scalar|null>}
     */
    public function toFcm(object $notifiable): array
    {
        return [
            'title' => $this->ticket->title,
            'body' => __('New public ticket in :building', ['building' => $this->ticket->building->name]),
            'data' => [
                'type' => 'public_ticket_created',
                'ticket_id' => $this->ticket->getKey(),
                'building_id' => $this->ticket->building_id,
                'url' => route('portal.tickets.show', $this->ticket, false),
            ],
        ];
    }
}
