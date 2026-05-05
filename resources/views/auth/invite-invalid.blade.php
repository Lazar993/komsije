<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <title>{{ __('Poziv nije dostupan') }} | Komšije</title>
        @include('partials.pwa-head')
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[linear-gradient(135deg,#1f2937_0%,#0f766e_42%,#f59e0b_100%)] text-slate-950 antialiased">
        <div class="relative isolate min-h-screen overflow-hidden">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(251,191,36,0.3),transparent_22%),radial-gradient(circle_at_bottom_right,rgba(255,255,255,0.18),transparent_38%)]"></div>

            <div class="relative mx-auto flex min-h-screen max-w-3xl items-center px-4 py-10 sm:px-6 lg:px-8">
                <section class="w-full rounded-[2rem] border border-slate-200/70 bg-white/94 p-8 shadow-2xl shadow-slate-950/20 backdrop-blur sm:p-10">
                    <p class="inline-flex rounded-full border border-amber-200 bg-amber-50 px-4 py-1 text-xs font-semibold uppercase tracking-[0.24em] text-amber-900">Komšije</p>
                    <h1 class="mt-6 text-4xl font-semibold tracking-tight text-slate-950">{{ __('Ovaj poziv više nije dostupan') }}</h1>
                    <p class="mt-4 text-base leading-7 text-slate-600">{{ __('Link je istekao, već je iskorišćen ili nije važeći. Ako vam je i dalje potreban pristup, zatražite novi poziv od upravnika zgrade.') }}</p>

                    @if ($invite !== null)
                        <div class="mt-8 rounded-3xl border border-slate-200 bg-slate-50 p-5">
                            <dl class="space-y-3 text-sm text-slate-700">
                                <div class="flex items-center justify-between gap-4">
                                    <dt>{{ __('Zgrada') }}</dt>
                                    <dd class="font-semibold text-slate-950">{{ $invite->building?->name ?? __('Nepoznato') }}</dd>
                                </div>
                                <div class="flex items-center justify-between gap-4">
                                    <dt>{{ __('Stan') }}</dt>
                                    <dd class="font-semibold text-slate-950">{{ $invite->apartment?->number ?? __('N/A') }}</dd>
                                </div>
                                <div class="flex items-center justify-between gap-4">
                                    <dt>{{ __('Email') }}</dt>
                                    <dd class="font-semibold text-slate-950">{{ $invite->email }}</dd>
                                </div>
                            </dl>
                        </div>
                    @endif

                    <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                        <a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-2xl bg-slate-950 px-5 py-3 text-sm font-semibold text-white transition hover:bg-teal-900">{{ __('Idi na prijavu') }}</a>
                        <a href="{{ url('/') }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-400 hover:bg-slate-50">{{ __('Početna strana') }}</a>
                    </div>
                </section>
            </div>

            @include('partials.install-prompt')
        </div>
    </body>
</html>