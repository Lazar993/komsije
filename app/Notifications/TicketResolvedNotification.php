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

final class TicketResolvedNotification extends Notification implements ShouldQueue
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
        $message = (new MailMessage())
            ->subject(__('Ticket resolved'))
            ->line(__(':actor marked the ticket ":title" as resolved.', ['actor' => $this->actor->name, 'title' => $this->ticket->title]));

        if ($this->note !== null && $this->note !== '') {
            $message->line($this->note);
        }

        return $message->action(__('Open ticket'), route('portal.tickets.show', $this->ticket));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'actor_id' => $this->actor->getKey(),
            'building_id' => $this->ticket->building_id,
            'message' => __('Ticket resolved: :title', ['title' => $this->ticket->title]),
            'note' => $this->note,
            'ticket_id' => $this->ticket->getKey(),
            'title' => $this->ticket->title,
            'type' => 'ticket_resolved',
        ];
    }

    /**
     * @return array{title: string, body: string, data: array<string, scalar|null>}
     */
    public function toFcm(object $notifiable): array
    {
        return [
            'title' => __('Resolved: :title', ['title' => $this->ticket->title]),
            'body' => __(':actor marked this ticket as resolved.', ['actor' => $this->actor->name]),
            'data' => [
                'type' => 'ticket_resolved',
                'ticket_id' => $this->ticket->getKey(),
                'building_id' => $this->ticket->building_id,
                'url' => route('portal.tickets.show', $this->ticket, false),
            ],
        ];
    }
}
