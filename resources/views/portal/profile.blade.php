@extends('portal.layouts.app')

@section('title', __('Profil'))

@section('content')
    @php
        $user = auth()->user();
        $canCreateTicket = $profileBuilding !== null && $user->can('create', [App\Models\Ticket::class, $profileBuilding]);
        $initials = collect(explode(' ', (string) $user->name))
            ->filter()
            ->take(2)
            ->map(fn (string $segment): string => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($segment, 0, 1)))
            ->implode('');
        $recentTicketTone = match ($recentTicket?->status?->value) {
            'resolved' => 'success',
            'in_progress' => 'warning',
            default => 'primary',
        };
    @endphp

    <section class="grid gap-6 xl:grid-cols-[minmax(0,1.25fr)_23rem]">
        <article class="overflow-hidden rounded-[2rem] bg-[var(--komsije-dark)] px-6 py-7 text-white shadow-[0_28px_80px_-32px_rgba(15,23,42,0.85)] sm:px-8 sm:py-8">
            <div class="flex flex-col gap-6">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0 flex-1">
                        <span class="inline-flex w-fit rounded-full bg-white/10 px-3 py-1 text-xs font-medium uppercase tracking-[0.22em] text-blue-100">{{ __('Moj profil') }}</span>
                        <div class="mt-4 flex items-center gap-4">
                            @if ($user->profileImageUrl())
                                <img src="{{ $user->profileImageUrl() }}" alt="{{ $user->name }}" class="h-16 w-16 shrink-0 rounded-[1.75rem] object-cover ring-1 ring-white/15">
                            @else
                                <span class="flex h-16 w-16 shrink-0 items-center justify-center rounded-[1.75rem] bg-white/10 text-xl font-semibold text-white">{{ $initials !== '' ? $initials : 'K' }}</span>
                            @endif
                            <div class="min-w-0">
                                <h1 class="truncate text-3xl font-bold tracking-tight sm:text-4xl">{{ $user->name }}</h1>
                                <p class="mt-2 text-sm text-slate-300">{{ $user->email }}</p>
                                <p class="mt-2 text-sm text-slate-200">
                                    {{ $profileApartment ? __('Stan :number, sprat :floor', ['number' => $profileApartment->number, 'floor' => $profileApartment->floor]) : __('Stan nije povezan sa nalogom.') }}
                                    @if ($profileBuilding)
                                        <span class="text-slate-400">·</span>
                                        {{ $profileBuilding->name }}
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>

                    @if ($canCreateTicket)
                        <a href="{{ route('portal.tickets.create') }}" class="inline-flex items-center justify-center gap-2 rounded-[1.25rem] bg-[var(--komsije-primary)] px-5 py-4 text-base font-medium text-white shadow-lg shadow-blue-600/30 transition hover:bg-blue-700">
                            <x-portal.app-icon name="plus" class="h-5 w-5" />
                            <span>{{ __('Prijavi kvar') }}</span>
                        </a>
                    @endif
                </div>

                @if ($profileBuilding)
                    <div class="rounded-[1.5rem] border border-white/10 bg-white/6 p-5">
                        <p class="text-xs uppercase tracking-[0.22em] text-slate-300">{{ __('Moja zgrada') }}</p>
                        <h2 class="mt-3 text-xl font-semibold text-white">{{ $profileBuilding->name }}</h2>
                        <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-300">{{ $profileBuilding->address }}</p>
                    </div>
                @else
                    <div class="rounded-[1.5rem] border border-white/10 bg-white/6 p-5 text-sm leading-6 text-slate-300">
                        {{ __('Trenutno nema aktivne zgrade za ovaj nalog. Čim nalog bude povezan sa zgradom, ovde će se pojaviti pregled aktivnosti i obaveštenja.') }}
                    </div>
                @endif
            </div>
        </article>

        <aside class="komsije-surface rounded-[2rem] p-6 sm:p-7">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold text-[var(--komsije-primary)]">{{ __('Aktivnost') }}</p>
                    <h2 class="mt-2 text-2xl font-semibold tracking-tight text-slate-950">{{ __('Brzi pregled') }}</h2>
                </div>
                <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-medium text-[var(--komsije-primary)]">{{ now()->translatedFormat('d M') }}</span>
            </div>

            <div class="mt-5 space-y-3">
                <div class="rounded-[1.5rem] bg-slate-50 p-4">
                    <p class="text-sm font-medium text-slate-500">{{ __('Aktivni kvarovi') }}</p>
                    <p class="mt-1 text-3xl font-semibold text-slate-950">{{ $ticketStats['active'] }}</p>
                </div>
                <div class="rounded-[1.5rem] bg-slate-50 p-4">
                    <p class="text-sm font-medium text-slate-500">{{ __('Poslednja prijava') }}</p>
                    <p class="mt-1 text-base font-semibold text-slate-950">{{ $recentTicket?->title ?? __('Još nema prijavljenih kvarova') }}</p>
                    <p class="mt-1 text-sm text-slate-500">{{ $recentTicket?->created_at?->diffForHumans() ?? __('Prijavite prvi kvar kada bude potrebno.') }}</p>
                </div>
                <div class="rounded-[1.5rem] bg-slate-50 p-4">
                    <p class="text-sm font-medium text-slate-500">{{ __('Nepročitana obaveštenja') }}</p>
                    <p class="mt-1 text-3xl font-semibold text-slate-950">{{ $recentUnreadAnnouncementsCount }}</p>
                </div>
            </div>
        </aside>
    </section>

    <section class="mt-6 grid gap-4 sm:grid-cols-3">
        <article class="komsije-surface rounded-[1.75rem] p-5">
            <p class="text-sm font-medium text-slate-500">{{ __('Aktivni kvarovi') }}</p>
            <p class="mt-3 text-3xl font-semibold text-slate-950">{{ $ticketStats['active'] }}</p>
        </article>
        <article class="komsije-surface rounded-[1.75rem] p-5">
            <p class="text-sm font-medium text-slate-500">{{ __('Rešeni kvarovi') }}</p>
            <p class="mt-3 text-3xl font-semibold text-emerald-600">{{ $ticketStats['resolved'] }}</p>
        </article>
        <article class="komsije-surface rounded-[1.75rem] p-5">
            <p class="text-sm font-medium text-slate-500">{{ __('Ukupno prijava') }}</p>
            <p class="mt-3 text-3xl font-semibold text-slate-950">{{ $ticketStats['total'] }}</p>
        </article>
    </section>

    <section class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
        <article class="komsije-surface rounded-[2rem] p-6 sm:p-7">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold text-[var(--komsije-primary)]">{{ __('Poslednji kvar') }}</p>
                    <h2 class="mt-1 text-2xl font-semibold tracking-tight text-slate-950">{{ __('Najskorija prijava') }}</h2>
                </div>
                <a href="{{ route('portal.tickets.index') }}" class="text-sm font-medium text-slate-500 transition hover:text-[var(--komsije-primary)]">{{ __('Svi kvarovi') }}</a>
            </div>

            <div class="mt-5">
                @if ($recentTicket)
                    <div class="rounded-[1.5rem] bg-slate-50 p-5">
                        <div class="flex flex-wrap items-center gap-2">
                            <x-portal.badge :label="$recentTicket->status->label()" :tone="$recentTicketTone" />
                            <span class="text-xs font-medium text-slate-400">{{ $recentTicket->created_at->translatedFormat('d M Y, H:i') }}</span>
                        </div>
                        <h3 class="mt-4 text-lg font-semibold text-slate-950">{{ $recentTicket->title }}</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600">{{ \Illuminate\Support\Str::limit($recentTicket->description, 180) }}</p>
                        <div class="mt-4 flex flex-wrap gap-3 text-xs text-slate-500">
                            @if ($recentTicket->apartment?->number)
                                <span>{{ __('Stan :number', ['number' => $recentTicket->apartment->number]) }}</span>
                            @endif
                            @if ($recentTicket->assignee?->name)
                                <span>{{ __('Zadužen: :name', ['name' => $recentTicket->assignee->name]) }}</span>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="rounded-[1.5rem] border border-dashed border-slate-300 bg-slate-50 px-5 py-10 text-sm text-slate-500">{{ __('Još nema prijava povezanih sa vašim nalogom.') }}</div>
                @endif
            </div>
        </article>

        <article class="komsije-surface rounded-[2rem] p-6 sm:p-7">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold text-[var(--komsije-primary)]">{{ __('Obaveštenja') }}</p>
                    <h2 class="mt-1 text-2xl font-semibold tracking-tight text-slate-950">{{ __('Poslednje objave') }}</h2>
                </div>
                <div class="flex items-center gap-3">
                    @if ($recentUnreadAnnouncementsCount > 0)
                        <x-portal.badge :label="trans_choice(':count nepročitano|:count nepročitana|:count nepročitanih', $recentUnreadAnnouncementsCount, ['count' => $recentUnreadAnnouncementsCount])" tone="primary" />
                    @endif
                    <a href="{{ route('portal.announcements.index') }}" class="text-sm font-medium text-slate-500 transition hover:text-[var(--komsije-primary)]">{{ __('Sve objave') }}</a>
                </div>
            </div>

            <div class="mt-5 space-y-3">
                @forelse ($recentAnnouncements as $announcement)
                    <x-portal.announcement-card :announcement="$announcement" :href="route('portal.announcements.show', $announcement)" :unread="! $announcement->is_read" />
                @empty
                    <div class="rounded-[1.5rem] border border-dashed border-slate-300 bg-slate-50 px-5 py-10 text-sm text-slate-500">{{ __('Nema objavljenih obaveštenja za aktivnu zgradu.') }}</div>
                @endforelse
            </div>
        </article>
    </section>

    <section class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,0.95fr)_minmax(0,1.05fr)]">
        <article class="komsije-surface rounded-[2rem] p-6 sm:p-7">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold text-[var(--komsije-primary)]">{{ __('Informacije o zgradi') }}</p>
                    <h2 class="mt-1 text-2xl font-semibold tracking-tight text-slate-950">{{ __('Kontakt i podrška') }}</h2>
                </div>
            </div>

            @if ($profileBuilding)
                <div class="mt-5 space-y-4">
                    <div class="rounded-[1.5rem] bg-slate-50 p-5">
                        <p class="text-sm font-medium text-slate-500">{{ __('Zgrada') }}</p>
                        <p class="mt-2 text-lg font-semibold text-slate-950">{{ $profileBuilding->name }}</p>
                        <p class="mt-2 text-sm leading-6 text-slate-600">{{ $profileBuilding->address }}</p>
                    </div>

                    <div class="rounded-[1.5rem] border border-[var(--komsije-border)] bg-white p-5">
                        <p class="text-sm font-medium text-slate-500">{{ __('Upravnik') }}</p>
                        <p class="mt-2 text-lg font-semibold text-slate-950">{{ $manager?->name ?? __('Nije dodeljen') }}</p>
                        @if ($manager?->email)
                            <a href="mailto:{{ $manager->email }}" class="mt-2 inline-flex text-sm font-medium text-[var(--komsije-primary)] hover:text-blue-700">{{ $manager->email }}</a>
                        @else
                            <p class="mt-2 text-sm text-slate-500">{{ __('Kontakt podaci će biti dostupni kada upravnik bude dodeljen ovoj zgradi.') }}</p>
                        @endif
                    </div>
                </div>
            @else
                <div class="mt-5 rounded-[1.5rem] border border-dashed border-slate-300 bg-slate-50 px-5 py-10 text-sm text-slate-500">{{ __('Nema aktivne zgrade za prikaz kontakt podataka.') }}</div>
            @endif
        </article>

        <article class="space-y-6">
            <section class="komsije-surface rounded-[2rem] p-6 sm:p-7">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-semibold text-[var(--komsije-primary)]">{{ __('Podešavanja') }}</p>
                        <h2 class="mt-1 text-2xl font-semibold tracking-tight text-slate-950">{{ __('Izmeni profil') }}</h2>
                    </div>
                </div>

                <form method="POST" action="{{ route('portal.profile.update') }}" class="mt-5 space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="name" class="mb-2 block text-sm font-medium text-slate-700">{{ __('Ime i prezime') }}</label>
                        <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required class="komsije-input w-full rounded-2xl px-4 py-3">
                        @error('name')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="email" class="mb-2 block text-sm font-medium text-slate-700">{{ __('Email adresa') }}</label>
                        <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required class="komsije-input w-full rounded-2xl px-4 py-3">
                        @error('email')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex flex-wrap items-center gap-3 pt-2">
                        <button type="submit" class="rounded-[1.25rem] bg-[var(--komsije-primary)] px-5 py-3 text-sm font-medium text-white shadow-lg shadow-blue-600/20 transition hover:bg-blue-700">{{ __('Sačuvaj profil') }}</button>
                    </div>
                </form>
            </section>

            <section id="bezbednost" class="komsije-surface rounded-[2rem] p-6 sm:p-7">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-semibold text-[var(--komsije-primary)]">{{ __('Bezbednost') }}</p>
                        <h2 class="mt-1 text-2xl font-semibold tracking-tight text-slate-950">{{ __('Promeni lozinku') }}</h2>
                    </div>
                </div>

                <form method="POST" action="{{ route('portal.profile.password.update') }}" class="mt-5 grid gap-4 sm:grid-cols-2">
                    @csrf
                    @method('PUT')

                    <div class="sm:col-span-2">
                        <label for="current_password" class="mb-2 block text-sm font-medium text-slate-700">{{ __('Trenutna lozinka') }}</label>
                        <x-password-input id="current_password" name="current_password" required autocomplete="current-password" inputClass="komsije-input w-full rounded-2xl px-4 py-3" />
                        @error('current_password')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="password" class="mb-2 block text-sm font-medium text-slate-700">{{ __('Nova lozinka') }}</label>
                        <x-password-input id="password" name="password" required autocomplete="new-password" inputClass="komsije-input w-full rounded-2xl px-4 py-3" />
                        @error('password')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="mb-2 block text-sm font-medium text-slate-700">{{ __('Potvrda lozinke') }}</label>
                        <x-password-input id="password_confirmation" name="password_confirmation" required autocomplete="new-password" inputClass="komsije-input w-full rounded-2xl px-4 py-3" />
                    </div>

                    <div class="sm:col-span-2 flex flex-wrap items-center gap-3 pt-2">
                        <button type="submit" class="rounded-[1.25rem] border border-[var(--komsije-border)] bg-white px-5 py-3 text-sm font-medium text-slate-700 transition hover:border-blue-200 hover:text-[var(--komsije-primary)]">{{ __('Promeni lozinku') }}</button>
                    </div>
                </form>
            </section>
        </article>
    </section>

@endsection