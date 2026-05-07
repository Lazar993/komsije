const APP_NAME = 'Komšije';
const INSTALL_PROMPT_DISMISSED_KEY = 'komsije-install-dismissed';
const INSTALL_PROMPT_DISMISSED_AT_KEY = 'komsije-install-dismissed-at';
const INSTALL_PROMPT_REENGAGE_MS = 30 * 24 * 60 * 60 * 1000; // 30 days

let deferredInstallPrompt = null;
let serviceWorkerRegistration = null;
let refreshing = false;

import { initPushNotifications, enablePush, disablePush, getPushStatus } from './push.js';

// Expose for inline UI hooks (e.g. "Enable notifications" buttons).
window.komsijePush = { enablePush, disablePush, getPushStatus };

document.addEventListener('DOMContentLoaded', async () => {
    applyStandaloneMode();
    upgradeImagesForPerformance();
    setupFileInputPreviews();
    setupTicketFilters();
    setupTicketConversation();
    setupAnnouncementPagination();
    setupCardDecks();
    setupLightbox();
    setupPdfPreview();
    setupInstallPrompt();
    setupPushSettings();
    await registerServiceWorker();
    initPushNotifications();
});

function setupFileInputPreviews(root = document) {
    const inputs = root.querySelectorAll('[data-file-preview-input]');

    inputs.forEach((input) => {
        if (!(input instanceof HTMLInputElement) || input.dataset.filePreviewReady === 'true') {
            return;
        }

        const list = input.parentElement?.querySelector('[data-file-preview-list]');
        if (!(list instanceof HTMLElement)) {
            return;
        }

        input.dataset.filePreviewReady = 'true';

        const render = () => {
            const files = Array.from(input.files ?? []);

            if (!files.length) {
                list.innerHTML = '';
                list.classList.add('hidden');

                return;
            }

            list.innerHTML = files
                .map((file) => `<li class="break-all leading-6">${escapeHtml(file.name)}</li>`)
                .join('');
            list.classList.remove('hidden');
        };

        input.addEventListener('change', render);
        render();
    });
}

function escapeHtml(value) {
    return value
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#39;');
}

