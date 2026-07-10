<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <title>{{ __('Stranica nije pronađena') }} | Komšije</title>
        @include('partials.pwa-head')
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=space-grotesk:500,600,700|manrope:400,500,600" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[linear-gradient(135deg,#0f172a_0%,#0e7490_45%,#f59e0b_100%)] text-slate-900 antialiased">
        <div class="mx-auto flex min-h-screen max-w-3xl items-center px-4 py-8 sm:px-6 lg:px-8">
            <section class="w-full rounded-[2rem] border border-white/30 bg-white/92 p-8 shadow-2xl shadow-slate-950/30 backdrop-blur sm:p-10">
                <p class="inline-flex rounded-full bg-amber-100 px-4 py-1 text-xs font-semibold uppercase tracking-[0.22em] text-amber-900">Komšije</p>
                <h1 class="mt-5 font-['Space_Grotesk'] text-4xl font-semibold text-slate-950">{{ __('QR link nije važeći') }}</h1>
                <p class="mt-4 text-base leading-7 text-slate-600">{{ __('Link je istekao ili ne postoji. Zamolite upravnika zgrade da vam podeli važeći QR kod za prijavu.') }}</p>

                <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                    <a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">{{ __('Idi na prijavu') }}</a>
                    <a href="{{ url('/') }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">{{ __('Početna') }}</a>
                </div>
            </section>
        </div>
    </body>
</html>
