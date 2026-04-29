<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ __('Portal Login') }}</title>
        @include('partials.pwa-head')
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen font-sans text-slate-900 antialiased overscroll-y-none" data-app-shell="standalone">
        <div class="relative isolate min-h-screen overflow-x-hidden">
            <div class="pointer-events-none absolute inset-x-0 top-0 h-72 bg-[radial-gradient(circle_at_top,rgba(37,99,235,0.18),transparent_58%)]"></div>

            <div class="relative mx-auto flex min-h-screen max-w-7xl flex-col px-4 py-4 sm:px-6 lg:px-8">
                <header class="komsije-surface mb-6 rounded-[2rem] px-4 py-4 sm:px-6 sm:py-5">
                    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <a href="{{ url('/') }}" class="flex min-w-0 items-center gap-3">
                            <img src="{{ asset('icons/logo-icon-v2.svg') }}" alt="" class="h-10 w-10 shrink-0 rounded-2xl" width="40" height="40">
                            <span class="min-w-0">
                                <span class="block truncate text-xl font-semibold text-[var(--komsije-dark)]">Komšije</span>
                                <span class="block text-sm text-slate-500">{{ __('Sve u vezi zgrade, na jednom mestu.') }}</span>
                            </span>
                        </a>

                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                            @include('partials.language-switcher', ['compact' => true])
                        </div>
                    </div>
                </header>

                <main class="flex flex-1 items-center py-4 md:py-8">
                    <div class="grid w-full gap-6 lg:grid-cols-[1.1fr_0.9fr] xl:gap-8">
                        <section class="komsije-surface rounded-[2rem] bg-[linear-gradient(180deg,rgba(255,255,255,0.96),rgba(239,246,255,0.96))] p-6 sm:p-8 lg:p-10">
                            <p class="inline-flex rounded-full bg-blue-50 px-4 py-1 text-xs font-semibold uppercase tracking-[0.24em] text-[var(--komsije-primary)]">{{ __('Resident Portal') }}</p>
                            <h1 class="mt-5 max-w-xl text-4xl font-semibold leading-tight text-[var(--komsije-dark)] sm:text-5xl">{{ __('Manage buildings, residents, tickets, and notices from one place.') }}</h1>
                            <p class="mt-5 max-w-xl text-base leading-7 text-slate-600">{{ __('This portal is now the main web surface for property managers and tenants. The API remains available for the later mobile app, but daily work should happen here first.') }}</p>

                            <div class="mt-8 grid gap-4">
                                <div class="rounded-[1.5rem] border border-[var(--komsije-border)] bg-white/80 p-5">
                                    <p class="text-sm font-semibold uppercase tracking-[0.18em] text-[var(--komsije-primary)]">{{ __('Tickets') }}</p>
                                    <p class="mt-2 text-sm leading-6 text-slate-600">{{ __('Track reported issues, comments, assignments, and resolution status.') }}</p>
                                </div>
                                <div class="rounded-[1.5rem] border border-[var(--komsije-border)] bg-white/80 p-5">
                                    <p class="text-sm font-semibold uppercase tracking-[0.18em] text-[var(--komsije-primary)]">{{ __('Announcements') }}</p>
                                    <p class="mt-2 text-sm leading-6 text-slate-600">{{ __('Publish operational updates, drafts, and read confirmations per building.') }}</p>
                                </div>
                            </div>
                        </section>

                        <section class="komsije-surface rounded-[2rem] p-6 sm:p-8">
                            <div class="mb-8">
                                <p class="text-sm font-semibold uppercase tracking-[0.22em] text-[var(--komsije-primary)]">{{ __('Resident Portal') }}</p>
                                <h2 class="mt-3 text-3xl font-semibold text-[var(--komsije-dark)]">{{ __('Sign in to the portal') }}</h2>
                                <p class="mt-3 text-sm leading-6 text-slate-600">{{ __('Use your building account to access tickets, announcements, and day-to-day operations.') }}</p>
                            </div>

                            <form method="POST" action="{{ route('login.store') }}" class="space-y-5">
                                @csrf

                                <div>
                                    <label for="email" class="mb-2 block text-sm font-medium text-slate-700">{{ __('Email') }}</label>
                                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus class="komsije-input w-full rounded-2xl px-4 py-3 text-slate-950 transition">
                                    @error('email')
                                        <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <div class="mb-2 flex items-center justify-between gap-2">
                                        <label for="password" class="block text-sm font-medium text-slate-700">{{ __('Password') }}</label>
                                        <a href="{{ route('password.request') }}" class="text-xs font-medium text-[var(--komsije-primary)] hover:underline">{{ __('Forgot password?') }}</a>
                                    </div>
                                    <x-password-input id="password" name="password" required autocomplete="current-password" />
                                    @error('password')
                                        <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <label class="flex items-center gap-3 text-sm text-slate-600">
                                    <input type="checkbox" name="remember" value="1" @checked(old('remember')) class="h-4 w-4 rounded border-slate-300 text-[var(--komsije-primary)] focus:ring-[var(--komsije-primary)]">
                                    {{ __('Keep me signed in on this browser') }}
                                </label>

                                <button type="submit" class="inline-flex w-full items-center justify-center rounded-2xl bg-[var(--komsije-primary)] px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-blue-600/20 transition hover:bg-blue-700">{{ __('Open portal') }}</button>
                            </form>

                            <div class="mt-6 rounded-[1.5rem] border border-blue-100 bg-blue-50 px-5 py-4 text-sm leading-6 text-slate-700">
                                <p class="font-semibold text-[var(--komsije-dark)]">{{ __('Demo accounts') }}</p>
                                <p class="mt-2">admin@upravnik.test</p>
                                <p>manager@upravnik.test</p>
                                <p>tenant@upravnik.test</p>
                                <p class="mt-2 font-medium text-[var(--komsije-primary)]">{{ __('Password: password') }}</p>
                            </div>
                        </section>
                    </div>
                </main>

                @include('partials.install-prompt')
            </div>
        </div>
    </body>
</html>