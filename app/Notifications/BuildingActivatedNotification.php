<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Filament\Resources\Buildings\BuildingResource;
use App\Models\Building;
use App\Notifications\Concerns\ResolvesChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class BuildingActivatedNotification extends Notification implements ShouldQueue
{
    use Queueable;
    use ResolvesChannels;

    public function __construct(private readonly Building $building)
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
            ->success()
            ->subject(__('Komšije is now active for :building', ['building' => $this->building->name]))
            ->line(__('Your subscription is active. Thank you for choosing Komšije!'))
            ->action(__('Open building'), BuildingResource::getUrl('view', ['record' => $this->building->getKey()], panel: 'admin'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'building_activated',
            'building_id' => $this->building->getKey(),
            'title' => __('Subscription active'),
            'message' => __('Your subscription for :building is now active.', ['building' => $this->building->name]),
        ];
    }

    /**
     * @return array{title: string, body: string, data: array<string, scalar|null>}
     */
    public function toFcm(object $notifiable): array
    {
        return [
            'title' => __('Subscription active'),
            'body' => __(':building is now fully active.', ['building' => $this->building->name]),
            'data' => [
                'type' => 'building_activated',
                'building_id' => $this->building->getKey(),
                'url' => BuildingResource::getUrl('view', ['record' => $this->building->getKey()], isAbsolute: false, panel: 'admin'),
            ],
        ];
    }
}
