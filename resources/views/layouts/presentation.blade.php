<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>@yield('title', 'Komšije')</title>
    @hasSection('meta_description')
        <meta name="description" content="@yield('meta_description')">
    @endif
    @yield('head')
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[var(--komsije-background)] font-sans text-slate-900 antialiased">
    <div class="flex min-h-screen flex-col">
        <header class="border-b border-[var(--komsije-border)] bg-white/80 backdrop-blur">
            <div class="mx-auto flex max-w-7xl items-center justify-between gap-3 px-4 py-4 sm:px-6 lg:px-8">
                <a href="{{ route('pages.professionals') }}" class="flex items-center gap-3">
                    <img src="{{ asset('icons/logo-icon-v3.svg') }}" alt="" class="h-9 w-9 rounded-2xl" width="36" height="36">
                    <span class="text-lg font-semibold text-[var(--komsije-dark)]">Komšije</span>
                </a>
                @include('partials.language-switcher', ['compact' => true, 'mobileLabelHidden' => true])
            </div>
        </header>

        <main class="flex-1">
            @yield('content')
        </main>

        <footer class="bg-[var(--komsije-dark)] text-slate-200">
            <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
                <div class="flex flex-col items-start justify-between gap-8 md:flex-row md:items-center">
                    <div class="max-w-sm">
                        <div class="flex items-center gap-3">
                            <img src="{{ asset('icons/logo-icon-v3.svg') }}" alt="" class="h-10 w-10 rounded-2xl bg-white/5" width="40" height="40">
                            <span class="text-xl font-semibold text-white">Komšije</span>
                        </div>
                        <p class="mt-4 text-sm leading-relaxed text-slate-400">
                            {{ __('Platforma za profesionalne upravnike zgrada.') }}
                        </p>
                    </div>

                    @isset($contactUrl)
                        <a href="{{ $contactUrl }}" class="inline-flex items-center gap-2 rounded-2xl bg-white/10 px-5 py-3 text-sm font-semibold text-white transition hover:bg-white/15">
                            {{ __('Kontaktirajte nas') }}
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M7.22 4.22a.75.75 0 0 1 1.06 0l5.25 5.25a.75.75 0 0 1 0 1.06l-5.25 5.25a.75.75 0 1 1-1.06-1.06L11.94 10 7.22 5.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                            </svg>
                        </a>
                    @endisset
                </div>

                <div class="mt-8 border-t border-white/10 pt-5 text-center text-xs text-slate-500 md:text-left">
                    &copy; {{ now()->year }} T&B Solutions. {{ __('Sva prava zadržana.') }}
                </div>
            </div>
        </footer>
    </div>
</body>
</html>