function setupLightbox(root = document) {
    const overlay = root.querySelector('[data-lightbox]');
    if (!(overlay instanceof HTMLElement) || overlay.dataset.lightboxReady === 'true') {
        return;
    }
    overlay.dataset.lightboxReady = 'true';

    const image = overlay.querySelector('[data-lightbox-image]');
    const caption = overlay.querySelector('[data-lightbox-caption]');
    const prevBtn = overlay.querySelector('[data-lightbox-prev]');
    const nextBtn = overlay.querySelector('[data-lightbox-next]');
    const closeBtn = overlay.querySelector('[data-lightbox-close]');

    if (!(image instanceof HTMLImageElement)) {
        return;
    }

    let items = [];
    let index = 0;
    let lastFocus = null;
    let touchStartX = 0;
    let touchStartY = 0;
    let touchActive = false;

    const setNavVisibility = () => {
        const multi = items.length > 1;
        if (prevBtn instanceof HTMLElement) {
            prevBtn.classList.toggle('hidden', !multi);
            prevBtn.classList.toggle('flex', multi);
        }
        if (nextBtn instanceof HTMLElement) {
            nextBtn.classList.toggle('hidden', !multi);
            nextBtn.classList.toggle('flex', multi);
        }
    };

    const render = () => {
        const item = items[index];
        if (!item) {
            return;
        }
        image.src = item.src;
        image.alt = item.alt || '';
        if (caption instanceof HTMLElement) {
            caption.textContent = item.alt || '';
            caption.classList.toggle('hidden', !item.alt);
        }
    };

    const open = (gallery, startSrc) => {
        const triggers = Array.from(root.querySelectorAll(`[data-lightbox-trigger="${cssEscape(gallery)}"]`));
        items = triggers
            .map((el) => ({
                src: el.getAttribute('data-lightbox-src') || '',
                alt: el.getAttribute('data-lightbox-alt') || '',
            }))
            .filter((item) => item.src);

        if (!items.length) {
            return;
        }

        const startIndex = items.findIndex((item) => item.src === startSrc);
        index = startIndex >= 0 ? startIndex : 0;

        lastFocus = document.activeElement instanceof HTMLElement ? document.activeElement : null;

        setNavVisibility();
        render();

        overlay.classList.remove('hidden');
        overlay.classList.add('flex');
        document.body.style.overflow = 'hidden';

        if (closeBtn instanceof HTMLElement) {
            closeBtn.focus();
        }
    };

    const close = () => {
        overlay.classList.add('hidden');
        overlay.classList.remove('flex');
        document.body.style.overflow = '';
        image.src = '';
        items = [];

        if (lastFocus && typeof lastFocus.focus === 'function') {
            lastFocus.focus();
        }
        lastFocus = null;
    };

    const next = () => {
        if (items.length < 2) return;
        index = (index + 1) % items.length;
        render();
    };

    const prev = () => {
        if (items.length < 2) return;
        index = (index - 1 + items.length) % items.length;
        render();
    };

    document.addEventListener('click', (event) => {
        const trigger = event.target instanceof Element ? event.target.closest('[data-lightbox-trigger]') : null;
        if (!(trigger instanceof HTMLElement)) {
            return;
        }
        event.preventDefault();
        const gallery = trigger.getAttribute('data-lightbox-trigger') || '';
        const src = trigger.getAttribute('data-lightbox-src') || '';
        if (gallery && src) {
            open(gallery, src);
        }
    });

    if (closeBtn instanceof HTMLElement) {
        closeBtn.addEventListener('click', close);
    }
    if (nextBtn instanceof HTMLElement) {
        nextBtn.addEventListener('click', next);
    }
    if (prevBtn instanceof HTMLElement) {
        prevBtn.addEventListener('click', prev);
    }

    overlay.addEventListener('click', (event) => {
        if (event.target === overlay) {
            close();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (overlay.classList.contains('hidden')) {
            return;
        }
        if (event.key === 'Escape') {
            event.preventDefault();
            close();
        } else if (event.key === 'ArrowRight') {
            event.preventDefault();
            next();
        } else if (event.key === 'ArrowLeft') {
            event.preventDefault();
            prev();
        }
    });

    overlay.addEventListener('touchstart', (event) => {
        if (event.touches.length !== 1) {
            touchActive = false;
            return;
        }
        touchActive = true;
        touchStartX = event.touches[0].clientX;
        touchStartY = event.touches[0].clientY;
    }, { passive: true });

    overlay.addEventListener('touchend', (event) => {
        if (!touchActive) {
            return;
        }
        touchActive = false;
        const touch = event.changedTouches[0];
        if (!touch) return;
        const dx = touch.clientX - touchStartX;
        const dy = touch.clientY - touchStartY;
        if (Math.abs(dx) > 50 && Math.abs(dx) > Math.abs(dy)) {
            if (dx < 0) {
                next();
            } else {
                prev();
            }
        } else if (dy > 80 && Math.abs(dy) > Math.abs(dx)) {
            close();
        }
    });
}

function cssEscape(value) {
    if (typeof window.CSS !== 'undefined' && typeof window.CSS.escape === 'function') {
        return window.CSS.escape(value);
    }
    return String(value).replace(/[^a-zA-Z0-9_-]/g, '\\$&');
}

function setupPdfPreview(root = document) {
    const overlay = root.querySelector('[data-pdf-preview]');
    if (!(overlay instanceof HTMLElement) || overlay.dataset.pdfPreviewReady === 'true') {
        return;
    }
    overlay.dataset.pdfPreviewReady = 'true';

    const frame = overlay.querySelector('[data-pdf-preview-frame]');
    const title = overlay.querySelector('[data-pdf-preview-title]');
    const closeBtn = overlay.querySelector('[data-pdf-preview-close]');
    const downloadLink = overlay.querySelector('[data-pdf-preview-download]');

    if (!(frame instanceof HTMLIFrameElement)) {
        return;
    }

    let lastFocus = null;

    const open = (src, name) => {
        if (!src) return;
        lastFocus = document.activeElement instanceof HTMLElement ? document.activeElement : null;

        if (title instanceof HTMLElement) {
            title.textContent = name || '';
        }
        if (downloadLink instanceof HTMLAnchorElement) {
            const sep = src.includes('?') ? '&' : '?';
            downloadLink.href = `${src}${sep}download=1`;
            downloadLink.classList.remove('hidden');
            downloadLink.classList.add('inline-flex');
        }

        frame.src = src;

        overlay.classList.remove('hidden');
        overlay.classList.add('flex');
        document.body.style.overflow = 'hidden';

        if (closeBtn instanceof HTMLElement) {
            closeBtn.focus();
        }
    };

    const close = () => {
        overlay.classList.add('hidden');
        overlay.classList.remove('flex');
        document.body.style.overflow = '';
        frame.src = 'about:blank';

        if (lastFocus && typeof lastFocus.focus === 'function') {
            lastFocus.focus();
        }
        lastFocus = null;
    };

    document.addEventListener('click', (event) => {
        const trigger = event.target instanceof Element ? event.target.closest('[data-pdf-preview-trigger]') : null;
        if (!(trigger instanceof HTMLElement)) {
            return;
        }
        const src = trigger.getAttribute('data-pdf-preview-src') || '';
        if (!src) return;
        // Allow modifier-click / middle-click to fall through to native open-in-tab.
        if (event.metaKey || event.ctrlKey || event.shiftKey || (event instanceof MouseEvent && event.button !== 0)) {
            return;
        }
        event.preventDefault();
        open(src, trigger.getAttribute('data-pdf-preview-name') || '');
    });

    if (closeBtn instanceof HTMLElement) {
        closeBtn.addEventListener('click', close);
    }

    document.addEventListener('keydown', (event) => {
        if (overlay.classList.contains('hidden')) {
            return;
        }
        if (event.key === 'Escape') {
            event.preventDefault();
            close();
        }
    });
}

function setupCardDecks(root = document) {
    const decks = root.querySelectorAll('[data-card-deck]');
    if (!decks.length) {
        return;
    }

    const mobileQuery = window.matchMedia('(max-width: 767px)');

    decks.forEach((deck) => {
        if (deck.dataset.cardDeckReady === 'true') {
            return;
        }
        deck.dataset.cardDeckReady = 'true';

        const scroller = deck.querySelector('[data-card-deck-scroller]');
        const counter = deck.querySelector('[data-card-deck-counter]');
        if (!scroller) {
            return;
        }

        const items = () => Array.from(scroller.children).filter(
            (child) => child.dataset.cardDeckItem !== 'skip',
        );

        const refreshState = () => {
            const total = items().length;
            if (total === 0) {
                deck.dataset.cardDeckEmpty = 'true';
                deck.removeAttribute('data-card-deck-single');
            } else if (total === 1) {
                deck.dataset.cardDeckSingle = 'true';
                deck.removeAttribute('data-card-deck-empty');
            } else {
                deck.removeAttribute('data-card-deck-single');
                deck.removeAttribute('data-card-deck-empty');
            }
            return total;
        };

        const updateCounter = () => {
            if (!counter) {
                return;
            }
            const total = items().length;
            if (total <= 1 || !mobileQuery.matches) {
                return;
            }
            const width = scroller.clientWidth || 1;
            const index = Math.min(total - 1, Math.max(0, Math.round(scroller.scrollLeft / width)));
            counter.textContent = `${index + 1}/${total}`;
        };

        refreshState();
        updateCounter();

        let frame = null;
        scroller.addEventListener(
            'scroll',
            () => {
                if (frame) {
                    cancelAnimationFrame(frame);
                }
                frame = requestAnimationFrame(updateCounter);
            },
            { passive: true },
        );

        const onResize = () => {
            scroller.scrollLeft = 0;
            updateCounter();
        };

        if (typeof mobileQuery.addEventListener === 'function') {
            mobileQuery.addEventListener('change', onResize);
        } else if (typeof mobileQuery.addListener === 'function') {
            mobileQuery.addListener(onResize);
        }

        window.addEventListener('resize', updateCounter, { passive: true });
    });
}

window.komsijeSetupCardDecks = setupCardDecks;

function setupTicketFilters() {
    setupAsyncListing({
        formSelector: '[data-ticket-filters]',
        resultsSelector: '[data-ticket-results]',
        resetSelector: '[data-ticket-filters-reset]',
    });
}

function setupTicketConversation(root = document) {
    const conversations = root.querySelectorAll('[data-ticket-conversation]');

    conversations.forEach((conversation) => {
        if (!(conversation instanceof HTMLElement) || conversation.dataset.ticketConversationReady === 'true') {
            return;
        }

        const article = conversation.closest('article');
        const refreshUrl = conversation.dataset.refreshUrl;
        const shell = conversation.querySelector('[data-ticket-conversation-shell]');
        const count = conversation.querySelector('[data-ticket-conversation-count]');
        const form = article?.querySelector('[data-ticket-conversation-form]');
        const error = form?.querySelector('[data-ticket-conversation-error]');
        const status = form?.querySelector('[data-ticket-conversation-form-status]');
        const submit = form?.querySelector('[data-ticket-conversation-submit]');
        const textarea = form?.querySelector('textarea[name="body"]');

        if (!refreshUrl || !(shell instanceof HTMLElement)) {
            return;
        }

        conversation.dataset.ticketConversationReady = 'true';

        let isSubmitting = false;
        let shouldStickToBottom = true;
        let pollRequest = null;
        const idleStatus = conversation.dataset.statusIdle || 'Messages stay on this ticket so everyone sees the same timeline.';
        const sendingStatus = conversation.dataset.statusSending || 'Sending...';
        const sentStatus = conversation.dataset.statusSent || 'Message sent.';
        const validationStatus = conversation.dataset.statusValidation || 'Please review the message and try again.';
        const failedStatus = conversation.dataset.statusFailed || 'Message delivery failed.';
        const sendErrorMessage = conversation.dataset.errorSend || 'Unable to send the message right now. Reload the page and try again.';
        const genericErrorMessage = conversation.dataset.errorGeneric || 'Unable to send the message right now.';

        const feed = () => shell.querySelector('[data-ticket-conversation-feed]');

        const isNearBottom = () => {
            const node = feed();

            if (!(node instanceof HTMLElement)) {
                return true;
            }

            return node.scrollHeight - node.scrollTop - node.clientHeight < 56;
        };

        const scrollToLatest = (behavior = 'smooth') => {
            const node = feed();

            if (!(node instanceof HTMLElement)) {
                return;
            }

            node.scrollTo({
                top: node.scrollHeight,
                behavior,
            });
        };

        const attachFeedTracking = () => {
            const node = feed();

            if (!(node instanceof HTMLElement) || node.dataset.ticketConversationTracked === 'true') {
                return;
            }

            node.dataset.ticketConversationTracked = 'true';
            node.addEventListener('scroll', () => {
                shouldStickToBottom = isNearBottom();
            }, { passive: true });
        };

        const setSubmitState = (disabled) => {
            if (submit instanceof HTMLButtonElement) {
                submit.disabled = disabled;
            }
        };

        const setError = (message = '') => {
            if (!(error instanceof HTMLElement)) {
                return;
            }

            error.textContent = message;
            error.classList.toggle('hidden', message === '');
        };

        const setStatus = (message) => {
            if (status instanceof HTMLElement) {
                status.textContent = message;
            }
        };

        const applyPayload = (payload, { forceScroll = false } = {}) => {
            const previousCount = Number(conversation.dataset.commentCount || 0);
            const previousLatestId = conversation.dataset.latestCommentId || '';
            const nextCount = Number(payload.count || 0);
            const nextLatestId = payload.latestCommentId ? String(payload.latestCommentId) : '';
            const hasNewMessage = nextCount !== previousCount || nextLatestId !== previousLatestId;

            if (typeof payload.html === 'string') {
                shell.innerHTML = payload.html;
            }

            if (count instanceof HTMLElement && typeof payload.countLabel === 'string') {
                count.textContent = payload.countLabel;
            }

            conversation.dataset.commentCount = String(nextCount);
            conversation.dataset.latestCommentId = nextLatestId;

            attachFeedTracking();

            if (forceScroll || (hasNewMessage && shouldStickToBottom)) {
                requestAnimationFrame(() => scrollToLatest(forceScroll ? 'smooth' : 'auto'));
            }
        };

        const fetchConversation = async ({ forceScroll = false } = {}) => {
            pollRequest?.abort();
            pollRequest = new AbortController();

            try {
                const url = new URL(refreshUrl, window.location.origin);
                url.searchParams.set('fragment', 'conversation');

                const response = await fetch(url.toString(), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        Accept: 'application/json',
                    },
                    signal: pollRequest.signal,
                });

                if (!response.ok) {
                    throw new Error(`Ticket conversation refresh failed with status ${response.status}`);
                }

                applyPayload(await response.json(), { forceScroll });
            } catch (error) {
                if (error.name !== 'AbortError') {
                    pollRequest = null;
                }
            }
        };

        attachFeedTracking();
        requestAnimationFrame(() => scrollToLatest('auto'));

        const pollInterval = window.setInterval(() => {
            if (document.hidden || isSubmitting) {
                return;
            }

            void fetchConversation();
        }, Number(conversation.dataset.refreshInterval || 15000));

        window.addEventListener('beforeunload', () => {
            window.clearInterval(pollInterval);
            pollRequest?.abort();
        }, { once: true });

        form?.addEventListener('submit', async (event) => {
            if (!(form instanceof HTMLFormElement)) {
                return;
            }

            event.preventDefault();

            if (isSubmitting) {
                return;
            }

            isSubmitting = true;
            shouldStickToBottom = true;
            setSubmitState(true);
            setError('');
            setStatus(sendingStatus);

            try {
                const response = await fetch(form.action, {
                    method: form.method || 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        Accept: 'application/json',
                    },
                    body: new FormData(form),
                });

                const payload = await response.json();

                if (!response.ok) {
                    const message = payload?.errors?.body?.[0]
                        || payload?.message
                        || genericErrorMessage;

                    setError(message);
                    setStatus(validationStatus);
                    return;
                }

                applyPayload(payload, { forceScroll: true });

                if (textarea instanceof HTMLTextAreaElement) {
                    textarea.value = '';
                    textarea.focus();
                }

                setStatus(sentStatus);
                window.setTimeout(() => {
                    setStatus(idleStatus);
                }, 2500);
            } catch (error) {
                setError(sendErrorMessage);
                setStatus(failedStatus);
            } finally {
                isSubmitting = false;
                setSubmitState(false);
            }
        });
    });
}

