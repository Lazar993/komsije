const VERSION = 'komsije-v11';
const STATIC_CACHE = `${VERSION}-static`;
const DYNAMIC_CACHE = `${VERSION}-dynamic`;
const API_CACHE = `${VERSION}-api`;
const OFFLINE_URL = '/offline.html';
const APP_SHELL = [
    '/manifest.json?v=6',
    '/offline.html',
    '/icons/favicon-32-v5.png',
    '/icons/icon-192-v5.png',
    '/icons/icon-512-v5.png',
    '/icons/apple-touch-icon-v5.png',
    '/icons/apple-touch-icon-167-v5.png',
    '/icons/apple-touch-icon-152-v5.png',
    '/icons/notification-badge-96.png',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(STATIC_CACHE).then((cache) => cache.addAll(APP_SHELL)).then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches
            .keys()
            .then((keys) =>
                Promise.all(
                    keys
                        .filter((key) => ![STATIC_CACHE, DYNAMIC_CACHE, API_CACHE].includes(key))
                        .map((key) => caches.delete(key))
                )
            )
            .then(() => self.clients.claim())
    );
});

self.addEventListener('message', (event) => {
    if (event.data?.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});

self.addEventListener('push', (event) => {
    let payload = {};

    if (event.data) {
        try {
            payload = event.data.json();
        } catch {
            payload = { notification: { title: 'Komšije', body: event.data.text() } };
        }
    }

    const notification = payload.notification || {};
    const data = payload.data || {};
    const title = notification.title || data.title || 'Komšije';
    const body = notification.body || data.body || '';

    const options = {
        body,
        icon: notification.icon || '/icons/icon-192-v5.png',
        badge: notification.badge || '/icons/notification-badge-96.png',
        tag: data.type ? `${data.type}-${data.ticket_id || data.announcement_id || ''}` : undefined,
        data: {
            url: data.url || data.click_action || '/',
            ...data,
        },
        renotify: false,
        // Android-only options (ignored on iOS/desktop): subtle vibration
        // pattern, language hint, and an explicit timestamp so notifications
        // sort correctly in the system shade.
        vibrate: [120, 60, 120],
        lang: 'sr-Latn',
        timestamp: Date.now(),
    };

    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const launchUrl = event.notification.data?.url || '/';
    const targetUrl = event.notification.data?.target_url || launchUrl;

    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
            const wantedUrl = new URL(targetUrl, self.location.origin);

            for (const client of clientList) {
                const clientUrl = new URL(client.url);

                if (clientUrl.origin === wantedUrl.origin && 'focus' in client) {
                    client.navigate(wantedUrl.toString()).catch(() => { });
                    return client.focus();
                }
            }

            return self.clients.openWindow(launchUrl);
        })
    );
});

self.addEventListener('fetch', (event) => {
    const { request } = event;

    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);
    const sameOrigin = url.origin === self.location.origin;
    const isNavigation = request.mode === 'navigate';
    const isApiRequest = sameOrigin && url.pathname.startsWith('/api/');
    const isStaticAsset =
        sameOrigin &&
        (
            url.pathname.startsWith('/build/') ||
            url.pathname.startsWith('/fonts/') ||
            url.pathname.startsWith('/icons/') ||
            /\.(?:css|js|mjs|woff2?|ttf|eot|png|jpg|jpeg|svg|webp|ico)$/i.test(url.pathname)
        );

    if (isNavigation) {
        event.respondWith(handleNavigationRequest(request));
        return;
    }

    if (isApiRequest) {
        event.respondWith(networkFirst(request, API_CACHE));
        return;
    }

    if (isStaticAsset) {
        event.respondWith(cacheFirst(request, STATIC_CACHE));
    }
});

async function handleNavigationRequest(request) {
    try {
        const response = await fetch(request);
        const cache = await caches.open(DYNAMIC_CACHE);
        cache.put(request, response.clone());
        return response;
    } catch {
        const cachedResponse = await caches.match(request);

        if (cachedResponse) {
            return cachedResponse;
        }

        return caches.match(OFFLINE_URL);
    }
}

async function cacheFirst(request, cacheName) {
    const cachedResponse = await caches.match(request);

    if (cachedResponse) {
        return cachedResponse;
    }

    const response = await fetch(request);
    const cache = await caches.open(cacheName);
    cache.put(request, response.clone());
    return response;
}

async function networkFirst(request, cacheName) {
    try {
        const response = await fetch(request);
        const cache = await caches.open(cacheName);
        cache.put(request, response.clone());
        return response;
    } catch {
        const cachedResponse = await caches.match(request);

        if (cachedResponse) {
            return cachedResponse;
        }

        return new Response(JSON.stringify({ message: 'Offline' }), {
            status: 503,
            headers: {
                'Content-Type': 'application/json',
                'X-Service-Worker': 'offline-fallback',
            },
        });
    }
}