<?php

declare(strict_types=1);

namespace App\Notifications\Channels;

use App\Models\DeviceToken;
use App\Support\NotificationLaunchUrl;
use App\Services\PushNotifications\FcmService;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

final class FcmChannel
{
    public function __construct(private readonly FcmService $fcm)
    {
    }

    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toFcm')) {
            return;
        }

        $tokens = method_exists($notifiable, 'routeNotificationFor')
            ? (array) $notifiable->routeNotificationFor('fcm', $notification)
            : [];

        $tokens = array_values(array_filter($tokens));

        if ($tokens === []) {
            return;
        }

        /** @var array{title: string, body: string, data?: array<string, scalar|null>} $payload */
        $payload = $notification->toFcm($notifiable);

        $title = (string) ($payload['title'] ?? config('app.name'));
        $body = (string) ($payload['body'] ?? '');
        $data = NotificationLaunchUrl::wrap((array) ($payload['data'] ?? []));

        if ($body === '') {
            return;
        }

        try {
            $invalid = $this->fcm->send($title, $body, $tokens, $data);
        } catch (\Throwable $e) {
            Log::error('FCM channel send failed', ['exception' => $e->getMessage()]);

            return;
        }

        if ($invalid !== []) {
            DeviceToken::query()->whereIn('token', $invalid)->delete();
        }
    }
}
