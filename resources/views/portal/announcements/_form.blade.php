@php
    $editing = isset($announcement);
@endphp

<form method="POST" action="{{ $editing ? route('portal.announcements.update', $announcement) : route('portal.announcements.store') }}" class="space-y-6">
    @csrf
    @if ($editing)
        @method('PUT')
    @endif

    <input type="hidden" name="building_id" value="{{ $currentBuilding->getKey() }}">

    <div>
        <label for="title" class="mb-2 block text-sm font-medium text-slate-700">{{ __('Title') }}</label>
        <input id="title" name="title" type="text" value="{{ old('title', $announcement->title ?? '') }}" required class="komsije-input w-full rounded-2xl px-4 py-3">
        @error('title')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="content" class="mb-2 block text-sm font-medium text-slate-700">{{ __('Content') }}</label>
        <textarea id="content" name="content" rows="10" required class="komsije-input w-full rounded-2xl px-4 py-3">{{ old('content', $announcement->content ?? '') }}</textarea>
        @error('content')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="published_at" class="mb-2 block text-sm font-medium text-slate-700">{{ __('Publish at') }}</label>
        <input id="published_at" name="published_at" type="datetime-local" value="{{ old('published_at', isset($announcement) && $announcement->published_at ? $announcement->published_at->format('Y-m-d\TH:i') : '') }}" class="komsije-input w-full rounded-2xl px-4 py-3">
        <p class="mt-2 text-sm text-slate-500">{{ __('Leave empty to keep it as a draft.') }}</p>
        @error('published_at')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
    </div>

    <div class="flex flex-wrap items-center gap-3">
        <button type="submit" class="rounded-[1.25rem] bg-[var(--komsije-primary)] px-5 py-3 text-sm font-medium text-white shadow-lg shadow-blue-600/20 transition hover:bg-blue-700">{{ $editing ? __('Sačuvaj objavu') : __('Objavi / sačuvaj') }}</button>
        <a href="{{ $editing ? route('portal.announcements.show', $announcement) : route('portal.announcements.index') }}" class="rounded-[1.25rem] border border-[var(--komsije-border)] bg-white px-5 py-3 text-sm font-medium text-slate-700 transition hover:border-blue-200 hover:text-[var(--komsije-primary)]">{{ __('Otkaži') }}</a>
    </div>
</form>