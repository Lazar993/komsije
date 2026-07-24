@php
    $editing = isset($post);
    $existingImages = $editing ? $post->images : collect();
@endphp

<form method="POST" action="{{ $editing ? route('portal.neighbor-board.update', $post) : route('portal.neighbor-board.store') }}" enctype="multipart/form-data" class="space-y-6">
    @csrf
    @if ($editing)
        @method('PUT')
    @endif

    <input type="hidden" name="building_id" value="{{ $currentBuilding->getKey() }}">

    <div>
        <label for="category" class="mb-2 block text-sm font-medium text-slate-700">{{ __('Kategorija') }}</label>
        <select id="category" name="category" required class="komsije-input w-full rounded-2xl px-4 py-3">
            @foreach ($categoryOptions as $value => $label)
                <option value="{{ $value }}" @selected((string) old('category', $post->category->value ?? '') === (string) $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('category')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="title" class="mb-2 block text-sm font-medium text-slate-700">{{ __('Naslov') }}</label>
        <input id="title" name="title" type="text" value="{{ old('title', $post->title ?? '') }}" required class="komsije-input w-full rounded-2xl px-4 py-3">
        @error('title')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="description" class="mb-2 block text-sm font-medium text-slate-700">{{ __('Opis') }}</label>
        <textarea id="description" name="description" rows="6" required class="komsije-input w-full rounded-2xl px-4 py-3">{{ old('description', $post->description ?? '') }}</textarea>
        @error('description')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="images" class="mb-2 block text-sm font-medium text-slate-700">{{ __('Slike') }}</label>
        <input id="images" name="images[]" type="file" multiple accept="image/*" data-file-preview-input class="komsije-input block min-w-0 w-full max-w-full overflow-hidden rounded-2xl px-4 py-3 text-sm text-slate-600 file:mr-4 file:rounded-2xl file:border-0 file:bg-slate-900 file:px-4 file:py-2.5 file:font-medium file:text-white hover:file:bg-slate-800">
        <ul data-file-preview-list class="mt-3 hidden space-y-2 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600"></ul>
        <p class="mt-2 text-sm text-slate-500">{{ __('Do 3 slike, do 10 MB po slici.') }}</p>
        @error('images')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
        @error('images.*')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
    </div>

    @if (! $editing)
        <div>
            <label class="inline-flex items-center gap-3 rounded-2xl border border-[var(--komsije-border)] bg-white px-4 py-3">
                <input type="hidden" name="notify_residents" value="0">
                <input type="checkbox" name="notify_residents" value="1" @checked(old('notify_residents', false)) class="h-4 w-4 rounded border-slate-300 text-[var(--komsije-primary)] focus:ring-[var(--komsije-primary)]">
                <span class="text-sm text-slate-700">{{ __('Pošalji push obaveštenje komšijama') }}</span>
            </label>
            @error('notify_residents')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
        </div>
    @endif

    @if ($editing && $existingImages->isNotEmpty())
        <div>
            <p class="mb-2 block text-sm font-medium text-slate-700">{{ __('Postojeće slike') }}</p>
            <ul class="grid gap-3 sm:grid-cols-3">
                @foreach ($existingImages as $image)
                    <li class="rounded-2xl border border-slate-200 bg-white p-3">
                        <img src="{{ asset('storage/' . $image->path) }}" alt="{{ $image->original_name }}" class="h-24 w-full rounded-xl object-cover" loading="lazy">
                        <label class="mt-3 inline-flex items-center gap-2 text-xs text-rose-600">
                            <input type="checkbox" name="remove_images[]" value="{{ $image->id }}" class="h-4 w-4 rounded border-slate-300 text-rose-600 focus:ring-rose-500">
                            {{ __('Ukloni') }}
                        </label>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="flex flex-wrap items-center gap-3">
        <button type="submit" class="rounded-[1.25rem] bg-[var(--komsije-primary)] px-5 py-3 text-sm font-medium text-white shadow-lg shadow-blue-600/20 transition hover:bg-blue-700">{{ $editing ? __('Sačuvaj izmene') : __('Objavi') }}</button>
        <a href="{{ $editing ? route('portal.neighbor-board.show', $post) : route('portal.neighbor-board.index') }}" class="rounded-[1.25rem] border border-[var(--komsije-border)] bg-white px-5 py-3 text-sm font-medium text-slate-700 transition hover:border-blue-200 hover:text-[var(--komsije-primary)]">{{ __('Otkaži') }}</a>
    </div>
</form>
