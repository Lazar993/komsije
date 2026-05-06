<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Announcement;
use App\Notifications\Concerns\ResolvesChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class AnnouncementImportantUpdatedNotification extends Notification implements ShouldQueue
{
    use Queueable;
    use ResolvesChannels;

    public function __construct(private readonly Announcement $announcement)
    {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return $this->resolveChannels(
            $notifiable,
            category: 'announcements',
            critical: (bool) $this->announcement->is_important,
        );
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject(__('Important announcement updated'))
            ->line(__('The important announcement ":title" was updated in :building.', [
                'title' => $this->announcement->title,
                'building' => $this->announcement->building->name,
            ]))
            ->action(__('Read announcement'), route('portal.announcements.show', $this->announcement));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'announcement_id' => $this->announcement->getKey(),
            'building_id' => $this->announcement->building_id,
            'message' => __('An important announcement was updated.'),
            'title' => $this->announcement->title,
            'type' => 'announcement_important_updated',
        ];
    }

    /**
     * @return array{title: string, body: string, data: array<string, scalar|null>}
     */
    public function toFcm(object $notifiable): array
    {
        return [
            'title' => __('Updated: :title', ['title' => $this->announcement->title]),
            'body' => __('The important announcement was updated in :building.', ['building' => $this->announcement->building->name]),
            'data' => [
                'type' => 'announcement_important_updated',
                'announcement_id' => $this->announcement->getKey(),
                'building_id' => $this->announcement->building_id,
                'url' => route('portal.announcements.show', $this->announcement, false),
            ],
        ];
    }
}
