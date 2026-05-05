<?php

declare(strict_types=1);

namespace App\Notifications\Concerns;

use App\Models\User;
use App\Notifications\Channels\FcmChannel;

/**
 * Centralised channel-resolution rules for notifications.
 *
 * Strategy (product rule):
 *   - Tenants/users only ever receive email when (a) they are being invited
 *     (handled separately via $emailAlways) or (b) they have explicitly opted
 *     in for the category in their notification preferences.
 *   - Everything else is delivered as push (when a device token exists) plus
 *     an in-app database record.
 *   - We deliberately do NOT fall back to email when push is unavailable:
 *     emails were leaking to tenants for every poll/ticket update where the
 *     PWA hadn't yet registered a token, and several mail templates link to
 *     the Filament admin panel (which 403s for tenants).
 *
 * The 'database' channel is always included so the in-app notification
 * centre keeps a record regardless of delivery channel choice.
 */
trait ResolvesChannels
{
    /**
     * @return array<int, string>
     */
    protected function resolveChannels(
        object $notifiable,
        string $category,
        bool $critical = false,
        bool $emailAlways = false,
    ): array {
        // Email-only events (invites, password reset). We don't add 'database'
        // here because such events are typically sent to non-User notifiables
        // or are security-sensitive.
        if ($emailAlways) {
            return ['mail'];
        }

        $channels = ['database'];

        // Non-User notifiables can't have preferences — push only (we have no
        // way to tell whether emailing this address is appropriate).
        if (! $notifiable instanceof User) {
            $channels[] = FcmChannel::class;

            return $channels;
        }

        if ($notifiable->wantsPush()) {
            $channels[] = FcmChannel::class;
        }

        // Email is opt-in only. wantsEmailFor() returns true when either the
        // master 'notify_email' toggle is on, or the per-category toggle for
        // this event is on. Without an opt-in, we never send mail — even for
        // "critical" events — so tenants stop receiving links to /admin.
        if ($notifiable->wantsEmailFor($category)) {
            $channels[] = 'mail';
        }

        unset($critical);

        return $channels;
    }
}
