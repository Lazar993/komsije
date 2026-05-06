<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Poll;
use App\Notifications\Concerns\ResolvesChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class PollClosedNotification extends Notification implements ShouldQueue
{
    use Queueable;
    use ResolvesChannels;

    public function __construct(private readonly Poll $poll)
    {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return $this->resolveChannels($notifiable, category: 'announcements');
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject(__('Poll closed'))
            ->line(__('The poll ":title" has closed. View the results.', ['title' => $this->poll->title]))
            ->action(__('View results'), route('portal.dashboard'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'building_id' => $this->poll->building_id,
            'message' => __('Poll closed: :title', ['title' => $this->poll->title]),
            'poll_id' => $this->poll->getKey(),
            'title' => $this->poll->title,
            'type' => 'poll_closed',
        ];
    }

    /**
     * @return array{title: string, body: string, data: array<string, scalar|null>}
     */
    public function toFcm(object $notifiable): array
    {
        return [
            'title' => __('Poll closed'),
            'body' => __(':title — view the results.', ['title' => $this->poll->title]),
            'data' => [
                'type' => 'poll_closed',
                'poll_id' => $this->poll->getKey(),
                'building_id' => $this->poll->building_id,
                'url' => route('portal.dashboard', [], false),
            ],
        ];
    }
}
