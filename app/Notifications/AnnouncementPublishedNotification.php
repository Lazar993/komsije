<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Filament\Resources\Announcements\AnnouncementResource;
use App\Models\Announcement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class AnnouncementPublishedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Announcement $announcement)
    {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject(__('New building announcement'))
            ->line(__('A new announcement was published in :building.', ['building' => $this->announcement->building->name]))
            ->line($this->announcement->title)
            ->action(__('Read announcement'), AnnouncementResource::getUrl('view', ['record' => $this->announcement]));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'announcement_id' => $this->announcement->getKey(),
            'building_id' => $this->announcement->building_id,
            'message' => __('A new building announcement is available.'),
            'title' => $this->announcement->title,
            'type' => 'announcement_published',
        ];
    }
}