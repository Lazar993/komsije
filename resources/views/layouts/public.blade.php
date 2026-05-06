<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>@yield('title', 'Komšije')</title>
    @hasSection('meta_description')
        <meta name="description" content="@yield('meta_description')">
    @endif
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[var(--komsije-background)] font-sans text-slate-900 antialiased">
    <div class="flex min-h-screen flex-col">
        <header class="border-b border-[var(--komsije-border)] bg-white/80 backdrop-blur">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
                <a href="{{ url('/') }}" class="flex items-center gap-3">
                    <img src="{{ asset('icons/logo-icon-v3.svg') }}" alt="" class="h-9 w-9 rounded-2xl" width="36" height="36">
                    <span class="text-lg font-semibold text-[var(--komsije-dark)]">Komšije</span>
                </a>
                @auth
                    <a href="{{ route('portal.dashboard') }}" class="rounded-2xl bg-[var(--komsije-primary)] px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-700">
                        {{ __('Portal') }}
                    </a>
                @else
                    <a href="{{ route('login') }}" class="rounded-2xl border border-[var(--komsije-border)] bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:border-blue-200 hover:text-[var(--komsije-primary)]">
                        {{ __('Prijava') }}
                    </a>
                @endauth
            </div>
        </header>

        <main class="flex-1">
            @yield('content')
        </main>

        @hasSection('hide_footer')
        @else
            <x-site-footer />
        @endif
    </div>
</body>
</html>
