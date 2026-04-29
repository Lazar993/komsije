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
        {{-- Hidden by default; the JS reveals it only for users who can act. --}}
        <div data-push-state="default" hidden class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-start gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-blue-50 text-[var(--komsije-primary)]">
                    <x-portal.app-icon name="bell" class="h-5 w-5" />
                </span>
                <div>
                    <p class="text-sm font-semibold text-slate-900">{{ __('Uključite obaveštenja') }}</p>
                    <p class="text-xs leading-5 text-slate-500">{{ __('Primajte push obaveštenja o vašim kvarovima i obaveštenjima zgrade.') }}</p>
                </div>
            </div>
            <div class="flex shrink-0 items-center gap-2 self-end sm:self-auto">
                <button type="button" class="rounded-full px-3 py-2 text-sm font-medium text-slate-500 transition hover:text-slate-900" data-push-action="dismiss">
                    {{ __('Kasnije') }}
                </button>
                <button type="button" class="inline-flex min-h-11 items-center justify-center rounded-full bg-[var(--komsije-primary)] px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-blue-600/20 transition hover:bg-blue-700" data-push-action="enable">
                    {{ __('Uključi') }}
                </button>
            </div>
        </div>

        <div data-push-state="needs-install" hidden class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-start gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-amber-50 text-amber-600">
                    <x-portal.app-icon name="bell" class="h-5 w-5" />
                </span>
                <div>
                    <p class="text-sm font-semibold text-slate-900">{{ __('Dodajte aplikaciju na početni ekran') }}</p>
                    <p class="text-xs leading-5 text-slate-500">{{ __('Na iPhone-u push obaveštenja rade tek kada otvorite aplikaciju iz ikone na početnom ekranu (Safari → Share → Add to Home Screen).') }}</p>
                </div>
            </div>
            <button type="button" class="self-end rounded-full px-3 py-2 text-sm font-medium text-slate-500 transition hover:text-slate-900 sm:self-auto" data-push-action="dismiss">
                {{ __('U redu') }}
            </button>
        </div>
    </div>
@else
    <article class="komsije-surface rounded-[2rem] p-6 sm:p-7" data-push-settings data-push-status="default">
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-[var(--komsije-primary)]">{{ __('Obaveštenja') }}</p>
                <h2 class="mt-1 text-xl font-semibold tracking-tight text-slate-950">{{ __('Push obaveštenja') }}</h2>
            </div>
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-blue-50 text-[var(--komsije-primary)]">
                <x-portal.app-icon name="bell" class="h-5 w-5" />
            </span>
        </div>

        <p class="mt-3 text-sm leading-6 text-slate-600">
            {{ __('Primajte trenutna obaveštenja o promenama statusa kvarova i novim obaveštenjima u vašoj zgradi.') }}
        </p>

        <div class="mt-5 space-y-3">
            {{-- Granted --}}
            <div data-push-state="granted" hidden class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-2 text-sm font-medium text-emerald-700">
                    <span class="inline-flex h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                    {{ __('Obaveštenja su uključena na ovom uređaju.') }}
                </div>
                <button type="button" class="self-start rounded-full border border-[var(--komsije-border)] bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:border-rose-200 hover:text-rose-600 sm:self-auto" data-push-action="disable">
                    {{ __('Isključi') }}
                </button>
            </div>

            {{-- Default (not yet asked) --}}
            <div data-push-state="default" hidden class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-2 text-sm font-medium text-slate-600">
                    <span class="inline-flex h-2.5 w-2.5 rounded-full bg-slate-300"></span>
                    {{ __('Obaveštenja još nisu uključena.') }}
                </div>
                <button type="button" class="self-start inline-flex min-h-11 items-center justify-center rounded-full bg-[var(--komsije-primary)] px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-blue-600/20 transition hover:bg-blue-700 sm:self-auto" data-push-action="enable">
                    {{ __('Uključi obaveštenja') }}
                </button>
            </div>

            {{-- Denied --}}
            <div data-push-state="denied" hidden class="rounded-2xl border border-rose-100 bg-rose-50 px-4 py-3 text-sm leading-6 text-rose-800">
                <p class="font-semibold">{{ __('Obaveštenja su blokirana u pretraživaču.') }}</p>
                <p class="mt-1 text-rose-700">{{ __('Otvorite podešavanja sajta u pretraživaču i dozvolite obaveštenja, zatim osvežite stranicu.') }}</p>
            </div>

            {{-- iOS needs install --}}
            <div data-push-state="needs-install" hidden class="rounded-2xl border border-amber-100 bg-amber-50 px-4 py-3 text-sm leading-6 text-amber-900">
                <p class="font-semibold">{{ __('Potrebna instalacija na početni ekran') }}</p>
                <p class="mt-1">{{ __('Na iPhone-u push obaveštenja rade tek kada se aplikacija otvori iz ikone na početnom ekranu. U Safariju otvorite Share meni i izaberite Add to Home Screen.') }}</p>
            </div>

            {{-- Unsupported --}}
            <div data-push-state="unsupported" hidden class="rounded-2xl bg-slate-50 px-4 py-3 text-sm leading-6 text-slate-600">
                {{ __('Vaš pretraživač ne podržava push obaveštenja.') }}
            </div>

            {{-- No config --}}
            <div data-push-state="no-config" hidden class="rounded-2xl bg-slate-50 px-4 py-3 text-sm leading-6 text-slate-600">
                {{ __('Push obaveštenja trenutno nisu konfigurisana na serveru.') }}
            </div>
        </div>
    </article>
@endif
