@props([
    'post',
    'href' => null,
])

@php
    $statusTone = match ($post->status?->value) {
        'resolved' => 'success',
        'archived' => 'warning',
        default => 'primary',
    };
    $statusLabel = match ($post->status?->value) {
        'resolved' => __('Rešeno'),
        'archived' => __('Arhivirano'),
        default => __('Aktivno'),
    };
    $wrapperClasses = 'group block rounded-[1.35rem] border border-slate-200/80 bg-gradient-to-b from-white to-slate-50 p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-md';
    $firstImage = $post->images->first();
    $authorApartment = $post->apartmentNumberForAuthor();
    $categoryPillClasses = match ($post->category?->value) {
        'garage' => 'bg-sky-50 text-sky-700 border-sky-200',
        'contractor_recommendation' => 'bg-amber-50 text-amber-700 border-amber-200',
        'neighbor_help' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'lost_found' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
        'give_away' => 'bg-rose-50 text-rose-700 border-rose-200',
        'for_sale' => 'bg-teal-50 text-teal-700 border-teal-200',
        'wanted' => 'bg-cyan-50 text-cyan-700 border-cyan-200',
        'question' => 'bg-violet-50 text-violet-700 border-violet-200',
        default => 'bg-slate-100 text-slate-700 border-slate-200',
    };
@endphp

@if ($href)
    <a href="{{ $href }}" class="{{ $wrapperClasses }}">
@else
    <div class="{{ $wrapperClasses }}">
@endif
    <div class="flex items-start justify-between gap-4">
        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold {{ $categoryPillClasses }}">{{ $post->category?->label() ?? '-' }}</span>
                <x-portal.badge :label="$statusLabel" :tone="$statusTone" />
                @if ($post->is_pinned)
                    <x-portal.badge :label="__('Zakačeno')" tone="warning" />
                @endif
                <span class="text-xs font-medium text-slate-400">{{ $post->created_at->diffForHumans() }}</span>
            </div>

            <h3 class="mt-3 truncate text-base font-semibold text-slate-950 transition group-hover:text-[var(--komsije-primary)]">{{ $post->title }}</h3>
            <p class="mt-2 text-sm leading-6 text-slate-600">{{ \Illuminate\Support\Str::limit($post->description, 140) }}</p>

            <div class="mt-4 flex flex-wrap gap-3 text-xs text-slate-400">
                <span>{{ $post->author?->name ?? __('Nepoznat autor') }}</span>
                @if ($authorApartment)
                    <span>{{ __('Stan :number', ['number' => $authorApartment]) }}</span>
                @endif
                <span>{{ trans_choice(':count komentar|:count komentara', (int) ($post->comments_count ?? 0), ['count' => (int) ($post->comments_count ?? 0)]) }}</span>
                @if ($post->comments_locked)
                    <span class="font-semibold text-amber-700">{{ __('Komentari zaključani') }}</span>
                @endif
            </div>
        </div>

        <div class="shrink-0">
            @if ($firstImage)
                <img
                    src="{{ asset('storage/' . $firstImage->path) }}"
                    alt="{{ $firstImage->original_name }}"
                    loading="lazy"
                    class="h-16 w-16 rounded-2xl object-cover"
                >
            @endif
        </div>
    </div>
@if ($href)
    </a>
@else
    </div>
@endif
