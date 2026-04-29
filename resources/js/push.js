// Push notification bootstrap for Komšije.
// - Lazy-loads the Firebase JS SDK only when the user is ready to opt in
// - Asks for Notification permission at the right moment (after login or first action)
// - Registers the FCM token with the Laravel backend
// Falls back gracefully when push is unsupported.

const TOKEN_CACHE_KEY = 'komsije-fcm-token';
const PROMPT_DEFER_KEY = 'komsije-push-prompt-deferred-until';
const PROMPT_DECLINED_KEY = 'komsije-push-prompt-declined';
const FIREBASE_SDK_VERSION = '10.13.2';

let firebaseAppPromise = null;

export function initPushNotifications() {
    if (!isPushSupported()) {
        return;
    }

    const config = readFirebaseConfig();

    if (!config) {
        return;
    }

    // Already granted: silently sync the token (refresh-safe).
    if (Notification.permission === 'granted') {
        void enablePush(config).catch(() => { });
        return;
    }

    if (Notification.permission === 'denied') {
        return;
    }

    schedulePermissionPrompt(config);
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
        return null;
    }

    const permission = await Notification.requestPermission();

    if (permission !== 'granted') {
        if (permission === 'denied') {
            window.localStorage.setItem(PROMPT_DECLINED_KEY, 'true');
        } else {
            // "default" = user dismissed; back off for 24h.
            window.localStorage.setItem(PROMPT_DEFER_KEY, String(Date.now() + 24 * 60 * 60 * 1000));
        }
        return null;
    }

    const { messaging, getToken, onMessage } = await loadFirebase(config);
    const swRegistration = await navigator.serviceWorker.register('/firebase-messaging-sw.js');

    // Forward the public web config to the FCM SW so it can handle background pushes.
    swRegistration.active?.postMessage({ type: 'FIREBASE_CONFIG', config });
    navigator.serviceWorker.controller?.postMessage({ type: 'FIREBASE_CONFIG', config });

    const token = await getToken(messaging, {
        vapidKey: config.vapidKey,
        serviceWorkerRegistration: swRegistration,
    });

    if (!token) {
        return null;
    }

    onMessage(messaging, (payload) => {
        const notification = payload.notification || {};
        const data = payload.data || {};

        // Foreground: show as native notification too (tab is open).
        if (Notification.permission === 'granted') {
            new Notification(notification.title || data.title || 'Komšije', {
                body: notification.body || data.body || '',
                icon: '/icons/icon-192-v4.png',
                badge: '/icons/favicon-32-v4.png',
                data,
            });
        }
    });

    if (window.localStorage.getItem(TOKEN_CACHE_KEY) !== token) {
        await syncTokenWithBackend(token);
        window.localStorage.setItem(TOKEN_CACHE_KEY, token);
    }

    window.localStorage.removeItem(PROMPT_DEFER_KEY);
    window.localStorage.removeItem(PROMPT_DECLINED_KEY);

    return token;
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
