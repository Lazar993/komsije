const APP_NAME = 'Komšije';
const INSTALL_PROMPT_DISMISSED_KEY = 'komsije-install-dismissed';

let deferredInstallPrompt = null;
let serviceWorkerRegistration = null;
let refreshing = false;

document.addEventListener('DOMContentLoaded', async () => {
    applyStandaloneMode();
    upgradeImagesForPerformance();
    setupTicketFilters();
    setupInstallPrompt();
    await registerServiceWorker();
});

function setupTicketFilters() {
    const form = document.querySelector('[data-ticket-filters]');
    const results = document.querySelector('[data-ticket-results]');

    if (!form || !results) {
        return;
    }

    const resetLink = document.querySelector('[data-ticket-filters-reset]');
    let activeRequest = null;

    const syncFormWithUrl = (url) => {
        const searchParams = new URL(url, window.location.origin).searchParams;

        form.querySelectorAll('select[name], input[name]').forEach((field) => {
            if (!(field instanceof HTMLInputElement || field instanceof HTMLSelectElement || field instanceof HTMLTextAreaElement)) {
                return;
            }

            field.value = searchParams.get(field.name) ?? '';
        });
    };

    const setLoadingState = (isLoading) => {
        results.setAttribute('aria-busy', String(isLoading));
        results.classList.toggle('pointer-events-none', isLoading);
        results.classList.toggle('opacity-60', isLoading);
    };

    const requestResults = async (url, { replace = false } = {}) => {
        activeRequest?.abort();
        activeRequest = new AbortController();

        setLoadingState(true);

        try {
            const response = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'text/html',
                },
                signal: activeRequest.signal,
            });

            if (!response.ok) {
                throw new Error(`Ticket filter request failed with status ${response.status}`);
            }

            results.innerHTML = await response.text();
            syncFormWithUrl(url);

            const historyMethod = replace ? 'replaceState' : 'pushState';
            window.history[historyMethod]({}, '', url);
        } catch (error) {
            if (error.name !== 'AbortError') {
                window.location.assign(url);
            }
        } finally {
            setLoadingState(false);
        }
    };

    const buildFilterUrl = () => {
        const url = new URL(form.action, window.location.origin);
        const formData = new FormData(form);

        for (const [key, value] of formData.entries()) {
            if (typeof value === 'string' && value !== '') {
                url.searchParams.set(key, value);
            }
        }

        url.searchParams.delete('page');

        return url.toString();
    };

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        void requestResults(buildFilterUrl());
    });

    form.querySelectorAll('select').forEach((field) => {
        field.addEventListener('change', () => {
            void requestResults(buildFilterUrl());
        });
    });

    resetLink?.addEventListener('click', (event) => {
        event.preventDefault();
        form.reset();
        void requestResults(resetLink.href);
    });

    results.addEventListener('click', (event) => {
        const link = event.target instanceof Element ? event.target.closest('a[href]') : null;

        if (!link || !results.contains(link)) {
            return;
        }

        const url = new URL(link.href, window.location.origin);

        if (url.origin !== window.location.origin || url.pathname !== new URL(form.action, window.location.origin).pathname) {
            return;
        }

        event.preventDefault();
        void requestResults(url.toString());
    });

    window.addEventListener('popstate', () => {
        syncFormWithUrl(window.location.href);
        void requestResults(window.location.href, { replace: true });
    });
}

