@extends('portal.layouts.app')

@section('title', $post->title)

@section('content')
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
        $authorApartment = $post->apartmentNumberForAuthor();
        $galleryId = 'neighbor-board-' . $post->getKey();
    @endphp

    <section class="grid gap-6 lg:grid-cols-[minmax(0,1.05fr)_minmax(0,0.95fr)]">
        <article class="min-w-0 overflow-hidden rounded-[2rem] border border-white/70 bg-white/80 p-6 shadow-xl shadow-slate-900/8 backdrop-blur sm:p-8">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <x-portal.badge :label="$post->category?->label() ?? '-'" tone="neutral" />
                        <x-portal.badge :label="$statusLabel" :tone="$statusTone" />
                        @if ($post->is_pinned)
                            <x-portal.badge :label="__('Zakačeno')" tone="warning" />
                        @endif
                    </div>
                    <h1 class="mt-4 break-words text-2xl font-semibold text-slate-950 sm:text-3xl">{{ $post->title }}</h1>
                </div>

                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('portal.neighbor-board.index') }}" class="rounded-full border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:border-slate-950 hover:text-slate-950">{{ __('Nazad') }}</a>
                    @can('update', $post)
                        <a href="{{ route('portal.neighbor-board.edit', $post) }}" class="rounded-full bg-slate-950 px-4 py-2 text-sm font-medium text-white transition hover:bg-sky-900">{{ __('Izmeni') }}</a>
                    @endcan
                </div>
            </div>

            <p class="mt-6 break-words text-base leading-8 text-slate-700">{{ $post->description }}</p>

            @if ($post->images->isNotEmpty())
                <div class="mt-8 grid grid-cols-2 gap-3 sm:grid-cols-3" data-lightbox-gallery="{{ $galleryId }}">
                    @foreach ($post->images as $image)
                        @php $url = asset('storage/' . $image->path); @endphp
                        <button
                            type="button"
                            class="group relative aspect-square overflow-hidden rounded-2xl border border-slate-200 bg-slate-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-500"
                            data-lightbox-trigger="{{ $galleryId }}"
                            data-lightbox-src="{{ $url }}"
                            data-lightbox-alt="{{ $image->original_name }}"
                            aria-label="{{ __('Open image :name', ['name' => $image->original_name]) }}"
                        >
                            <img src="{{ $url }}" alt="{{ $image->original_name }}" loading="lazy" class="h-full w-full object-cover transition group-hover:scale-[1.02]">
                        </button>
                    @endforeach
                </div>
            @endif

            <div class="mt-8 rounded-[1.5rem] border border-slate-200 bg-white p-5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 class="text-lg font-semibold text-slate-950">{{ __('Komentari') }}</h2>
                    <span class="text-sm text-slate-500">{{ trans_choice(':count komentar|:count komentara', (int) $post->comments_count, ['count' => (int) $post->comments_count]) }}</span>
                </div>

                <div class="mt-4 space-y-3">
                    @forelse ($post->comments as $comment)
                        @php
                            $commentApartment = $comment->user?->apartments?->firstWhere('building_id', (int) $post->building_id)?->number;
                        @endphp
                        <article class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <div class="flex flex-wrap items-center gap-2 text-xs text-slate-500">
                                <span class="font-semibold text-slate-700">{{ $comment->user?->name ?? __('Korisnik') }}</span>
                                @if ($commentApartment)
                                    <span>{{ __('Stan :number', ['number' => $commentApartment]) }}</span>
                                @endif
                                <span>{{ $comment->created_at->diffForHumans() }}</span>
                            </div>
                            <p class="mt-2 text-sm leading-6 text-slate-700">{{ $comment->body }}</p>
                        </article>
                    @empty
                        <p class="rounded-2xl border border-dashed border-slate-300 px-4 py-6 text-sm text-slate-500">{{ __('Još nema komentara.') }}</p>
                    @endforelse
                </div>

                @can('comment', $post)
                    <form method="POST" action="{{ route('portal.neighbor-board.comments.store', $post) }}" class="mt-5">
                        @csrf
                        <input type="hidden" name="building_id" value="{{ $currentBuilding->getKey() }}">
                        <label for="body" class="mb-2 block text-sm font-medium text-slate-700">{{ __('Dodaj komentar') }}</label>
                        <textarea id="body" name="body" rows="3" required class="komsije-input w-full rounded-2xl px-4 py-3">{{ old('body') }}</textarea>
                        @error('body')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror

                        <div class="mt-3">
                            <button type="submit" class="rounded-full bg-slate-950 px-4 py-2 text-sm font-medium text-white transition hover:bg-sky-900">{{ __('Pošalji komentar') }}</button>
                        </div>
                    </form>
                @else
                    @if ($post->comments_locked)
                        <p class="mt-5 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">{{ __('Komentari su zaključani za ovu objavu.') }}</p>
                    @endif
                @endcan
            </div>
        </article>

        <aside class="min-w-0 space-y-6">
            <article class="min-w-0 overflow-hidden rounded-[2rem] border border-white/70 bg-white/80 p-6 shadow-xl shadow-slate-900/8 backdrop-blur sm:p-8">
                <h2 class="text-xl font-semibold text-slate-950">{{ __('Autor') }}</h2>

                <div class="mt-5 flex items-center gap-3">
                    @if ($post->author?->profileImageUrl())
                        <img src="{{ $post->author->profileImageUrl() }}" alt="{{ $post->author->name }}" class="h-12 w-12 rounded-2xl object-cover">
                    @else
                        <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-sm font-semibold text-slate-700">
                            {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($post->author?->name ?? 'K', 0, 1)) }}
                        </span>
                    @endif

                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-slate-900">{{ $post->author?->name ?? __('Nepoznat autor') }}</p>
                        @if ($authorApartment)
                            <p class="text-xs text-slate-500">{{ __('Stan :number', ['number' => $authorApartment]) }}</p>
                        @endif
                    </div>
                </div>

                <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                    <p>{{ __('Objavljeno: :time', ['time' => $post->created_at->translatedFormat('d M Y, H:i')]) }}</p>
                    <p class="mt-1">{{ __('Komentara: :count', ['count' => (int) $post->comments_count]) }}</p>
                </div>
            </article>

            @can('markResolved', $post)
                <form method="POST" action="{{ route('portal.neighbor-board.resolve', $post) }}" class="rounded-[1.5rem] border border-emerald-200 bg-emerald-50 p-4">
                    @csrf
                    <button type="submit" class="w-full rounded-xl bg-emerald-600 px-4 py-3 text-sm font-medium text-white transition hover:bg-emerald-700">{{ __('Označi kao rešeno') }}</button>
                </form>
            @endcan

            @if (auth()->user()?->can('archive', $post) || auth()->user()?->can('delete', $post))
                <article class="rounded-[2rem] border border-white/70 bg-white/80 p-6 shadow-xl shadow-slate-900/8 backdrop-blur sm:p-8">
                    <h2 class="text-xl font-semibold text-slate-950">{{ __('Moderacija') }}</h2>

                    <div class="mt-4 grid gap-3">
                        @can('pin', $post)
                            <form method="POST" action="{{ route('portal.neighbor-board.pin', $post) }}">
                                @csrf
                                <button type="submit" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm font-medium text-slate-700 transition hover:border-blue-200 hover:text-[var(--komsije-primary)]">{{ $post->is_pinned ? __('Ukloni zakačeno') : __('Zakači objavu') }}</button>
                            </form>
                        @endcan

                        @can('lockComments', $post)
                            <form method="POST" action="{{ route('portal.neighbor-board.lock-comments', $post) }}">
                                @csrf
                                <button type="submit" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm font-medium text-slate-700 transition hover:border-blue-200 hover:text-[var(--komsije-primary)]">{{ $post->comments_locked ? __('Otključaj komentare') : __('Zaključaj komentare') }}</button>
                            </form>
                        @endcan

                        @can('archive', $post)
                            @if ($post->status->value !== 'archived')
                                <form method="POST" action="{{ route('portal.neighbor-board.archive', $post) }}">
                                    @csrf
                                    <button type="submit" class="w-full rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800 transition hover:bg-amber-100">{{ __('Arhiviraj') }}</button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('portal.neighbor-board.restore', $post) }}">
                                    @csrf
                                    <button type="submit" class="w-full rounded-xl border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 transition hover:bg-emerald-100">{{ __('Vrati iz arhive') }}</button>
                                </form>
                            @endif
                        @endcan

                        @can('delete', $post)
                            <form method="POST" action="{{ route('portal.neighbor-board.destroy', $post) }}" onsubmit="return confirm('{{ __('Da li ste sigurni da želite da obrišete objavu?') }}');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="w-full rounded-xl border border-rose-300 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700 transition hover:bg-rose-100">{{ __('Obriši objavu') }}</button>
                            </form>
                        @endcan
                    </div>
                </article>
            @endif
        </aside>
    </section>
@endsection
