<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ __('Prihvatanje poziva') }} | Komšije</title>
        @include('partials.pwa-head')
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[linear-gradient(135deg,#083344_0%,#164e63_40%,#f4a261_100%)] text-slate-950 antialiased">
        <div class="relative isolate min-h-screen overflow-hidden">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(250,204,21,0.32),transparent_24%),radial-gradient(circle_at_bottom_right,rgba(255,255,255,0.2),transparent_36%)]"></div>

            <div class="relative mx-auto flex min-h-screen max-w-6xl items-center px-4 py-10 sm:px-6 lg:grid lg:grid-cols-[1.05fr_0.95fr] lg:gap-10 lg:px-8">
                <section class="hidden rounded-[2rem] border border-white/20 bg-white/10 p-10 text-white shadow-2xl shadow-slate-950/30 backdrop-blur lg:block">
                    <p class="mb-4 inline-flex rounded-full border border-white/25 bg-white/10 px-4 py-1 text-xs font-semibold uppercase tracking-[0.24em] text-amber-100">Komšije</p>
                    <h1 class="max-w-xl text-5xl font-semibold leading-tight">{{ __('Pozvani ste da se pridružite zgradi :building.', ['building' => $invite->building->name]) }}</h1>
                    <p class="mt-6 max-w-xl text-lg leading-8 text-slate-100/90">{{ __('Vaš nalog će biti povezan sa stanom :apartment i automatski dodat u portal za stanare.', ['apartment' => $invite->apartment?->number ?? __('N/A')]) }}</p>

                    <div class="mt-10 rounded-3xl border border-white/20 bg-slate-950/20 p-6">
                        <p class="text-sm uppercase tracking-[0.18em] text-amber-200">{{ __('Detalji poziva') }}</p>
                        <dl class="mt-4 space-y-3 text-sm text-slate-100/85">
                            <div class="flex items-center justify-between gap-4">
                                <dt>{{ __('Zgrada') }}</dt>
                                <dd class="font-semibold text-white">{{ $invite->building->name }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-4">
                                <dt>{{ __('Stan') }}</dt>
                                <dd class="font-semibold text-white">{{ $invite->apartment?->number ?? __('N/A') }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-4">
                                <dt>{{ __('Uloga') }}</dt>
                                <dd class="font-semibold text-white">{{ __('Tenant') }}</dd>
                            </div>
                        </dl>
                    </div>
                </section>

                <section class="w-full rounded-[2rem] border border-slate-200/70 bg-white/92 p-6 shadow-2xl shadow-slate-950/20 backdrop-blur sm:p-8">
                    <div class="mb-8 flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-semibold uppercase tracking-[0.22em] text-cyan-800">{{ __('Invite Registration') }}</p>
                            @if ($hasExistingAccount)
                                <h2 class="mt-3 text-3xl font-semibold text-slate-950">{{ __('Accept invite') }}</h2>
                                <p class="mt-3 text-sm leading-6 text-slate-600">{{ __('An account already exists for :email. Enter your current password to accept the invite and link this building to your existing account.', ['email' => $invite->email]) }}</p>
                            @else
                                <h2 class="mt-3 text-3xl font-semibold text-slate-950">{{ __('Kreirajte svoj nalog') }}</h2>
                                <p class="mt-3 text-sm leading-6 text-slate-600">{{ __('Pozvani ste da se pridružite zgradi :building, stan :apartment.', ['building' => $invite->building->name, 'apartment' => $invite->apartment?->number ?? __('N/A')]) }}</p>
                            @endif
                        </div>

                        @include('partials.language-switcher')
                    </div>

                    <form method="POST" action="{{ route('invite.store', $invite->token) }}" class="space-y-5">
                        @csrf

                        @unless ($hasExistingAccount)
                            <div>
                                <label for="name" class="mb-2 block text-sm font-medium text-slate-700">{{ __('Name') }}</label>
                                <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-slate-950 outline-none transition focus:border-cyan-600 focus:ring-4 focus:ring-cyan-500/15">
                                @error('name')
                                    <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>
                        @endunless

                        <div>
                            <label for="email" class="mb-2 block text-sm font-medium text-slate-700">{{ __('Email') }}</label>
                            <input id="email" name="email" type="email" value="{{ old('email', $invite->email) }}" readonly required class="w-full rounded-2xl border border-slate-300 bg-slate-50 px-4 py-3 text-slate-950 outline-none transition focus:border-cyan-600 focus:ring-4 focus:ring-cyan-500/15">
                            @error('email')
                                <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="password" class="mb-2 block text-sm font-medium text-slate-700">
                                {{ $hasExistingAccount ? __('Your current password') : __('Password') }}
                            </label>
                            <x-password-input id="password" name="password" required autocomplete="new-password" inputClass="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-slate-950 outline-none transition focus:border-cyan-600 focus:ring-4 focus:ring-cyan-500/15" />
                            @error('password')
                                <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        @unless ($hasExistingAccount)
                            <div>
                                <label for="password_confirmation" class="mb-2 block text-sm font-medium text-slate-700">{{ __('Confirm password') }}</label>
                                <x-password-input id="password_confirmation" name="password_confirmation" required autocomplete="new-password" inputClass="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-slate-950 outline-none transition focus:border-cyan-600 focus:ring-4 focus:ring-cyan-500/15" />
                            </div>
                        @else
                            {{-- For existing users the password is not "confirmed" — submit the same value as confirmation --}}
                            <input type="hidden" name="password_confirmation" value="">
                        @endunless

                        <button type="submit" class="inline-flex w-full items-center justify-center rounded-2xl bg-slate-950 px-5 py-3 text-sm font-semibold text-white transition hover:bg-cyan-900">
                            {{ $hasExistingAccount ? __('Accept invite') : __('Join building') }}
                        </button>
                    </form>

                    <div class="mt-8 rounded-3xl border border-cyan-200 bg-cyan-50 p-4 text-sm leading-6 text-cyan-950">
                        <p class="font-semibold">{{ __('Secure onboarding') }}</p>
                        <p class="mt-1">{{ __('Ovaj poziv je vezan za adresu :email i može se iskoristiti samo jednom.', ['email' => $invite->email]) }}</p>
                    </div>
                </section>
            </div>

            @include('partials.install-prompt')
        </div>
    </body>
</html>