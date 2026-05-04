<?php

declare(strict_types=1);

namespace App\Notifications\Concerns;

use App\Models\User;
use App\Notifications\Channels\FcmChannel;

/**
 * Centralised channel-resolution rules for notifications.
 *
 * Strategy (see notification matrix):
 *   - Always-email events (invites, password reset, security): mail only.
 *   - Critical events (e.g. important announcement): push + mail.
 *   - Standard events: push if the user has push + token, mail only as a
 *     fallback (no token) or when the user explicitly opted in for the
 *     category.
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

        // Non-User notifiables can't have preferences — be conservative and
        // send both channels for critical events, push otherwise.
        if (! $notifiable instanceof User) {
            $channels[] = FcmChannel::class;
            if ($critical) {
                $channels[] = 'mail';
            }

            return $channels;
        }

        $pushAvailable = $notifiable->wantsPush();

        if ($critical) {
            // Important: deliver everywhere we reasonably can.
            if ($pushAvailable) {
                $channels[] = FcmChannel::class;
            }
            $channels[] = 'mail';

            return $channels;
        }

        if ($pushAvailable) {
            $channels[] = FcmChannel::class;

            if ($notifiable->wantsEmailFor($category)) {
                $channels[] = 'mail';
            }

            return $channels;
        }

        // No push token (or push disabled) → email is the fallback so the
        // user is never silent.
        $channels[] = 'mail';

        return $channels;
    }
}
