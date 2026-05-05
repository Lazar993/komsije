@extends('portal.layouts.app')

@section('title', __('Komšije'))

@section('content')
    @if ($currentBuilding === null)
        <section class="komsije-surface rounded-[2rem] p-6 sm:p-10">
            <p class="text-sm font-semibold uppercase tracking-[0.24em] text-[var(--komsije-primary)]">{{ __('Portal status') }}</p>
            <h1 class="mt-4 max-w-2xl text-2xl font-bold tracking-tight text-slate-950 sm:text-4xl">{{ __('No building is assigned to this account yet.') }}</h1>
            <p class="mt-4 max-w-2xl text-base leading-7 text-slate-600">{{ __('Add the user to at least one building from the admin area, then return to the portal. The same account can belong to multiple buildings and switch between them here.') }}</p>
        </section>
    @else
        @include('partials.push-settings', ['variant' => 'banner'])

        <section class="grid gap-6 lg:grid-cols-[minmax(0,1.2fr)_24rem]">
            <article class="min-w-0 overflow-hidden rounded-[2rem] bg-[var(--komsije-dark)] px-6 py-7 text-white shadow-[0_28px_80px_-32px_rgba(15,23,42,0.85)] sm:px-8 sm:py-8">
                <div class="flex flex-col gap-6">
                    <div class="space-y-3">
                        <span class="inline-flex w-fit rounded-full bg-white/10 px-3 py-1 text-xs font-medium uppercase tracking-[0.22em] text-blue-100">{{ __('Početna') }}</span>
                        <h1 class="max-w-xl text-2xl font-bold tracking-tight sm:text-5xl">{{ __('Sve za vašu zgradu, bez lutanja.') }}</h1>
                        <p class="max-w-2xl text-sm leading-7 text-slate-300 sm:text-base">{{ $currentBuilding->name }} · {{ $currentBuilding->address }}</p>
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
                        @if (auth()->user()->can('create', [App\Models\Ticket::class, $currentBuilding]))
                            <a href="{{ route('portal.tickets.create') }}" class="inline-flex items-center justify-center gap-2 rounded-[1.25rem] bg-[var(--komsije-primary)] px-5 py-4 text-base font-medium text-white shadow-lg shadow-blue-600/30 transition hover:bg-blue-700">
                                <x-portal.app-icon name="plus" class="h-5 w-5" />
                                <span>{{ __('Prijavi kvar') }}</span>
                            </a>
                        @endif
                        <a href="{{ route('portal.tickets.index') }}" class="inline-flex items-center justify-center gap-2 rounded-[1.25rem] border border-white/15 bg-white/8 px-5 py-4 text-sm font-medium text-white transition hover:bg-white/12">
                            <x-portal.app-icon name="tickets" class="h-5 w-5" />
                            <span>{{ __('Moji kvarovi') }}</span>
                        </a>
                        <a href="{{ route('portal.announcements.index') }}" class="inline-flex items-center justify-center gap-2 rounded-[1.25rem] border border-white/15 bg-white/8 px-5 py-4 text-sm font-medium text-white transition hover:bg-white/12">
                            <x-portal.app-icon name="announcements" class="h-5 w-5" />
                            <span>{{ __('Obaveštenja') }}</span>
                        </a>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-3">
                        <div class="rounded-[1.5rem] border border-white/10 bg-white/6 p-4">
                            <p class="text-xs uppercase tracking-[0.22em] text-slate-300">{{ __('Stanovi') }}</p>
                            <p class="mt-3 text-3xl font-semibold text-white">{{ $currentBuilding->apartments_count }}</p>
                        </div>
                        <div class="rounded-[1.5rem] border border-white/10 bg-white/6 p-4">
                            <p class="text-xs uppercase tracking-[0.22em] text-slate-300">{{ __('Kvarovi') }}</p>
                            <p class="mt-3 text-3xl font-semibold text-white">{{ $currentBuilding->tickets_count }}</p>
                        </div>
                        <div class="rounded-[1.5rem] border border-white/10 bg-white/6 p-4">
                            <p class="text-xs uppercase tracking-[0.22em] text-slate-300">{{ __('Obaveštenja') }}</p>
                            <p class="mt-3 text-3xl font-semibold text-white">{{ $currentBuilding->announcements_count }}</p>
                        </div>
                    </div>
                </div>
            </article>

            <aside class="komsije-surface min-w-0 rounded-[2rem] p-6 sm:p-7">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-semibold text-[var(--komsije-primary)]">{{ __('Danas') }}</p>
                        <h2 class="mt-2 text-2xl font-semibold tracking-tight text-slate-950">{{ __('Brzi pregled') }}</h2>
                    </div>
                    <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-medium text-[var(--komsije-primary)]">{{ now()->translatedFormat('d M') }}</span>
                </div>

                @php($latestTicket = $recentTickets->first())

                <div class="mt-5 space-y-3">
                    <a href="{{ route('portal.announcements.index') }}" class="block rounded-[1.5rem] bg-slate-50 p-4 transition hover:bg-blue-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--komsije-primary)]">
                        <p class="text-sm font-medium text-slate-500">{{ __('Nepročitana obaveštenja') }}</p>
                        <p class="mt-1 text-3xl font-semibold text-slate-950">{{ $unreadAnnouncementsCount ?? 0 }}</p>
                    </a>
                    <a href="{{ $latestTicket ? route('portal.tickets.show', $latestTicket->id) : route('portal.tickets.index') }}" class="block rounded-[1.5rem] bg-slate-50 p-4 transition hover:bg-blue-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--komsije-primary)]">
                        <p class="text-sm font-medium text-slate-500">{{ __('Poslednja prijava') }}</p>
                        <p class="mt-1 text-base font-semibold text-slate-950">{{ $latestTicket?->title ?? __('Još nema prijava') }}</p>
                        <p class="mt-1 text-sm text-slate-500">{{ $latestTicket?->created_at?->diffForHumans() ?? __('Prijavite prvi kvar kad zatreba.') }}</p>
                    </a>
                </div>

                <a href="{{ route('portal.profile.show') }}" class="mt-5 block rounded-[1.5rem] border border-[var(--komsije-border)] bg-white p-4 transition hover:border-blue-200 hover:bg-blue-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-[var(--komsije-primary)]">
                    <p class="text-sm font-medium text-slate-500">{{ __('Vaš profil') }}</p>
                    <div class="mt-3 flex items-center gap-3">
                        <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-blue-50 text-sm font-semibold text-[var(--komsije-primary)]">{{ \Illuminate\Support\Str::of(auth()->user()->name)->trim()->substr(0, 1)->upper() }}</span>
                        <div>
                            <p class="font-semibold text-slate-950">{{ auth()->user()->name }}</p>
                            <p class="text-sm text-slate-500">{{ auth()->user()->email }}</p>
                        </div>
                    </div>
                </a>
            </aside>
        </section>

        <section class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
            <div
                id="portal-polls-panel"
                class="contents"
                data-polls-panel
                data-polls-url="{{ route('portal.dashboard', ['fragment' => 'polls']) }}"
            >
                @include('portal.partials.polls-panel', ['polls' => $polls])
            </div>

            <article class="komsije-surface min-w-0 overflow-hidden rounded-[2rem] p-6 sm:p-7">
                <div class="flex items-center justify-between gap-4">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-[var(--komsije-primary)]">{{ __('Kvarovi') }}</p>
                        <h2 class="mt-1 text-2xl font-semibold tracking-tight text-slate-950">{{ __('Nedavne prijave') }}</h2>
                    </div>
                    <a href="{{ route('portal.tickets.index') }}" class="shrink-0 text-sm font-medium text-slate-500 transition hover:text-[var(--komsije-primary)]">{{ __('Pogledaj sve') }}</a>
                </div>

                <div class="card-deck mt-5" data-card-deck>
                    <div class="card-deck__scroller" data-card-deck-scroller>
                        @forelse ($recentTickets as $ticket)
                            <x-portal.ticket-card :ticket="$ticket" :href="route('portal.tickets.show', $ticket->id)" />
                        @empty
                            <div class="rounded-[1.5rem] border border-dashed border-slate-300 bg-slate-50 px-5 py-10 text-sm text-slate-500">{{ __('Još nema prijavljenih kvarova u ovoj zgradi.') }}</div>
                        @endforelse
                    </div>
                    <p class="card-deck__counter" data-card-deck-counter aria-live="polite">1/{{ $recentTickets->count() }}</p>
                </div>
            </article>

            <article class="komsije-surface min-w-0 overflow-hidden rounded-[2rem] p-6 sm:p-7">
                <div class="flex items-center justify-between gap-4">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-[var(--komsije-primary)]">{{ __('Obaveštenja') }}</p>
                        <h2 class="mt-1 text-2xl font-semibold tracking-tight text-slate-950">{{ __('Najnovije objave') }}</h2>
                    </div>
                    <a href="{{ route('portal.announcements.index') }}" class="shrink-0 text-sm font-medium text-slate-500 transition hover:text-[var(--komsije-primary)]">{{ __('Pogledaj sve') }}</a>
                </div>

                <div class="card-deck mt-5" data-card-deck>
                    <div class="card-deck__scroller" data-card-deck-scroller>
                        @forelse ($recentAnnouncements as $announcement)
                            <x-portal.announcement-card :announcement="$announcement" :href="route('portal.announcements.show', $announcement->id)" :unread="! $announcement->is_read" />
                        @empty
                            <div class="rounded-[1.5rem] border border-dashed border-slate-300 bg-slate-50 px-5 py-10 text-sm text-slate-500">{{ __('Nema aktivnih obaveštenja za ovu zgradu.') }}</div>
                        @endforelse
                    </div>
                    <p class="card-deck__counter" data-card-deck-counter aria-live="polite">1/{{ $recentAnnouncements->count() }}</p>
                </div>
            </article>
        </section>

        <script>
            (() => {
                const panel = document.querySelector('[data-polls-panel]');

                if (!panel) {
                    return;
                }

                const url = panel.dataset.pollsUrl;

                if (!url) {
                    return;
                }

                let isRefreshing = false;

                const refreshPolls = async () => {
                    if (isRefreshing || document.hidden) {
                        return;
                    }

                    isRefreshing = true;

                    try {
                        const response = await fetch(url, {
                            credentials: 'same-origin',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'text/html',
                            },
                        });

                        if (!response.ok) {
                            return;
                        }

                        panel.innerHTML = await response.text();
                    } catch (error) {
                        // Do nothing - next interval will retry automatically.
                    } finally {
                        isRefreshing = false;
                    }
                };

                const intervalId = window.setInterval(refreshPolls, 15000);

                document.addEventListener('visibilitychange', () => {
                    if (!document.hidden) {
                        refreshPolls();
                    }
                });

                window.addEventListener('beforeunload', () => {
                    window.clearInterval(intervalId);
                });
            })();
        </script>
    @endif
@endsection