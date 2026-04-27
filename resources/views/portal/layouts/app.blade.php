<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>@yield('title', 'Komšije')</title>
        @include('partials.pwa-head')
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-[var(--komsije-background)] font-sans text-slate-900 antialiased overscroll-y-none">
        @php
            $user = auth()->user();
            $currentRoute = request()->route()?->getName();
            $canCreateTicket = $currentBuilding !== null && $user?->can('create', [App\Models\Ticket::class, $currentBuilding]);
            $showQuickReportCta = $canCreateTicket && ! in_array($currentRoute, ['portal.tickets.create', 'portal.tickets.edit'], true);
            $navItems = [
                ['label' => __('Početna'), 'route' => route('portal.dashboard'), 'icon' => 'home', 'active' => $currentRoute === 'portal.dashboard'],
                ['label' => __('Kvarovi'), 'route' => route('portal.tickets.index'), 'icon' => 'tickets', 'active' => str_starts_with((string) $currentRoute, 'portal.tickets.')],
                ['label' => __('Obaveštenja'), 'route' => route('portal.announcements.index'), 'icon' => 'announcements', 'active' => str_starts_with((string) $currentRoute, 'portal.announcements.')],
                ['label' => __('Profil'), 'route' => route('portal.profile.show'), 'icon' => 'profile', 'active' => str_starts_with((string) $currentRoute, 'portal.profile.')],
            ];
            $primaryMobileNavItems = array_slice($navItems, 0, 2);
            $secondaryMobileNavItems = array_slice($navItems, 2);
        @endphp

        <div class="relative isolate min-h-screen overflow-x-hidden pb-30 md:pb-8">
            <div class="pointer-events-none absolute inset-x-0 top-0 h-64 bg-[radial-gradient(circle_at_top,rgba(37,99,235,0.16),transparent_58%)]"></div>
            <div class="relative mx-auto flex min-h-screen max-w-7xl flex-col px-4 py-4 sm:px-6 lg:px-8">
                <header class="komsije-surface mb-6 rounded-[2rem] px-4 py-4 sm:px-6 sm:py-5">
                    <div class="flex flex-col gap-4">
                        <div class="flex items-start justify-between gap-3">
                            <a href="{{ route('portal.dashboard') }}" class="flex min-w-0 items-center gap-3">
                                <img src="{{ asset('icons/logo-icon-v2.svg') }}" alt="" class="h-10 w-10 shrink-0 rounded-2xl" width="40" height="40">
                                <span class="min-w-0">
                                    <span class="block truncate text-xl font-semibold text-[var(--komsije-dark)]">Komšije</span>
                                    <span class="block text-sm text-slate-500">{{ __('Sve u vezi zgrade, na jednom mestu.') }}</span>
                                </span>
                            </a>

                            <div class="flex items-center gap-2 sm:gap-3">
                                <a href="{{ route('portal.announcements.index') }}" class="komsije-pill relative inline-flex h-11 w-11 items-center justify-center rounded-2xl text-slate-600 transition hover:border-blue-200 hover:text-[var(--komsije-primary)]" aria-label="{{ __('Obaveštenja') }}">
                                    <x-portal.app-icon name="bell" class="h-5 w-5" />
                                    @if (($unreadAnnouncementsCount ?? 0) > 0)
                                        <span class="absolute -right-1 -top-1 inline-flex min-h-5 min-w-5 items-center justify-center rounded-full bg-[var(--komsije-primary)] px-1.5 text-[11px] font-semibold text-white">{{ min($unreadAnnouncementsCount, 9) }}</span>
                                    @endif
                                </a>

                                @if ($user?->isSuperAdmin() || $user?->isBuildingAdmin())
                                    <a href="/admin" class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-[var(--komsije-dark)] text-white transition hover:bg-slate-800 sm:hidden" aria-label="{{ __('Admin') }}" title="{{ __('Admin') }}">
                                        <x-portal.app-icon name="admin" class="h-4 w-4" />
                                    </a>

                                    <a href="/admin" class="hidden rounded-2xl bg-[var(--komsije-dark)] px-4 py-2.5 text-sm font-medium text-white transition hover:bg-slate-800 sm:inline-flex">{{ __('Admin') }}</a>
                                @endif
                            </div>
                        </div>

                        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                            <nav class="hidden items-center gap-2 md:flex">
                                @foreach ($navItems as $item)
                                    <a href="{{ $item['route'] }}" class="inline-flex items-center gap-2 rounded-2xl px-4 py-2.5 text-sm font-medium transition {{ $item['active'] ? 'bg-[var(--komsije-primary)] text-white shadow-lg shadow-blue-600/20' : 'text-slate-600 hover:bg-blue-50 hover:text-[var(--komsije-primary)]' }}">
                                        <x-portal.app-icon :name="$item['icon']" class="h-4 w-4" />
                                        <span>{{ $item['label'] }}</span>
                                    </a>
                                @endforeach
                            </nav>

                            <div class="flex flex-col gap-3 sm:min-w-0 sm:flex-row sm:flex-nowrap sm:items-center sm:justify-end">
                                @if ($accessibleBuildings->isNotEmpty())
                                    <form method="POST" action="{{ route('portal.buildings.switch', $currentBuilding ?? $accessibleBuildings->first()) }}" class="komsije-pill flex items-center gap-3 rounded-2xl px-4 py-3 sm:min-w-0 sm:max-w-44 md:max-w-52 xl:max-w-none">
                                        @csrf
                                        <div class="flex min-w-0 flex-1 flex-col">
                                            <span class="text-xs font-medium uppercase tracking-[0.22em] text-slate-400">{{ __('Aktivna zgrada') }}</span>
                                            <select name="building" class="w-full min-w-0 truncate bg-transparent pt-1 text-sm font-medium text-slate-700 outline-none" onchange="this.form.action='{{ url('/portal/buildings') }}/' + this.value + '/switch'; this.form.submit();">
                                                @foreach ($accessibleBuildings as $buildingOption)
                                                    <option value="{{ $buildingOption->getKey() }}" @selected($currentBuilding?->is($buildingOption))>{{ $buildingOption->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </form>
                                @endif

                                <a id="profil" href="{{ route('portal.profile.show') }}" class="komsije-pill flex items-center gap-3 rounded-2xl px-4 py-3 transition hover:border-blue-200 hover:text-[var(--komsije-primary)] sm:min-w-0 sm:max-w-44 md:max-w-52 xl:max-w-none">
                                    @if ($user?->profileImageUrl())
                                        <img src="{{ $user->profileImageUrl() }}" alt="{{ $user->name }}" class="h-10 w-10 rounded-2xl object-cover">
                                    @else
                                        <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-slate-100 text-sm font-semibold text-slate-700">{{ \Illuminate\Support\Str::of($user?->name ?? 'K')->trim()->substr(0, 1)->upper() }}</span>
                                    @endif
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-slate-900">{{ $user?->name }}</p>
                                        <p class="truncate text-xs text-slate-500">{{ $currentBuilding?->name ?? __('Nema aktivne zgrade') }}</p>
                                    </div>
                                </a>

                                <div class="flex items-center justify-end gap-2 self-end sm:gap-3 sm:self-auto">
                                    @include('partials.language-switcher', ['compact' => true, 'mobileLabelHidden' => true])

                                    <form method="POST" action="{{ route('logout') }}" class="shrink-0">
                                        @csrf
                                        <button type="submit" class="whitespace-nowrap rounded-2xl border border-[var(--komsije-border)] bg-white px-3 py-2.5 text-xs font-medium text-slate-700 transition hover:border-blue-200 hover:text-[var(--komsije-primary)] sm:px-4 sm:py-3 sm:text-sm">{{ __('Odjava') }}</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </header>

                @if (session('status'))
                    <div class="mb-6 rounded-[1.5rem] border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-900 shadow-sm">
                        {{ session('status') }}
                    </div>
                @endif

                <main class="flex-1">@yield('content')</main>
            </div>

            @include('partials.install-prompt')

            <nav class="fixed inset-x-4 bottom-4 z-30 md:hidden">
                <div class="komsije-surface grid {{ $showQuickReportCta ? 'grid-cols-5' : 'grid-cols-4' }} rounded-[1.75rem] px-2 py-2">
                    @if ($showQuickReportCta)
                        @foreach ($primaryMobileNavItems as $item)
                            <a href="{{ $item['route'] }}" class="relative flex flex-col items-center gap-1 rounded-2xl px-2 py-2 text-[11px] font-medium transition {{ $item['active'] ? 'bg-blue-50 text-[var(--komsije-primary)]' : 'text-slate-500' }}">
                                <x-portal.app-icon :name="$item['icon']" class="h-5 w-5" />
                                <span>{{ $item['label'] }}</span>
                            </a>
                        @endforeach

                        <a href="{{ route('portal.tickets.create') }}" class="relative flex flex-col items-center justify-center gap-1 rounded-2xl px-2 pb-2 text-[11px] font-medium text-[var(--komsije-primary)]">
                            <span class="inline-flex h-14 w-14 items-center justify-center rounded-[1.35rem] bg-[var(--komsije-primary)] text-white shadow-[0_20px_36px_-18px_rgba(37,99,235,0.9)]">
                                <x-portal.app-icon name="plus" class="h-6 w-6" />
                            </span>
                            <span>{{ __('Prijavi') }}</span>
                        </a>

                        @foreach ($secondaryMobileNavItems as $item)
                            <a href="{{ $item['route'] }}" class="relative flex flex-col items-center gap-1 rounded-2xl px-2 py-2 text-[11px] font-medium transition {{ $item['active'] ? 'bg-blue-50 text-[var(--komsije-primary)]' : 'text-slate-500' }}">
                                <x-portal.app-icon :name="$item['icon']" class="h-5 w-5" />
                                <span>{{ $item['label'] }}</span>
                                @if ($item['icon'] === 'announcements' && ($unreadAnnouncementsCount ?? 0) > 0)
                                    <span class="absolute right-4 top-1 inline-flex h-2.5 w-2.5 rounded-full bg-[var(--komsije-primary)]"></span>
                                @endif
                            </a>
                        @endforeach
                    @else
                        @foreach ($navItems as $item)
                            <a href="{{ $item['route'] }}" class="relative flex flex-col items-center gap-1 rounded-2xl px-2 py-2 text-[11px] font-medium transition {{ $item['active'] ? 'bg-blue-50 text-[var(--komsije-primary)]' : 'text-slate-500' }}">
                                <x-portal.app-icon :name="$item['icon']" class="h-5 w-5" />
                                <span>{{ $item['label'] }}</span>
                                @if ($item['icon'] === 'announcements' && ($unreadAnnouncementsCount ?? 0) > 0)
                                    <span class="absolute right-4 top-1 inline-flex h-2.5 w-2.5 rounded-full bg-[var(--komsije-primary)]"></span>
                                @endif
                            </a>
                        @endforeach
                    @endif
                </div>
            </nav>
        </div>
    </body>
</html>