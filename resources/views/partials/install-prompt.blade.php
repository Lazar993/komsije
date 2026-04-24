<div
    class="pointer-events-none fixed inset-x-4 bottom-24 z-40 translate-y-6 opacity-0 transition duration-300 data-[visible=true]:pointer-events-auto data-[visible=true]:translate-y-0 data-[visible=true]:opacity-100 md:bottom-6 md:left-auto md:right-6 md:max-w-sm"
    data-install-prompt
    data-visible="false"
    hidden
>
    <div class="komsije-surface flex items-center gap-3 rounded-[1.75rem] px-4 py-4 shadow-xl shadow-slate-900/10">
        <img src="{{ asset('icons/logo-icon-v2.svg') }}" alt="" class="h-12 w-12 shrink-0 rounded-2xl" width="48" height="48">
        <div class="min-w-0 flex-1">
            <p class="text-sm font-semibold text-slate-900" data-install-title>{{ __('Install Komšije') }}</p>
            <p class="mt-1 text-xs leading-5 text-slate-500" data-install-copy>{{ __('Dodajte aplikaciju na početni ekran radi bržeg pristupa i rada u punom ekranu.') }}</p>
        </div>
        <div class="flex shrink-0 items-center gap-2">
            <button type="button" class="rounded-full px-3 py-2 text-sm font-medium text-slate-500 transition hover:text-slate-900" data-install-dismiss>
                {{ __('Kasnije') }}
            </button>
            <button type="button" class="inline-flex min-h-11 items-center justify-center rounded-full bg-[var(--komsije-primary)] px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-blue-600/20 transition hover:bg-blue-700" data-install-action>
                {{ __('Install') }}
            </button>
        </div>
    </div>
</div>