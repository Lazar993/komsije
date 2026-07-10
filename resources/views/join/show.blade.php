<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <title>{{ __('Pridružite se zgradi') }} | Komšije</title>
        @include('partials.pwa-head')
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=space-grotesk:400,500,600,700|manrope:400,500,600,700" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[radial-gradient(circle_at_top_left,#fef3c7_0%,#f8fafc_35%,#e0f2fe_100%)] text-slate-900 antialiased">
        <div class="mx-auto max-w-6xl px-4 py-6 sm:px-6 lg:px-8">
            <header class="mb-6 rounded-[2rem] border border-white/70 bg-white/80 px-5 py-4 shadow-lg shadow-sky-100/40 backdrop-blur sm:px-7">
                <div class="flex items-center justify-between gap-4">
                    <a href="{{ url('/') }}" class="flex items-center gap-3">
                        <img src="{{ asset('icons/logo-icon-v3.svg') }}" alt="" class="h-10 w-10 rounded-2xl" width="40" height="40">
                        <div>
                            <p class="font-['Space_Grotesk'] text-xl font-semibold leading-tight text-slate-900">Komšije</p>
                            <p class="text-sm text-slate-600">{{ __('Onboarding za stanare') }}</p>
                        </div>
                    </a>
                    @include('partials.language-switcher', ['compact' => true])
                </div>
            </header>

            <main class="grid gap-6 lg:grid-cols-[1.05fr_0.95fr]">
                <section class="rounded-[2rem] border border-slate-200/70 bg-white/85 p-6 shadow-xl shadow-cyan-100/30 backdrop-blur sm:p-8 lg:p-10">
                    <p class="inline-flex rounded-full bg-cyan-50 px-4 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-cyan-700">{{ __('Komšije Join') }}</p>
                    <h1 class="mt-5 font-['Space_Grotesk'] text-4xl font-semibold leading-tight text-slate-950 sm:text-5xl">{{ __('Pridružite se vašoj zgradi') }}</h1>
                    <p class="mt-4 max-w-xl text-base leading-7 text-slate-600">{{ __('Skenirali ste onboarding QR kod za zgradu :building. Popunite kratku prijavu i upravnik će je odobriti.', ['building' => $building->name]) }}</p>

                    <div class="mt-8 rounded-3xl border border-slate-200 bg-slate-50/70 p-5">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-cyan-700">{{ __('Zgrada') }}</p>
                        <p class="mt-2 text-lg font-semibold text-slate-900">{{ $building->name }}</p>
                    </div>

                    <ul class="mt-8 space-y-3 text-sm text-slate-700">
                        <li class="flex items-start gap-3"><span class="mt-0.5 text-cyan-700">✓</span><span>{{ __('Trenutno ne kreiramo korisnički nalog dok upravnik ne odobri zahtev.') }}</span></li>
                        <li class="flex items-start gap-3"><span class="mt-0.5 text-cyan-700">✓</span><span>{{ __('Proces traje manje od jednog minuta.') }}</span></li>
                        <li class="flex items-start gap-3"><span class="mt-0.5 text-cyan-700">✓</span><span>{{ __('Komšije rade na Android i iPhone uređajima.') }}</span></li>
                    </ul>
                </section>

                <section class="rounded-[2rem] border border-slate-200/70 bg-white p-6 shadow-xl shadow-sky-100/30 sm:p-8">
                    <h2 class="font-['Space_Grotesk'] text-2xl font-semibold text-slate-950">{{ __('Kratka prijava') }}</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600">{{ __('Nakon odobrenja dobićete postojeći Komšije poziv na e-mail i nastaviti standardnu registraciju.') }}</p>

                    @if (session('status'))
                        <div class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                            {{ session('status') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('join.store', ['token' => $token]) }}" class="mt-6 space-y-4">
                        @csrf
                        <input type="text" name="company" value="" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true">

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="first_name" class="mb-2 block text-sm font-medium text-slate-700">{{ __('First Name') }}</label>
                                <input id="first_name" name="first_name" type="text" value="{{ old('first_name') }}" required class="komsije-input w-full rounded-2xl px-4 py-3">
                                @error('first_name')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="last_name" class="mb-2 block text-sm font-medium text-slate-700">{{ __('Last Name') }}</label>
                                <input id="last_name" name="last_name" type="text" value="{{ old('last_name') }}" required class="komsije-input w-full rounded-2xl px-4 py-3">
                                @error('last_name')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div>
                            <label for="apartment_number" class="mb-2 block text-sm font-medium text-slate-700">{{ __('Apartment Number') }}</label>
                            <input id="apartment_number" name="apartment_number" type="text" value="{{ old('apartment_number') }}" required class="komsije-input w-full rounded-2xl px-4 py-3">
                            @error('apartment_number')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="email" class="mb-2 block text-sm font-medium text-slate-700">{{ __('Email Address') }}</label>
                            <input id="email" name="email" type="email" value="{{ old('email') }}" required class="komsije-input w-full rounded-2xl px-4 py-3">
                            @error('email')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="phone" class="mb-2 block text-sm font-medium text-slate-700">{{ __('Phone Number (optional)') }}</label>
                            <input id="phone" name="phone" type="text" value="{{ old('phone') }}" class="komsije-input w-full rounded-2xl px-4 py-3">
                            @error('phone')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                        </div>

                        <div class="mt-2 rounded-2xl border border-cyan-100 bg-cyan-50/60 px-4 py-3">
                            <div class="flex items-start gap-3">
                                <input id="privacy_accepted" type="checkbox" name="privacy_accepted" value="1" class="mt-1 h-4 w-4 rounded border-slate-300 text-cyan-700" @checked(old('privacy_accepted'))>
                                <div>
                                    <label for="privacy_accepted" class="block text-sm font-medium text-slate-800">
                                        {{ __('I have read and accept the Privacy Policy.') }}
                                        <a href="{{ route('pages.show', ['slug' => 'politika-privatnosti']) }}" target="_blank" rel="noopener" class="font-semibold text-cyan-700 underline decoration-cyan-300 underline-offset-2 hover:text-cyan-800">
                                            {{ __('Open Privacy Policy') }}
                                        </a>
                                        <span class="text-slate-500">{{ __('(opens in new tab)') }}</span>
                                    </label>
                                    <p class="mt-1 text-xs leading-5 text-slate-600">
                                        {{ __('We use this data only to process your request, verify your apartment, and send onboarding updates.') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        @error('privacy_accepted')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror

                        <button type="submit" class="mt-3 inline-flex w-full items-center justify-center rounded-2xl bg-cyan-700 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-cyan-700/20 transition hover:bg-cyan-800">
                            {{ __('Pošalji prijavu') }}
                        </button>
                    </form>
                </section>
            </main>
        </div>
    </body>
</html>
