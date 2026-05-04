@php
    /** @var \Illuminate\Support\Collection<int, \App\Models\Page> $footerPages */
    $footerPages = \App\Models\Page::query()
        ->published()
        ->orderBy('title')
        ->get(['id', 'title', 'slug']);
    $year = now()->year;
@endphp

<footer class="bg-[var(--komsije-dark)] text-slate-200">
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        {{-- Desktop / tablet layout --}}
        <div class="hidden gap-10 md:flex md:items-start md:justify-between">
            <div class="max-w-sm">
                <a href="{{ url('/') }}" class="flex items-center gap-3">
                    <img src="{{ asset('icons/logo-icon-v3.svg') }}" alt="" class="h-10 w-10 rounded-2xl bg-white/5" width="40" height="40">
                    <span class="text-xl font-semibold text-white">Komšije</span>
                </a>
                <p class="mt-4 text-sm leading-relaxed text-slate-400">
                    {{ __('Sve u vezi zgrade, na jednom mestu. Upravljajte kvarovima, obaveštenjima i komšijama jednostavno.') }}
                </p>
            </div>

            @if ($footerPages->isNotEmpty())
                <nav aria-label="{{ __('Footer') }}" class="flex flex-col gap-2 text-sm">
                    <span class="mb-1 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">{{ __('Informacije') }}</span>
                    @foreach ($footerPages as $page)
                        <a href="{{ url('/page/'.$page->slug) }}" class="text-slate-300 transition hover:text-white">
                            {{ $page->title }}
                        </a>
                    @endforeach
                </nav>
            @endif
        </div>

        {{-- Mobile layout: stacked, centered --}}
        <div class="flex flex-col items-center gap-6 text-center md:hidden">
            <a href="{{ url('/') }}" class="flex items-center gap-2">
                <img src="{{ asset('icons/logo-icon-v3.svg') }}" alt="" class="h-9 w-9 rounded-2xl bg-white/5" width="36" height="36">
                <span class="text-lg font-semibold text-white">Komšije</span>
            </a>
            @if ($footerPages->isNotEmpty())
                <nav aria-label="{{ __('Footer') }}" class="flex flex-wrap items-center justify-center gap-x-5 gap-y-2 text-sm">
                    @foreach ($footerPages as $page)
                        <a href="{{ url('/page/'.$page->slug) }}" class="text-slate-300 transition hover:text-white">
                            {{ $page->title }}
                        </a>
                    @endforeach
                </nav>
            @endif
        </div>

        <div class="mt-8 border-t border-white/10 pt-5 text-center text-xs text-slate-500 md:text-left">
            &copy; {{ $year }} Komšije. {{ __('Sva prava zadržana.') }}
        </div>
    </div>
</footer>
