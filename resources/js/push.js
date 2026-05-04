// Push notification bootstrap for Komšije.
// - Lazy-loads the Firebase JS SDK only when the user is ready to opt in
// - Asks for Notification permission at the right moment (after login or first action)
// - Registers the FCM token with the Laravel backend
// Falls back gracefully when push is unsupported.

const TOKEN_CACHE_KEY = 'komsije-fcm-token';
const PROMPT_DEFER_KEY = 'komsije-push-prompt-deferred-until';
const PROMPT_DECLINED_KEY = 'komsije-push-prompt-declined';
const USER_DISABLED_KEY = 'komsije-push-user-disabled';
const FIREBASE_SDK_VERSION = '10.13.2';
const STATUS_EVENT = 'komsije:push-status';

// Possible status values broadcast on document via STATUS_EVENT and returned by getPushStatus():
//   'unsupported'   – browser has no Notification/PushManager/SW APIs (or insecure context)
//   'no-config'     – Firebase config meta tag missing (server-side disabled)
//   'needs-install' – iOS Safari tab; push only works after "Add to Home Screen"
//   'denied'        – user explicitly blocked notifications in this browser
//   'default'       – never asked yet, can be enabled
//   'granted'       – enabled, token synced with backend

let firebaseAppPromise = null;

export function initPushNotifications() {
    if (!isPushSupported()) {
        emitStatus('unsupported');
        return;
    }

    // Self-heal: remove any legacy FCM service worker registered at root scope.
    // Older versions registered firebase-messaging-sw.js with no scope, which
    // caused it to compete with the main app shell SW and trigger a reload loop.
    void cleanupLegacyFcmRegistration();

    const config = readFirebaseConfig();

    if (!config) {
        emitStatus('no-config');
        return;
    }

    // iOS Safari outside a home-screen PWA cannot deliver web push at all and
    // returns 'denied' from requestPermission() without showing a UI. Treat that
    // as a separate 'needs-install' state so we never persist a false decline.
    if (isIosNonStandalone()) {
        emitStatus('needs-install');
        return;
    }

    // Already granted in the browser. The user might have explicitly turned
    // notifications off from our UI on this device — in which case we keep the
    // token unregistered and surface 'default' so they can opt back in.
    if (Notification.permission === 'granted') {
        if (isUserDisabled()) {
            emitStatus('default');
            return;
        }
        void enablePush(config).catch(() => { });
        return;
    }

    if (Notification.permission === 'denied') {
        emitStatus('denied');
        return;
    }

    emitStatus('default');
    schedulePermissionPrompt(config);
}

export function getPushStatus() {
    if (!isPushSupported()) return 'unsupported';
    if (!readFirebaseConfig()) return 'no-config';
    if (isIosNonStandalone()) return 'needs-install';
    if (Notification.permission === 'denied') return 'denied';
    if (Notification.permission === 'granted' && !isUserDisabled()) return 'granted';
    return 'default';
}

function isUserDisabled() {
    try {
        return window.localStorage.getItem(USER_DISABLED_KEY) === 'true';
    } catch {
        return false;
    }
}

export async function disablePush() {
    const cachedToken = window.localStorage.getItem(TOKEN_CACHE_KEY);

    if (cachedToken) {
        try {
            await deleteTokenOnBackend(cachedToken);
        } catch (error) {
            console.warn('Push token unregistration failed', error);
        }
    }

    window.localStorage.removeItem(TOKEN_CACHE_KEY);
    window.localStorage.setItem(PROMPT_DECLINED_KEY, 'true');
    window.localStorage.setItem(USER_DISABLED_KEY, 'true');

    try {
        const registrations = await navigator.serviceWorker.getRegistrations();
        for (const reg of registrations) {
            const url = reg.active?.scriptURL || reg.installing?.scriptURL || reg.waiting?.scriptURL || '';
            if (url.endsWith('/firebase-messaging-sw.js')) {
                await reg.unregister();
            }
        }
    } catch (error) {
        console.warn('FCM SW cleanup on disable failed', error);
    }

    emitStatus(getPushStatus());
}

