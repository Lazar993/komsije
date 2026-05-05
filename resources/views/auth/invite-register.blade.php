<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <title>{{ __('Prihvatanje poziva') }} | Komšije</title>
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
                            <img src="{{ asset('icons/logo-icon-v3.svg') }}" alt="" class="h-10 w-10 shrink-0 rounded-2xl" width="40" height="40">
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
                        @php($inviteRole = \App\Enums\BuildingRole::from((string) $invite->role))
                        <section class="komsije-surface rounded-[2rem] bg-[linear-gradient(180deg,rgba(255,255,255,0.96),rgba(239,246,255,0.96))] p-6 sm:p-8 lg:p-10">
                            <p class="inline-flex rounded-full bg-blue-50 px-4 py-1 text-xs font-semibold uppercase tracking-[0.24em] text-[var(--komsije-primary)]">{{ __('Invite Registration') }}</p>
                            <h1 class="mt-5 max-w-xl text-4xl font-semibold leading-tight text-[var(--komsije-dark)] sm:text-5xl">{{ __('Pozvani ste da se pridružite zgradi :building.', ['building' => $invite->building->name]) }}</h1>
                            <p class="mt-5 max-w-xl text-base leading-7 text-slate-600">
                                {{ $inviteRole === \App\Enums\BuildingRole::Tenant
                                    ? __('Vaš nalog će biti povezan sa stanom :apartment i automatski dodat u portal za stanare.', ['apartment' => $invite->apartment?->number ?? __('N/A')])
                                    : __('Vaš nalog će biti dodat kao administrator ove zgrade i dobićete pristup upravljanju zgradom.') }}
                            </p>

                            <div class="mt-8 grid gap-4">
                                <div class="rounded-[1.5rem] border border-[var(--komsije-border)] bg-white/80 p-5">
                                    <p class="text-sm font-semibold uppercase tracking-[0.18em] text-[var(--komsije-primary)]">{{ __('Building') }}</p>
                                    <p class="mt-2 text-base font-semibold text-[var(--komsije-dark)]">{{ $invite->building->name }}</p>
                                </div>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    @if ($inviteRole === \App\Enums\BuildingRole::Tenant)
                                        <div class="rounded-[1.5rem] border border-[var(--komsije-border)] bg-white/80 p-5">
                                            <p class="text-sm font-semibold uppercase tracking-[0.18em] text-[var(--komsije-primary)]">{{ __('Apartment') }}</p>
                                            <p class="mt-2 text-base font-semibold text-[var(--komsije-dark)]">{{ $invite->apartment?->number ?? __('N/A') }}</p>
                                        </div>
                                    @else
                                        <div class="rounded-[1.5rem] border border-[var(--komsije-border)] bg-white/80 p-5">
                                            <p class="text-sm font-semibold uppercase tracking-[0.18em] text-[var(--komsije-primary)]">{{ __('Access') }}</p>
                                            <p class="mt-2 text-base font-semibold text-[var(--komsije-dark)]">{{ __('Building administration') }}</p>
                                        </div>
                                    @endif
                                    <div class="rounded-[1.5rem] border border-[var(--komsije-border)] bg-white/80 p-5">
                                        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-[var(--komsije-primary)]">{{ __('Role') }}</p>
                                        <p class="mt-2 text-base font-semibold text-[var(--komsije-dark)]">{{ __($inviteRole->label()) }}</p>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section class="komsije-surface rounded-[2rem] p-6 sm:p-8">
                            <div class="mb-8">
                                <p class="text-sm font-semibold uppercase tracking-[0.22em] text-[var(--komsije-primary)]">{{ __('Building Portal') }}</p>
                                @if ($hasExistingAccount)
                                    <h2 class="mt-3 text-3xl font-semibold text-[var(--komsije-dark)]">{{ __('Accept invite') }}</h2>
                                    <p class="mt-3 text-sm leading-6 text-slate-600">{{ __('An account already exists for :email. Enter your current password to accept the invite and link this building to your existing account.', ['email' => $invite->email]) }}</p>
                                @else
                                    <h2 class="mt-3 text-3xl font-semibold text-[var(--komsije-dark)]">{{ __('Kreirajte svoj nalog') }}</h2>
                                    <p class="mt-3 text-sm leading-6 text-slate-600">
                                        {{ $inviteRole === \App\Enums\BuildingRole::Tenant
                                            ? __('Pozvani ste da se pridružite zgradi :building, stan :apartment.', ['building' => $invite->building->name, 'apartment' => $invite->apartment?->number ?? __('N/A')])
                                            : __('Pozvani ste da se pridružite zgradi :building kao administrator.', ['building' => $invite->building->name]) }}
                                    </p>
                                @endif
                            </div>

                            <form method="POST" action="{{ route('invite.store', $invite->token) }}" class="space-y-5">
                                @csrf

                                @unless ($hasExistingAccount)
                                    <div>
                                        <label for="name" class="mb-2 block text-sm font-medium text-slate-700">{{ __('Name') }}</label>
                                        <input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus class="komsije-input w-full rounded-2xl px-4 py-3 text-slate-950 transition">
                                        @error('name')
                                            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                @endunless

                                <div>
                                    <label for="email" class="mb-2 block text-sm font-medium text-slate-700">{{ __('Email') }}</label>
                                    <input id="email" name="email" type="email" value="{{ old('email', $invite->email) }}" readonly required class="komsije-input w-full rounded-2xl bg-slate-50 px-4 py-3 text-slate-950 transition">
                                    @error('email')
                                        <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="password" class="mb-2 block text-sm font-medium text-slate-700">
                                        {{ $hasExistingAccount ? __('Your current password') : __('Password') }}
                                    </label>
                                    <x-password-input id="password" name="password" required autocomplete="new-password" />
                                    @error('password')
                                        <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                @unless ($hasExistingAccount)
                                    <div>
                                        <label for="password_confirmation" class="mb-2 block text-sm font-medium text-slate-700">{{ __('Confirm password') }}</label>
                                        <x-password-input id="password_confirmation" name="password_confirmation" required autocomplete="new-password" />
                                    </div>
                                @else
                                    <input type="hidden" name="password_confirmation" value="">
                                @endunless

                                <button type="submit" class="inline-flex w-full items-center justify-center rounded-2xl bg-[var(--komsije-primary)] px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-blue-600/20 transition hover:bg-blue-700">
                                    {{ $hasExistingAccount ? __('Accept invite') : __('Join building') }}
                                </button>
                            </form>

                            <div class="mt-8 rounded-[1.5rem] border border-blue-100 bg-blue-50 px-5 py-4 text-sm leading-6 text-slate-700">
                                <p class="font-semibold text-[var(--komsije-dark)]">{{ __('Secure onboarding') }}</p>
                                <p class="mt-1">{{ __('Ovaj poziv je vezan za adresu :email i može se iskoristiti samo jednom.', ['email' => $invite->email]) }}</p>
                            </div>
                        </section>
                    </div>
                </main>

                @include('partials.install-prompt')
            </div>
        </div>
    </body>
</html>