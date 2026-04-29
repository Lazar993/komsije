@php
    /**
     * Variants:
     *   'card'   - full settings card (used on profile)
     *   'banner' - compact inline banner (used on dashboard)
     */
    $variant = $variant ?? 'card';
@endphp

@if ($variant === 'banner')
    <div
        class="komsije-surface mb-6 rounded-[1.75rem] px-4 py-4 sm:px-5 sm:py-4"
        data-push-settings
        data-push-status="default"
        hidden
        data-push-banner
    >
        <div data-push-state="default" hidden class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-start gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-blue-50 text-[var(--komsije-primary)]">
                    <x-portal.app-icon name="bell" class="h-5 w-5" />
                </span>
                <div>
                    <p class="text-sm font-semibold text-slate-900">{{ __('Turn on notifications') }}</p>
                    <p class="text-xs leading-5 text-slate-500">{{ __('Get instant updates about your tickets and building announcements.') }}</p>
                </div>
            </div>
            <div class="flex shrink-0 items-center gap-2 self-end sm:self-auto">
                <button type="button" class="rounded-full px-3 py-2 text-sm font-medium text-slate-500 transition hover:text-slate-900" data-push-action="dismiss">
                    {{ __('Later') }}
                </button>
                <button type="button" class="inline-flex min-h-11 items-center justify-center rounded-full bg-[var(--komsije-primary)] px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-blue-600/20 transition hover:bg-blue-700" data-push-action="enable">
                    {{ __('Turn on') }}
                </button>
            </div>
        </div>

        <div data-push-state="needs-install" hidden class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-start gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-amber-50 text-amber-600">
                    <x-portal.app-icon name="bell" class="h-5 w-5" />
                </span>
                <div>
                    <p class="text-sm font-semibold text-slate-900">{{ __('Install the app to receive notifications') }}</p>
                    <p class="text-xs leading-5 text-slate-500">{{ __('On iPhone, notifications only work after adding the app to your Home Screen.') }}</p>
                </div>
            </div>
            <button type="button" class="self-end rounded-full px-3 py-2 text-sm font-medium text-slate-500 transition hover:text-slate-900 sm:self-auto" data-push-action="dismiss">
                {{ __('OK') }}
            </button>
        </div>
    </div>
