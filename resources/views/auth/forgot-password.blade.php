<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ __('Forgot password') }} | Komšije</title>
        @include('partials.pwa-head')
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen font-sans text-slate-900 antialiased" data-app-shell="standalone">
        <div class="relative isolate min-h-screen overflow-x-hidden">
            <div class="pointer-events-none absolute inset-x-0 top-0 h-72 bg-[radial-gradient(circle_at_top,rgba(37,99,235,0.18),transparent_58%)]"></div>

            <div class="relative mx-auto flex min-h-screen max-w-7xl flex-col px-4 py-4 sm:px-6 lg:px-8">
                <header class="komsije-surface mb-6 rounded-[2rem] px-4 py-4 sm:px-6 sm:py-5">
                    <div class="flex items-center gap-3">
                        <a href="{{ url('/') }}" class="flex items-center gap-3">
                            <img src="{{ asset('icons/logo-icon-v3.svg') }}" alt="" class="h-10 w-10 shrink-0 rounded-2xl" width="40" height="40">
                            <span>
                                <span class="block text-xl font-semibold text-[var(--komsije-dark)]">Komšije</span>
                                <span class="block text-sm text-slate-500">{{ __('Sve u vezi zgrade, na jednom mestu.') }}</span>
                            </span>
                        </a>
                    </div>
                </header>

                <main class="flex flex-1 items-center justify-center py-8">
                    <div class="w-full max-w-md">
                        <section class="komsije-surface rounded-[2rem] p-6 sm:p-8">
                            <p class="inline-flex rounded-full bg-blue-50 px-4 py-1 text-xs font-semibold uppercase tracking-[0.24em] text-[var(--komsije-primary)]">{{ __('Password reset') }}</p>
                            <h1 class="mt-4 text-2xl font-semibold text-[var(--komsije-dark)]">{{ __('Forgot your password?') }}</h1>
                            <p class="mt-3 text-sm leading-6 text-slate-600">{{ __('Enter the email address associated with your account and we will send you a link to reset your password.') }}</p>

                            @if (session('status'))
                                <div class="mt-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-800">
                                    {{ session('status') }}
                                </div>
                            @endif

                            <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-5">
                                @csrf

                                <div>
                                    <label for="email" class="mb-2 block text-sm font-medium text-slate-700">{{ __('Email') }}</label>
                                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                                        class="komsije-input w-full rounded-2xl px-4 py-3 text-slate-950 transition">
                                    @error('email')
                                        <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <button type="submit"
                                    class="inline-flex w-full items-center justify-center rounded-2xl bg-[var(--komsije-primary)] px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-blue-600/20 transition hover:bg-blue-700">
                                    {{ __('Send reset link') }}
                                </button>
                            </form>

                            <p class="mt-6 text-center text-sm text-slate-600">
                                <a href="{{ route('login') }}" class="font-medium text-[var(--komsije-primary)] hover:underline">{{ __('Back to login') }}</a>
                            </p>
                        </section>
                    </div>
                </main>
            </div>
        </div>
    </body>
</html>
