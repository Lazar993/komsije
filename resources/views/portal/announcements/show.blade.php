@extends('portal.layouts.app')

@section('title', $announcement->title)

@section('content')
    <section class="grid gap-6 lg:grid-cols-[minmax(0,1.05fr)_minmax(0,0.95fr)]">
        <article class="min-w-0 rounded-[2rem] border border-white/70 bg-white/80 p-6 shadow-xl shadow-slate-900/8 backdrop-blur sm:p-8">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <span class="rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] {{ $announcement->published_at ? 'bg-emerald-100 text-emerald-950' : 'bg-amber-100 text-amber-950' }}">{{ $announcement->published_at ? __('Published') : __('Draft') }}</span>
                    <h1 class="mt-4 break-words text-2xl font-semibold text-slate-950 sm:text-3xl">{{ $announcement->title }}</h1>
                </div>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('portal.announcements.index') }}" class="rounded-full border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:border-slate-950 hover:text-slate-950">{{ __('Back') }}</a>
                    @can('approve', $announcement)
                        @if ($announcement->published_at === null)
                            <form method="POST" action="{{ route('portal.announcements.approve', $announcement) }}">
                                @csrf
                                <button type="submit" class="rounded-full bg-emerald-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-emerald-700">{{ __('Odobri i objavi') }}</button>
                            </form>
                        @endif
                    @endcan
                    @can('update', $announcement)
                        <a href="{{ route('portal.announcements.edit', $announcement) }}" class="rounded-full bg-slate-950 px-4 py-2 text-sm font-medium text-white transition hover:bg-sky-900">{{ __('Edit') }}</a>
                    @endcan
                </div>
            </div>

            @if ($announcement->published_at === null)
                <div class="mt-6 rounded-[1.5rem] border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-900">
                    {{ __('Ova objava čeka odobrenje upravnika i još uvek nije vidljiva ostalim komšijama.') }}
                </div>
            @endif

            <div class="mt-8 rounded-[1.5rem] border border-slate-200 bg-white p-6 text-base leading-8 text-slate-700">
                {!! nl2br(e($announcement->content)) !!}
            </div>

            @if ($announcement->attachments->isNotEmpty())
                <div class="mt-6 rounded-[1.5rem] border border-slate-200 bg-white p-6">
                    <h2 class="text-sm font-semibold uppercase tracking-[0.16em] text-slate-500">{{ __('Prilozi') }}</h2>
                    <ul class="mt-4 divide-y divide-slate-100">
                        @foreach ($announcement->attachments as $attachment)
                            @php
                                $isPdf = ($attachment->mime_type ?? '') === 'application/pdf';
                                $previewUrl = route('portal.announcements.attachments.download', [$announcement, $attachment]);
                            @endphp
                            <li class="flex flex-col items-start gap-2 py-3 sm:flex-row sm:items-center sm:justify-between sm:gap-3">
                                <a href="{{ $previewUrl }}"
                                   target="_blank"
                                   rel="noopener"
                                   @if ($isPdf)
                                       data-pdf-preview-trigger
                                       data-pdf-preview-src="{{ $previewUrl }}"
                                       data-pdf-preview-name="{{ $attachment->original_name }}"
                                   @endif
                                   class="inline-flex min-w-0 w-full items-center gap-3 text-sm font-medium text-slate-700 transition hover:text-[var(--komsije-primary)] sm:flex-1">
                                    <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-500">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                                            <path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8l-5-5Z" />
                                            <path d="M14 3v5h5" />
                                        </svg>
                                    </span>
                                    <span class="min-w-0 break-all sm:truncate">{{ $attachment->original_name }}</span>
                                </a>
                                <div class="flex w-full items-center justify-between gap-3 sm:w-auto sm:shrink-0 sm:justify-end">
                                    @if ($isPdf)
                                        <button type="button"
                                                data-pdf-preview-trigger
                                                data-pdf-preview-src="{{ $previewUrl }}"
                                                data-pdf-preview-name="{{ $attachment->original_name }}"
                                                class="text-xs font-semibold uppercase tracking-[0.16em] text-[var(--komsije-primary)] hover:underline">
                                            {{ __('Pregled') }}
                                        </button>
                                    @endif
                                    <a href="{{ $previewUrl }}?download=1"
                                                    download="{{ $attachment->original_name }}"
                                       class="text-xs font-semibold uppercase tracking-[0.16em] text-[var(--komsije-primary)] hover:underline">
                                        {{ __('Preuzmi') }}
                                    </a>
                                    <span class="text-xs text-slate-400">
                                        {{ $attachment->size > 0 ? number_format($attachment->size / 1024, 1) . ' KB' : '—' }}
                                    </span>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </article>

        <aside class="min-w-0 rounded-[2rem] border border-white/70 bg-white/80 p-6 shadow-xl shadow-slate-900/8 backdrop-blur sm:p-8">
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