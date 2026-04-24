@props([
    'announcement',
    'href' => null,
    'unread' => false,
    'showState' => false,
])

@php
    $wrapperClasses = 'group block rounded-[1.35rem] border border-transparent bg-slate-50 p-5 transition hover:border-blue-100 hover:bg-white';
@endphp

@if ($href)
    <a href="{{ $href }}" class="{{ $wrapperClasses }}">
@else
    <div class="{{ $wrapperClasses }}">
@endif
    <div class="flex items-start justify-between gap-4">
        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2">
                @if ($unread)
                    <x-portal.badge :label="__('New')" tone="primary" />
                @endif
                @if ($showState)
                    <x-portal.badge :label="$announcement->published_at ? __('Published') : __('Draft')" :tone="$announcement->published_at ? 'success' : 'warning'" />
                @endif
                <span class="text-xs font-medium text-slate-400">{{ $announcement->published_at?->diffForHumans() ?? __('Draft') }}</span>
            </div>
            <h3 class="mt-3 truncate text-base font-semibold text-slate-950 transition group-hover:text-[var(--komsije-primary)]">{{ $announcement->title }}</h3>
            <p class="mt-2 text-sm leading-6 text-slate-600">{{ \Illuminate\Support\Str::limit($announcement->content, 140) }}</p>
            <div class="mt-4 flex flex-wrap gap-3 text-xs text-slate-400">
                <span>{{ $announcement->author?->name ?? __('Unknown author') }}</span>
                @if ($announcement->published_at)
                    <span>{{ $announcement->published_at->translatedFormat('d M Y, H:i') }}</span>
                @endif
            </div>
        </div>

        <span class="rounded-2xl bg-white px-3 py-2 text-xs font-medium text-slate-500 ring-1 ring-slate-200 transition group-hover:text-[var(--komsije-primary)]">{{ __('Otvori') }}</span>
    </div>
@if ($href)
    </a>
@else
    </div>
@endif