@extends('portal.layouts.app')

@section('title', __('Announcements'))

@section('content')
    <section class="komsije-surface rounded-[2rem] p-6 sm:p-8">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-semibold text-[var(--komsije-primary)]">{{ __('Obaveštenja') }}</p>
                <h1 class="mt-3 text-3xl font-semibold tracking-tight text-slate-950">{{ __('Sve objave za :building', ['building' => $currentBuilding->name]) }}</h1>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">{{ __('Važne informacije, kratka obaveštenja i objave upravnika nalaze se na jednom mestu.') }}</p>
            </div>

            @can('create', [App\Models\Announcement::class, $currentBuilding])
                <a href="{{ route('portal.announcements.create') }}" class="inline-flex items-center justify-center gap-2 rounded-[1.25rem] bg-[var(--komsije-primary)] px-5 py-3 text-sm font-medium text-white shadow-lg shadow-blue-600/20 transition hover:bg-blue-700">
                    <x-portal.app-icon name="plus" class="h-4 w-4" />
                    <span>{{ __('Nova objava') }}</span>
                </a>
            @endcan
        </div>

        <div data-announcement-results aria-live="polite">
            @include('portal.announcements.partials.results', ['announcements' => $announcements])
        </div>
    </section>
@endsection