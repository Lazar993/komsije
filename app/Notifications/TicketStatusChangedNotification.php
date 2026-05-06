<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\Concerns\ResolvesChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class TicketStatusChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;
    use ResolvesChannels;

    public function __construct(
        private readonly Ticket $ticket,
        private readonly User $actor,
        private readonly TicketStatus $fromStatus,
        private readonly TicketStatus $toStatus,
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
        $line = __(':actor changed status of ":title" from :from to :to.', [
            'actor' => $this->actor->name,
            'title' => $this->ticket->title,
            'from' => $this->fromStatus->label(),
            'to' => $this->toStatus->label(),
        ]);

        $message = (new MailMessage())
            ->subject(__('Ticket status changed'))
            ->line($line);

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
            'from_status' => $this->fromStatus->value,
            'to_status' => $this->toStatus->value,
            'message' => __('Status changed: :from → :to', [
                'from' => $this->fromStatus->label(),
                'to' => $this->toStatus->label(),
            ]),
            'note' => $this->note,
            'ticket_id' => $this->ticket->getKey(),
            'title' => $this->ticket->title,
            'type' => 'ticket_status_changed',
        ];
    }

    /**
     * @return array{title: string, body: string, data: array<string, scalar|null>}
     */
    public function toFcm(object $notifiable): array
    {
        return [
            'title' => $this->ticket->title,
            'body' => __('Status: :from → :to', [
                'from' => $this->fromStatus->label(),
                'to' => $this->toStatus->label(),
            ]),
            'data' => [
                'type' => 'ticket_status_changed',
                'ticket_id' => $this->ticket->getKey(),
                'building_id' => $this->ticket->building_id,
                'url' => route('portal.tickets.show', $this->ticket, false),
            ],
        ];
    }
}