function emitStatus(status) {
    try {
        document.dispatchEvent(new CustomEvent(STATUS_EVENT, { detail: { status } }));
    } catch {
        // CustomEvent unavailable (very old browsers) — safe to ignore.
    }
}

function isStandalone() {
    return (
        window.matchMedia?.('(display-mode: standalone)')?.matches === true ||
        window.navigator.standalone === true
    );
}

function isIos() {
    return /iphone|ipad|ipod/i.test(window.navigator.userAgent);
}

function isIosNonStandalone() {
    return isIos() && !isStandalone();
}

function isPushSupported() {
    return (
        typeof window !== 'undefined' &&
        'serviceWorker' in navigator &&
        'PushManager' in window &&
        'Notification' in window &&
        window.isSecureContext
    );
}

function readFirebaseConfig() {
    const meta = document.querySelector('meta[name="firebase-config"]');
    const raw = meta?.getAttribute('content');

    if (!raw) {
        return null;
    }

    try {
        const config = JSON.parse(raw);
        if (!config.apiKey || !config.projectId || !config.appId || !config.vapidKey) {
            return null;
        }
        return config;
    } catch {
        return null;
    }
}

function schedulePermissionPrompt(config) {
    const deferUntil = Number(window.localStorage.getItem(PROMPT_DEFER_KEY) || 0);
    const declined = window.localStorage.getItem(PROMPT_DECLINED_KEY) === 'true';

    if (declined || (deferUntil && Date.now() < deferUntil)) {
        return;
    }

    // Trigger after first meaningful user interaction (a click on a button/link).
    const handler = async () => {
        document.removeEventListener('click', handler, true);

        try {
            await enablePush(config);
        } catch (error) {
            console.warn('Push enablement failed', error);
        }
    };

    // Defer registration until the user has actually interacted with the page.
    document.addEventListener('click', handler, { capture: true, once: false, passive: true });
}

export async function enablePush(configOverride = null) {
    const config = configOverride || readFirebaseConfig();

    if (!config || !isPushSupported()) {
        emitStatus(getPushStatus());
        return null;
    }

    // On iOS Safari outside a PWA, requestPermission() resolves to 'denied'
    // immediately without any UI. Refuse to ask, so we don't poison localStorage.
    if (isIosNonStandalone()) {
        emitStatus('needs-install');
        return null;
    }

    // User explicitly opted back in — clear any prior soft-declines/back-offs.
    window.localStorage.removeItem(PROMPT_DECLINED_KEY);
    window.localStorage.removeItem(PROMPT_DEFER_KEY);
    window.localStorage.removeItem(USER_DISABLED_KEY);

    // Skip requestPermission() when permission was already granted. Calling it
    // outside a user gesture (e.g. from the silent re-sync on page load) can
    // resolve to 'denied' on iOS Safari standalone, which would then poison
    // localStorage and surface a false "blocked" state on every revisit.
    let permission = Notification.permission;

    if (permission !== 'granted') {
        permission = await Notification.requestPermission();
    }

    if (permission !== 'granted') {
        if (permission === 'denied') {
            window.localStorage.setItem(PROMPT_DECLINED_KEY, 'true');
            emitStatus('denied');
        } else {
            // "default" = user dismissed; back off for 24h.
            window.localStorage.setItem(PROMPT_DEFER_KEY, String(Date.now() + 24 * 60 * 60 * 1000));
            emitStatus('default');
        }
        return null;
    }

    const { messaging, getToken, onMessage } = await loadFirebase(config);

    // Register the FCM SW under a dedicated scope so it can never conflict
    // with the main app shell service worker at the root scope.
    const swRegistration = await navigator.serviceWorker.register('/firebase-messaging-sw.js', {
        scope: '/firebase-cloud-messaging-push-scope/',
    });

    const token = await getToken(messaging, {
        vapidKey: config.vapidKey,
        serviceWorkerRegistration: swRegistration,
    });

    if (!token) {
        return null;
    }

    onMessage(messaging, (payload) => {
        const data = payload.data || {};
        const notification = payload.notification || {};
        const title = notification.title || data.title || 'Komšije';
        const body = notification.body || data.body || '';

        // Foreground display via the SW registration so we share the same
        // tag-based collapsing as background notifications. Using a raw
        // `new Notification()` here would bypass the tag and produce a second
        // visible notification on top of the SW-rendered one.
        const tag = data.type ? `${data.type}-${data.ticket_id || data.announcement_id || ''}` : undefined;

        const reg = swRegistration || navigator.serviceWorker.controller?.registration;

        if (reg && typeof reg.showNotification === 'function') {
            reg.showNotification(title, {
                body,
                icon: '/icons/icon-192-v5.png',
                badge: '/icons/favicon-32-v5.png',
                tag,
                renotify: false,
                data: { url: data.url || '/', ...data },
            });
        }
    });

    if (window.localStorage.getItem(TOKEN_CACHE_KEY) !== token) {
        await syncTokenWithBackend(token);
        window.localStorage.setItem(TOKEN_CACHE_KEY, token);
    }

    window.localStorage.removeItem(PROMPT_DEFER_KEY);
    window.localStorage.removeItem(PROMPT_DECLINED_KEY);
    window.localStorage.removeItem(USER_DISABLED_KEY);

    emitStatus('granted');
    return token;
}

