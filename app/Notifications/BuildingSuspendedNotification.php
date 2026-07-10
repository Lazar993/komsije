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

final class BuildingSuspendedNotification extends Notification implements ShouldQueue
{
    use Queueable;
    use ResolvesChannels;

    public function __construct(
        private readonly Building $building,
        private readonly string $reason = 'manual',
    ) {
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
            ->error()
            ->subject(__('Komšije access for :building has been suspended', ['building' => $this->building->name]))
            ->line($this->body())
            ->line(__('You can still sign in and review your history, but new activity is paused.'))
            ->action(__('Manage building'), BuildingResource::getUrl('view', ['record' => $this->building->getKey()], panel: 'admin'))
            ->line(__('Please contact T&B Solutions to continue using Komšije.'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'building_suspended',
            'building_id' => $this->building->getKey(),
            'title' => __('Building suspended'),
            'message' => $this->body(),
        ];
    }

    /**
     * @return array{title: string, body: string, data: array<string, scalar|null>}
     */
    public function toFcm(object $notifiable): array
    {
        return [
            'title' => __('Building suspended'),
            'body' => $this->body(),
            'data' => [
                'type' => 'building_suspended',
                'building_id' => $this->building->getKey(),
                'url' => BuildingResource::getUrl('view', ['record' => $this->building->getKey()], isAbsolute: false, panel: 'admin'),
            ],
        ];
    }

    private function body(): string
    {
        return $this->reason === 'trial_expired'
            ? __('The trial period for :building has ended and access is now read-only.', ['building' => $this->building->name])
            : __('Access to :building is now read-only.', ['building' => $this->building->name]);
    }
}
