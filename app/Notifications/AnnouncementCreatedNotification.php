<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Filament\Resources\Announcements\AnnouncementResource;
use App\Models\Announcement;
use App\Notifications\Concerns\ResolvesChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class AnnouncementCreatedNotification extends Notification implements ShouldQueue
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
        return $this->resolveChannels($notifiable, category: 'announcements');
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject(__('Announcement pending approval'))
            ->line(__('A resident submitted a new announcement in :building.', ['building' => $this->announcement->building->name]))
            ->line($this->announcement->title)
            ->action(__('Review announcement'), AnnouncementResource::getUrl('view', ['record' => $this->announcement]));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'announcement_id' => $this->announcement->getKey(),
            'building_id' => $this->announcement->building_id,
            'message' => __('A new announcement is waiting for approval.'),
            'title' => $this->announcement->title,
            'type' => 'announcement_created',
        ];
    }

    /**
     * @return array{title: string, body: string, data: array<string, scalar|null>}
     */
    public function toFcm(object $notifiable): array
    {
        return [
            'title' => $this->announcement->title,
            'body' => __('Announcement pending approval in :building', ['building' => $this->announcement->building->name]),
            'data' => [
                'type' => 'announcement_created',
                'announcement_id' => $this->announcement->getKey(),
                'building_id' => $this->announcement->building_id,
                'url' => route('portal.announcements.show', $this->announcement, false),
            ],
        ];
    }
}