function setupAnnouncementPagination() {
    setupAsyncListing({
        resultsSelector: '[data-announcement-results]',
    });
}

function setupAsyncListing({ formSelector = null, resultsSelector, resetSelector = null }) {
    const form = formSelector ? document.querySelector(formSelector) : null;
    const results = document.querySelector(resultsSelector);

    if (!results || (formSelector && !form)) {
        return;
    }

    const baseUrl = form instanceof HTMLFormElement
        ? new URL(form.action, window.location.origin)
        : new URL(window.location.href, window.location.origin);
    const resetLink = resetSelector ? document.querySelector(resetSelector) : null;
    let activeRequest = null;

    const syncFormWithUrl = (url) => {
        if (!(form instanceof HTMLFormElement)) {
            return;
        }

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
                throw new Error(`Async listing request failed with status ${response.status}`);
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

    const buildFormUrl = () => {
        if (!(form instanceof HTMLFormElement)) {
            return baseUrl.toString();
        }

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

    form?.addEventListener('submit', (event) => {
        event.preventDefault();
        void requestResults(buildFormUrl());
    });

    form?.querySelectorAll('select').forEach((field) => {
        field.addEventListener('change', () => {
            void requestResults(buildFormUrl());
        });
    });

    resetLink?.addEventListener('click', (event) => {
        if (!(form instanceof HTMLFormElement)) {
            return;
        }

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

        if (url.origin !== window.location.origin || url.pathname !== baseUrl.pathname) {
            return;
        }

        event.preventDefault();
        void requestResults(url.toString());
    });

    window.addEventListener('popstate', () => {
        if (window.location.pathname !== baseUrl.pathname) {
            return;
        }

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

            // Only reload if the new controller is OUR app shell SW.
            // The Firebase messaging SW must never trigger a reload — that
            // would cause an infinite loop with the main SW.
            const controllerUrl = navigator.serviceWorker.controller?.scriptURL || '';

            if (!controllerUrl.endsWith('/service-worker.js')) {
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
    const wasDismissed = isInstallPromptDismissed();
    const ua = window.navigator.userAgent;
    const isIos = /iphone|ipad|ipod/i.test(ua);
    // iPadOS 13+ reports as Mac. Treat touch-enabled "Mac" with maxTouchPoints>1 as iPad.
    const isIpadOs = !isIos && /Macintosh/.test(ua) && (navigator.maxTouchPoints || 0) > 1;
    const iosLike = isIos || isIpadOs;
    // Add to Home Screen only works in Safari on iOS. Detect in-app browsers
    // (Chrome iOS = CriOS, Firefox iOS = FxiOS, Edge iOS = EdgiOS, Facebook /
    // Instagram = FBAN/FBAV/Instagram) so we can tell users to reopen in Safari.
    const isIosSafari = iosLike && !/CriOS|FxiOS|EdgiOS|OPiOS|YaBrowser|FBAN|FBAV|Instagram|Line\//i.test(ua);

    if (isStandalone || wasDismissed) {
        return;
    }

    dismissButton?.addEventListener('click', () => {
        rememberInstallPromptDismissal();
        setInstallPromptVisibility(promptElement, false);
    });

    actionButton?.addEventListener('click', async () => {
        if (actionButton.dataset.mode === 'ios-help') {
            rememberInstallPromptDismissal();
            setInstallPromptVisibility(promptElement, false);
            return;
        }

        if (!deferredInstallPrompt) {
            return;
        }

        deferredInstallPrompt.prompt();
        const { outcome } = await deferredInstallPrompt.userChoice;

        if (outcome === 'dismissed') {
            rememberInstallPromptDismissal();
        } else {
            clearInstallPromptDismissal();
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
        clearInstallPromptDismissal();
        document.documentElement.dataset.appInstalled = 'true';
    });

    if (iosLike) {
        if (titleElement) {
            titleElement.textContent = APP_NAME;
        }

        if (copyElement) {
            copyElement.textContent = isIosSafari
                ? 'U Safariju otvorite Share meni i izaberite Add to Home Screen da biste instalirali aplikaciju.'
                : 'Da biste instalirali aplikaciju, otvorite ovu stranicu u Safariju, pa u Share meniju izaberite Add to Home Screen.';
        }

        if (actionButton) {
            actionButton.textContent = 'U redu';
            actionButton.dataset.mode = 'ios-help';
        }

        setInstallPromptVisibility(promptElement, true);
    }
}

function isInstallPromptDismissed() {
    try {
        if (window.localStorage.getItem(INSTALL_PROMPT_DISMISSED_KEY) !== 'true') {
            return false;
        }

        // Re-engage Android/Chrome users after the cool-off window so the
        // install affordance isn't permanently suppressed by a single dismiss.
        const dismissedAt = Number(window.localStorage.getItem(INSTALL_PROMPT_DISMISSED_AT_KEY) || 0);

        if (dismissedAt && Date.now() - dismissedAt > INSTALL_PROMPT_REENGAGE_MS) {
            clearInstallPromptDismissal();
            return false;
        }

        return true;
    } catch {
        return false;
    }
}

function rememberInstallPromptDismissal() {
    try {
        window.localStorage.setItem(INSTALL_PROMPT_DISMISSED_KEY, 'true');
        window.localStorage.setItem(INSTALL_PROMPT_DISMISSED_AT_KEY, String(Date.now()));
    } catch {
        // Storage unavailable (e.g. private mode) — non-fatal.
    }
}

function clearInstallPromptDismissal() {
    try {
        window.localStorage.removeItem(INSTALL_PROMPT_DISMISSED_KEY);
        window.localStorage.removeItem(INSTALL_PROMPT_DISMISSED_AT_KEY);
    } catch {
        // Storage unavailable — non-fatal.
    }
}

function setInstallPromptVisibility(element, visible) {
    element.hidden = !visible;
    element.dataset.visible = String(visible);
}

function setupPushSettings() {
    const elements = document.querySelectorAll('[data-push-settings]');

    if (elements.length === 0) {
        return;
    }

    const apply = (status) => {
        elements.forEach((element) => {
            element.dataset.pushStatus = status;

            element.querySelectorAll('[data-push-state]').forEach((node) => {
                node.hidden = node.dataset.pushState !== status;
            });

            // Banners are hidden by default in the markup; only reveal them for
            // actionable states and only if the user hasn't dismissed them.
            if (element.hasAttribute('data-push-banner')) {
                const dismissed = (() => {
                    try { return window.localStorage.getItem('komsije-push-banner-dismissed') === 'true'; }
                    catch { return false; }
                })();

                const actionable = status === 'default' || status === 'needs-install';
                element.hidden = !actionable || dismissed;
            }
        });
    };

    elements.forEach((element) => {
        element.addEventListener('click', async (event) => {
            const enableBtn = event.target.closest('[data-push-action="enable"]');
            const disableBtn = event.target.closest('[data-push-action="disable"]');
            const dismissBtn = event.target.closest('[data-push-action="dismiss"]');

            if (enableBtn) {
                event.preventDefault();
                enableBtn.disabled = true;
                try {
                    await window.komsijePush.enablePush();
                } finally {
                    enableBtn.disabled = false;
                    apply(window.komsijePush.getPushStatus());
                }
                return;
            }

            if (disableBtn) {
                event.preventDefault();
                disableBtn.disabled = true;
                try {
                    await window.komsijePush.disablePush();
                } finally {
                    disableBtn.disabled = false;
                    apply(window.komsijePush.getPushStatus());
                }
                return;
            }

            if (dismissBtn) {
                event.preventDefault();
                if (element.hasAttribute('data-push-banner')) {
                    element.hidden = true;
                    try {
                        window.localStorage.setItem('komsije-push-banner-dismissed', 'true');
                    } catch { /* ignore */ }
                }
            }
        });
    });

    document.addEventListener('komsije:push-status', (event) => {
        apply(event.detail?.status || window.komsijePush.getPushStatus());
    });

    apply(window.komsijePush.getPushStatus());
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
