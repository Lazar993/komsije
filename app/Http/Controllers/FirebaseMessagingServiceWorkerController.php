<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Response;

final class FirebaseMessagingServiceWorkerController extends Controller
{
    public function __invoke(): Response
    {
        $web = (array) config('services.fcm.web', []);

        $config = [
            'apiKey' => $web['api_key'] ?? null,
            'authDomain' => $web['auth_domain'] ?? null,
            'projectId' => $web['project_id'] ?? null,
            'messagingSenderId' => $web['messaging_sender_id'] ?? null,
            'appId' => $web['app_id'] ?? null,
        ];

        // Only emit a working SW if FCM is configured; otherwise return a no-op
        // worker so navigator.serviceWorker.register() still resolves cleanly.
        if (empty($config['apiKey']) || empty($config['projectId']) || empty($config['appId'])) {
            $body = "// FCM is not configured; this service worker is a no-op.\n"
                . "self.addEventListener('install', () => self.skipWaiting());\n";

            return response($body, 200, [
                'Content-Type' => 'application/javascript; charset=UTF-8',
                'Service-Worker-Allowed' => '/',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
            ]);
        }

        $configJson = json_encode($config, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);

        $body = <<<JS
            /* Firebase Cloud Messaging service worker (templated by Laravel). */
            importScripts('https://www.gstatic.com/firebasejs/10.13.2/firebase-app-compat.js');
            importScripts('https://www.gstatic.com/firebasejs/10.13.2/firebase-messaging-compat.js');

            self.addEventListener('install', () => self.skipWaiting());
            // Intentionally NOT calling clients.claim() — this SW must not steal
            // control of the page from the main service-worker.js (it would
            // trigger controllerchange and a reload loop).

            firebase.initializeApp({$configJson});
            const messaging = firebase.messaging();

            messaging.onBackgroundMessage((payload) => {
                const notification = payload.notification || {};
                const data = payload.data || {};
                const title = notification.title || data.title || 'Komšije';

                self.registration.showNotification(title, {
                    body: notification.body || data.body || '',
                    icon: '/icons/icon-192-v4.png',
                    badge: '/icons/favicon-32-v4.png',
                    tag: data.type ? `\${data.type}-\${data.ticket_id || data.announcement_id || ''}` : undefined,
                    data: {
                        url: data.url || data.click_action || '/',
                        ...data,
                    },
                    renotify: true,
                });
            });

            self.addEventListener('notificationclick', (event) => {
                event.notification.close();
                const url = event.notification.data?.url || '/';

                event.waitUntil(
                    self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clients) => {
                        for (const client of clients) {
                            if ('focus' in client) {
                                client.navigate(url).catch(() => {});
                                return client.focus();
                            }
                        }
                        return self.clients.openWindow(url);
                    })
                );
            });
            JS;

        return response($body, 200, [
            'Content-Type' => 'application/javascript; charset=UTF-8',
            'Service-Worker-Allowed' => '/',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }
}
