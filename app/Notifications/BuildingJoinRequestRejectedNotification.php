<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\BuildingJoinRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class BuildingJoinRequestRejectedNotification extends Notification implements ShouldQueue
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

        $mail = (new MailMessage())
            ->subject(__('Update on your resident request'))
            ->greeting(__('Poštovani,'))
            ->line(__('Your request could not be approved.'))
            ->line(__('Please contact your building manager for details.'));

        if (filled($this->joinRequest->rejection_reason)) {
            $mail->line(__('Reason: :reason', ['reason' => $this->joinRequest->rejection_reason]));
        }

        return $mail;
    }
}