async function deleteTokenOnBackend(token) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    const response = await fetch('/device-tokens', {
        method: 'DELETE',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken,
        },
        body: JSON.stringify({ token }),
    });

    if (!response.ok) {
        throw new Error(`Token unregistration failed (${response.status})`);
    }
}

async function syncTokenWithBackend(token) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    const response = await fetch('/device-tokens', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken,
        },
        body: JSON.stringify({
            token,
            device_type: detectDeviceType(),
        }),
    });

    if (!response.ok) {
        throw new Error(`Token registration failed (${response.status})`);
    }
}

function detectDeviceType() {
    const ua = navigator.userAgent;

    if (/iphone|ipad|ipod/i.test(ua)) return 'ios';
    if (/android/i.test(ua)) return 'android';
    return 'web';
}

async function cleanupLegacyFcmRegistration() {
    try {
        const registrations = await navigator.serviceWorker.getRegistrations();

        for (const registration of registrations) {
            const scriptUrl = registration.active?.scriptURL
                || registration.installing?.scriptURL
                || registration.waiting?.scriptURL
                || '';

            if (!scriptUrl.endsWith('/firebase-messaging-sw.js')) {
                continue;
            }

            // The new FCM SW lives under /firebase-cloud-messaging-push-scope/.
            // Anything else is the legacy root-scoped registration that fights
            // with the main app SW; unregister it.
            if (registration.scope.endsWith('/firebase-cloud-messaging-push-scope/')) {
                continue;
            }

            await registration.unregister();
        }
    } catch (error) {
        console.warn('Legacy FCM SW cleanup failed', error);
    }
}

async function loadFirebase(config) {
    if (!firebaseAppPromise) {
        firebaseAppPromise = (async () => {
            const [{ initializeApp }, messagingModule] = await Promise.all([
                import(/* @vite-ignore */ `https://www.gstatic.com/firebasejs/${FIREBASE_SDK_VERSION}/firebase-app.js`),
                import(/* @vite-ignore */ `https://www.gstatic.com/firebasejs/${FIREBASE_SDK_VERSION}/firebase-messaging.js`),
            ]);

            const app = initializeApp({
                apiKey: config.apiKey,
                authDomain: config.authDomain,
                projectId: config.projectId,
                messagingSenderId: config.messagingSenderId,
                appId: config.appId,
            });

            return {
                messaging: messagingModule.getMessaging(app),
                getToken: messagingModule.getToken,
                onMessage: messagingModule.onMessage,
            };
        })();
    }

    return firebaseAppPromise;
}