@else
    @php
        $userAgent = request()->userAgent() ?? '';
        $isIos = (bool) preg_match('/iphone|ipad|ipod/i', $userAgent);
        $isAndroid = (bool) preg_match('/android/i', $userAgent);
    @endphp

    <article class="komsije-surface rounded-[2rem] p-6 sm:p-7" data-push-settings data-push-status="default">
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-[var(--komsije-primary)]">{{ __('Notifications') }}</p>
                <h2 class="mt-1 text-xl font-semibold tracking-tight text-slate-950">{{ __('Push notifications') }}</h2>
            </div>
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-blue-50 text-[var(--komsije-primary)]">
                <x-portal.app-icon name="bell" class="h-5 w-5" />
            </span>
        </div>

        <p class="mt-3 text-sm leading-6 text-slate-600">
            {{ __('Receive instant updates about ticket status changes and new announcements in your building.') }}
        </p>

        <div class="mt-5 space-y-4">
            {{-- Granted --}}
            <div data-push-state="granted" hidden class="flex flex-col gap-3 rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-2 text-sm font-medium text-emerald-800">
                    <span class="inline-flex h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                    {{ __('Notifications are enabled on this device.') }}
                </div>
                <button type="button" class="self-start rounded-full border border-emerald-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:border-rose-200 hover:text-rose-600 sm:self-auto" data-push-action="disable">
                    {{ __('Turn off') }}
                </button>
            </div>

            {{-- Default (not yet asked) --}}
            <div data-push-state="default" hidden class="space-y-3">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-2 text-sm font-medium text-slate-600">
                        <span class="inline-flex h-2.5 w-2.5 rounded-full bg-slate-300"></span>
                        {{ __('Notifications are off.') }}
                    </div>
                    <button type="button" class="self-start inline-flex min-h-11 items-center justify-center rounded-full bg-[var(--komsije-primary)] px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-blue-600/20 transition hover:bg-blue-700 sm:self-auto" data-push-action="enable">
                        {{ __('Turn on notifications') }}
                    </button>
                </div>
                <p class="text-xs leading-5 text-slate-500">
                    {{ __('Your browser will ask for permission. You can change your mind anytime from this page.') }}
                </p>
            </div>

            {{-- Denied --}}
            <div data-push-state="denied" hidden class="space-y-3 rounded-2xl border border-rose-100 bg-rose-50 px-4 py-4">
                <div class="flex items-start gap-2 text-sm">
                    <span class="mt-0.5 inline-flex h-2.5 w-2.5 shrink-0 rounded-full bg-rose-500"></span>
                    <div>
                        <p class="font-semibold text-rose-900">{{ __('Notifications are blocked in your browser.') }}</p>
                        <p class="mt-1 text-rose-800">{{ __('If you just allowed notifications in your device settings, tap Try again below. Otherwise, follow the steps for your device.') }}</p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" class="inline-flex min-h-11 items-center justify-center rounded-full bg-[var(--komsije-primary)] px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-blue-600/20 transition hover:bg-blue-700" data-push-action="enable">
                        {{ __('Try again') }}
                    </button>
                    <button type="button" class="inline-flex min-h-11 items-center justify-center rounded-full border border-rose-200 bg-white px-4 py-2.5 text-sm font-medium text-rose-700 transition hover:bg-rose-50" onclick="window.location.reload()">
                        {{ __('Reload page') }}
                    </button>
                </div>

                <details class="group rounded-xl bg-white/60 px-3 py-2" @if ($isAndroid) open @endif>
                    <summary class="flex cursor-pointer list-none items-center justify-between text-sm font-medium text-slate-800">
                        <span>{{ __('Android (Chrome)') }}</span>
                        <span class="text-slate-400 transition group-open:rotate-180">▾</span>
                    </summary>
                    <ol class="mt-2 list-decimal space-y-1 pl-5 text-sm leading-6 text-slate-700">
                        <li>{{ __('Tap the lock icon next to the address bar.') }}</li>
                        <li>{{ __('Open Permissions → Notifications.') }}</li>
                        <li>{{ __('Choose Allow, then refresh the page.') }}</li>
                    </ol>
                </details>

                <details class="group rounded-xl bg-white/60 px-3 py-2" @if ($isIos) open @endif>
                    <summary class="flex cursor-pointer list-none items-center justify-between text-sm font-medium text-slate-800">
                        <span>{{ __('iPhone (Home Screen app)') }}</span>
                        <span class="text-slate-400 transition group-open:rotate-180">▾</span>
                    </summary>
                    <ol class="mt-2 list-decimal space-y-1 pl-5 text-sm leading-6 text-slate-700">
                        <li>{{ __('Open the iPhone Settings app.') }}</li>
                        <li>{{ __('Go to Notifications → Komšije.') }}</li>
                        <li>{{ __('Turn on Allow Notifications.') }}</li>
                        <li>{{ __('Return here and tap Try again. If it still does not work, fully close the app (swipe up) and reopen it from the Home Screen.') }}</li>
                    </ol>
                </details>

                <details class="group rounded-xl bg-white/60 px-3 py-2">
                    <summary class="flex cursor-pointer list-none items-center justify-between text-sm font-medium text-slate-800">
                        <span>{{ __('Desktop browser') }}</span>
                        <span class="text-slate-400 transition group-open:rotate-180">▾</span>
                    </summary>
                    <ol class="mt-2 list-decimal space-y-1 pl-5 text-sm leading-6 text-slate-700">
                        <li>{{ __('Click the lock or info icon to the left of the address bar.') }}</li>
                        <li>{{ __('Find Notifications and switch them to Allow.') }}</li>
                        <li>{{ __('Reload the page.') }}</li>
                    </ol>
                </details>
            </div>

            {{-- iOS needs install --}}
            <div data-push-state="needs-install" hidden class="space-y-3 rounded-2xl border border-amber-100 bg-amber-50 px-4 py-4">
                <div class="flex items-start gap-2 text-sm">
                    <span class="mt-0.5 inline-flex h-2.5 w-2.5 shrink-0 rounded-full bg-amber-500"></span>
                    <div>
                        <p class="font-semibold text-amber-900">{{ __('Install the app to receive notifications') }}</p>
                        <p class="mt-1 text-amber-800">{{ __('On iPhone, push notifications only work when the app is opened from your Home Screen.') }}</p>
                    </div>
                </div>

                <div class="rounded-xl bg-white/70 px-3 py-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-amber-700">{{ __('How to install') }}</p>
                    <ol class="mt-2 list-decimal space-y-1.5 pl-5 text-sm leading-6 text-slate-800">
                        <li>{{ __('Tap the Share button at the bottom of Safari (square with an arrow).') }}</li>
                        <li>{{ __('Scroll down and tap "Add to Home Screen".') }}</li>
                        <li>{{ __('Tap Add in the top right corner.') }}</li>
                        <li>{{ __('Open Komšije from the Home Screen icon and turn on notifications here.') }}</li>
                    </ol>
                </div>
            </div>

            {{-- Unsupported --}}
            <div data-push-state="unsupported" hidden class="rounded-2xl bg-slate-50 px-4 py-3 text-sm leading-6 text-slate-600">
                {{ __('Your browser does not support push notifications.') }}
            </div>

            {{-- No config --}}
            <div data-push-state="no-config" hidden class="rounded-2xl bg-slate-50 px-4 py-3 text-sm leading-6 text-slate-600">
                {{ __('Push notifications are not configured on the server right now.') }}
            </div>
        </div>
    </article>
@endif
