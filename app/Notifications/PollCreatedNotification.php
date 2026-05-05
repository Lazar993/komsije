<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Poll;
use App\Notifications\Concerns\ResolvesChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class PollCreatedNotification extends Notification implements ShouldQueue
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
            ->subject(__('New poll in your building'))
            ->line(__('A new poll is available in :building.', ['building' => $this->poll->building->name]))
            ->line($this->poll->title)
            ->action(__('Vote now'), route('portal.dashboard'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'poll_id' => $this->poll->getKey(),
            'building_id' => $this->poll->building_id,
            'message' => __('A new poll is open for voting.'),
            'title' => $this->poll->title,
            'type' => 'poll_created',
        ];
    }

    /**
     * @return array{title: string, body: string, data: array<string, scalar|null>}
     */
    public function toFcm(object $notifiable): array
    {
        return [
            'title' => $this->poll->title,
            'body' => __('New poll in :building', ['building' => $this->poll->building->name]),
            'data' => [
                'type' => 'poll_created',
                'poll_id' => $this->poll->getKey(),
                'building_id' => $this->poll->building_id,
                'url' => route('portal.dashboard', [], false),
            ],
        ];
    }
}