async function registerServiceWorker() {
    if (!import.meta.env.PROD || !('serviceWorker' in navigator) || !window.isSecureContext) {
        return;
    }

    try {
        serviceWorkerRegistration = await navigator.serviceWorker.register('/service-worker.js');

        if (serviceWorkerRegistration.waiting) {
            promptServiceWorkerUpdate(serviceWorkerRegistration);
        }

        serviceWorkerRegistration.addEventListener('updatefound', () => {
            const installingWorker = serviceWorkerRegistration.installing;

            if (!installingWorker) {
                return;
            }

            installingWorker.addEventListener('statechange', () => {
                if (installingWorker.state === 'installed' && navigator.serviceWorker.controller) {
                    promptServiceWorkerUpdate(serviceWorkerRegistration);
                }
            });
        });

        navigator.serviceWorker.addEventListener('controllerchange', () => {
            if (refreshing) {
                return;
            }

            refreshing = true;
            window.location.reload();
        });
    } catch (error) {
        console.error('Service worker registration failed', error);
    }
}

function promptServiceWorkerUpdate(registration) {
    registration.waiting?.postMessage({ type: 'SKIP_WAITING' });
}

function setupInstallPrompt() {
    const promptElement = document.querySelector('[data-install-prompt]');

    if (!promptElement) {
        return;
    }

    const actionButton = promptElement.querySelector('[data-install-action]');
    const dismissButton = promptElement.querySelector('[data-install-dismiss]');
    const titleElement = promptElement.querySelector('[data-install-title]');
    const copyElement = promptElement.querySelector('[data-install-copy]');
    const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
    const wasDismissed = window.localStorage.getItem(INSTALL_PROMPT_DISMISSED_KEY) === 'true';
    const isIos = /iphone|ipad|ipod/i.test(window.navigator.userAgent);

    if (isStandalone || wasDismissed) {
        return;
    }

    dismissButton?.addEventListener('click', () => {
        window.localStorage.setItem(INSTALL_PROMPT_DISMISSED_KEY, 'true');
        setInstallPromptVisibility(promptElement, false);
    });

    actionButton?.addEventListener('click', async () => {
        if (actionButton.dataset.mode === 'ios-help') {
            window.localStorage.setItem(INSTALL_PROMPT_DISMISSED_KEY, 'true');
            setInstallPromptVisibility(promptElement, false);
            return;
        }

        if (!deferredInstallPrompt) {
            return;
        }

        deferredInstallPrompt.prompt();
        const { outcome } = await deferredInstallPrompt.userChoice;

        if (outcome === 'dismissed') {
            window.localStorage.setItem(INSTALL_PROMPT_DISMISSED_KEY, 'true');
        } else {
            window.localStorage.removeItem(INSTALL_PROMPT_DISMISSED_KEY);
        }

        deferredInstallPrompt = null;
        setInstallPromptVisibility(promptElement, false);
    });

    window.addEventListener('beforeinstallprompt', (event) => {
        event.preventDefault();
        deferredInstallPrompt = event;
        setInstallPromptVisibility(promptElement, true);
    });

    window.addEventListener('appinstalled', () => {
        deferredInstallPrompt = null;
        setInstallPromptVisibility(promptElement, false);
        window.localStorage.removeItem(INSTALL_PROMPT_DISMISSED_KEY);
        document.documentElement.dataset.appInstalled = 'true';
    });

    if (isIos) {
        if (titleElement) {
            titleElement.textContent = APP_NAME;
        }

        if (copyElement) {
            copyElement.textContent = 'U Safariju otvorite Share meni i izaberite Add to Home Screen da biste instalirali aplikaciju.';
        }

        if (actionButton) {
            actionButton.textContent = 'U redu';
            actionButton.dataset.mode = 'ios-help';
        }

        setInstallPromptVisibility(promptElement, true);
    }
}

function setInstallPromptVisibility(element, visible) {
    element.hidden = !visible;
    element.dataset.visible = String(visible);
}

function applyStandaloneMode() {
    const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;

    if (!isStandalone) {
        return;
    }

    document.body.dataset.appShell = 'standalone';
}

function upgradeImagesForPerformance() {
    document.querySelectorAll('img').forEach((image) => {
        if (!image.hasAttribute('loading')) {
            image.loading = 'lazy';
        }

        if (!image.hasAttribute('decoding')) {
            image.decoding = 'async';
        }
    });
}
