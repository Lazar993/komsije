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

final class TrialReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;
    use ResolvesChannels;

    public function __construct(
        private readonly Building $building,
        private readonly int $daysRemaining,
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
        $mail = (new MailMessage())
            ->subject($this->subject())
            ->greeting(__('Hello :name', ['name' => $notifiable->name ?? '']))
            ->line($this->body())
            ->action(__('Manage building'), $this->adminUrl(true))
            ->line(__('Contact T&B Solutions to activate your subscription and keep Komšije running.'));

        return $this->daysRemaining <= 0 ? $mail->error() : $mail;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'building_trial_reminder',
            'building_id' => $this->building->getKey(),
            'days_remaining' => $this->daysRemaining,
            'title' => $this->subject(),
            'message' => $this->body(),
        ];
    }

    /**
     * @return array{title: string, body: string, data: array<string, scalar|null>}
     */
    public function toFcm(object $notifiable): array
    {
        return [
            'title' => $this->subject(),
            'body' => $this->body(),
            'data' => [
                'type' => 'building_trial_reminder',
                'building_id' => $this->building->getKey(),
                'days_remaining' => $this->daysRemaining,
                'url' => $this->adminUrl(false),
            ],
        ];
    }

    private function subject(): string
    {
        return $this->daysRemaining <= 0
            ? __('Your Komšije trial ends today')
            : __('Your Komšije trial ends in :days days', ['days' => $this->daysRemaining]);
    }

    private function body(): string
    {
        return $this->daysRemaining <= 0
            ? __('The trial for :building ends today. Activate your subscription to avoid interruption.', ['building' => $this->building->name])
            : __('The trial for :building ends in :days days.', ['building' => $this->building->name, 'days' => $this->daysRemaining]);
    }

    private function adminUrl(bool $absolute): string
    {
        return BuildingResource::getUrl('view', ['record' => $this->building->getKey()], isAbsolute: $absolute, panel: 'admin');
    }
}
