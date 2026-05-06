<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Poll;
use App\Notifications\Concerns\ResolvesChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class PollEndingSoonNotification extends Notification implements ShouldQueue
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
            ->subject(__('Poll ending soon'))
            ->line(__('The poll ":title" closes on :ends.', [
                'title' => $this->poll->title,
                'ends' => $this->poll->ends_at?->format('Y-m-d H:i') ?? '',
            ]))
            ->line(__('You haven\'t voted yet — make your voice heard.'))
            ->action(__('Vote now'), route('portal.dashboard'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'building_id' => $this->poll->building_id,
            'ends_at' => $this->poll->ends_at?->toIso8601String(),
            'message' => __('A poll is closing soon and you haven\'t voted yet.'),
            'poll_id' => $this->poll->getKey(),
            'title' => $this->poll->title,
            'type' => 'poll_ending_soon',
        ];
    }

    /**
     * @return array{title: string, body: string, data: array<string, scalar|null>}
     */
    public function toFcm(object $notifiable): array
    {
        return [
            'title' => __('Poll ending soon'),
            'body' => __(':title — vote before it closes.', ['title' => $this->poll->title]),
            'data' => [
                'type' => 'poll_ending_soon',
                'poll_id' => $this->poll->getKey(),
                'building_id' => $this->poll->building_id,
                'url' => route('portal.dashboard', [], false),
            ],
        ];
    }
}
