<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\BuildingJoinRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class PendingResidentJoinRequestReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly BuildingJoinRequest $joinRequest)
    {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        unset($notifiable);

        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        unset($notifiable);

        return (new MailMessage())
            ->subject(__('Reminder: pending resident request'))
            ->line(__('A resident join request has been waiting for review for more than 24 hours.'))
            ->line(__('Building: :building', ['building' => $this->joinRequest->building?->name ?? '-']))
            ->line(__('Apartment: :apartment', ['apartment' => $this->joinRequest->apartment_number]))
            ->line(__('Resident: :name', ['name' => $this->joinRequest->fullName()]))
            ->action(
                __('Review request'),
                route('filament.admin.resources.building-join-requests.view', ['record' => $this->joinRequest]),
            );
    }
}
