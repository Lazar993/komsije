<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Invite;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class TenantInviteNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Invite $invite)
    {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject(__('Poziv za pristup zgradi :building', ['building' => $this->invite->building->name]))
            ->greeting(__('Zdravo,'))
            ->line(__('Pozvani ste da se pridružite zgradi :building preko aplikacije Komšije.', ['building' => $this->invite->building->name]))
            ->line(__('Stan: :apartment', ['apartment' => $this->invite->apartment?->number ?? __('N/A')]))
            ->line(__('Ovaj link važi do :date i može se iskoristiti samo jednom.', ['date' => $this->invite->expires_at?->format('d.m.Y H:i')]))
            ->action(__('Prihvati poziv'), route('invite.show', $this->invite->token))
            ->line(__('Ako ne očekujete ovaj poziv, slobodno ignorišite ovu poruku.'));
    }
}