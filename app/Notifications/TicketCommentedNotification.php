<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use App\Notifications\Concerns\ResolvesChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

final class TicketCommentedNotification extends Notification implements ShouldQueue
{
    use Queueable;
    use ResolvesChannels;

    public function __construct(
        private readonly Ticket $ticket,
        private readonly TicketComment $comment,
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
            ->subject(__('New comment on your ticket'))
            ->line(__(':actor commented on ":title".', ['actor' => $this->actor->name, 'title' => $this->ticket->title]))
            ->line($this->preview())
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
            'comment_id' => $this->comment->getKey(),
            'message' => __(':actor commented on this ticket.', ['actor' => $this->actor->name]),
            'preview' => $this->preview(),
            'ticket_id' => $this->ticket->getKey(),
            'title' => $this->ticket->title,
            'type' => 'ticket_commented',
        ];
    }

    /**
     * @return array{title: string, body: string, data: array<string, scalar|null>}
     */
    public function toFcm(object $notifiable): array
    {
        return [
            'title' => __(':actor on :title', ['actor' => $this->actor->name, 'title' => $this->ticket->title]),
            'body' => $this->preview(),
            'data' => [
                'type' => 'ticket_commented',
                'ticket_id' => $this->ticket->getKey(),
                'comment_id' => $this->comment->getKey(),
                'building_id' => $this->ticket->building_id,
                'url' => route('portal.tickets.show', $this->ticket, false),
            ],
        ];
    }

    private function preview(): string
    {
        return Str::limit((string) $this->comment->body, 140);
    }
}
