@extends('portal.layouts.app')

@section('title', $announcement->title)

@section('content')
    <section class="grid gap-6 lg:grid-cols-[1.05fr_0.95fr]">
        <article class="rounded-[2rem] border border-white/70 bg-white/80 p-8 shadow-xl shadow-slate-900/8 backdrop-blur">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <span class="rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] {{ $announcement->published_at ? 'bg-emerald-100 text-emerald-950' : 'bg-amber-100 text-amber-950' }}">{{ $announcement->published_at ? __('Published') : __('Draft') }}</span>
                    <h1 class="mt-4 text-3xl font-semibold text-slate-950">{{ $announcement->title }}</h1>
                </div>
                <div class="flex gap-3">
                    <a href="{{ route('portal.announcements.index') }}" class="rounded-full border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:border-slate-950 hover:text-slate-950">{{ __('Back') }}</a>
                    @can('update', $announcement)
                        <a href="{{ route('portal.announcements.edit', $announcement) }}" class="rounded-full bg-slate-950 px-4 py-2 text-sm font-medium text-white transition hover:bg-sky-900">{{ __('Edit') }}</a>
                    @endcan
                </div>
            </div>

            <div class="mt-8 rounded-[1.5rem] border border-slate-200 bg-white p-6 text-base leading-8 text-slate-700">
                {!! nl2br(e($announcement->content)) !!}
            </div>
        </article>

        <aside class="rounded-[2rem] border border-white/70 bg-white/80 p-8 shadow-xl shadow-slate-900/8 backdrop-blur">
            <h2 class="text-xl font-semibold text-slate-950">{{ __('Announcement details') }}</h2>
            <div class="mt-6 space-y-4">
                <div class="rounded-3xl border border-slate-200 bg-slate-50 p-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ __('Author') }}</p>
                    <p class="mt-2 text-sm font-medium text-slate-950">{{ $announcement->author?->name ?? __('Unknown author') }}</p>
                </div>
                <div class="rounded-3xl border border-slate-200 bg-slate-50 p-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ __('Published at') }}</p>
                    <p class="mt-2 text-sm font-medium text-slate-950">{{ $announcement->published_at?->translatedFormat('M j, Y H:i') ?? __('Draft') }}</p>
                </div>
                <div class="rounded-3xl border border-slate-200 bg-slate-50 p-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ __('Read count') }}</p>
                    <p class="mt-2 text-sm font-medium text-slate-950">{{ $announcement->reads_count }}</p>
                </div>
                <div class="rounded-3xl border border-slate-200 bg-slate-50 p-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ __('Created') }}</p>
                    <p class="mt-2 text-sm font-medium text-slate-950">{{ $announcement->created_at->translatedFormat('M j, Y H:i') }}</p>
                </div>
            </div>
        </aside>
    </section>
@endsection