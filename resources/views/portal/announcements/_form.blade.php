@php
    $editing = isset($announcement);
    $existingAttachments = $editing ? $announcement->attachments : collect();
    $isAdmin = auth()->user()->isBuildingAdmin($currentBuilding->getKey());
@endphp

<form method="POST" action="{{ $editing ? route('portal.announcements.update', $announcement) : route('portal.announcements.store') }}" enctype="multipart/form-data" class="space-y-6">
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

    @if ($isAdmin)
        <div>
            <label for="published_at" class="mb-2 block text-sm font-medium text-slate-700">{{ __('Publish at') }}</label>
            <input id="published_at" name="published_at" type="datetime-local" value="{{ old('published_at', isset($announcement) && $announcement->published_at ? $announcement->published_at->format('Y-m-d\TH:i') : '') }}" class="komsije-input block min-w-0 w-full max-w-full overflow-hidden rounded-2xl px-4 py-3">
            <p class="mt-2 text-sm text-slate-500">{{ __('Leave empty to keep it as a draft.') }}</p>
            @error('published_at')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="mb-2 block text-sm font-medium text-slate-700">{{ __('Important') }}</label>
            <label class="inline-flex items-center gap-3 rounded-2xl border border-[var(--komsije-border)] bg-white px-4 py-3">
                <input type="hidden" name="is_important" value="0">
                <input type="checkbox" name="is_important" value="1" @checked(old('is_important', $announcement->is_important ?? false)) class="h-4 w-4 rounded border-slate-300 text-[var(--komsije-primary)] focus:ring-[var(--komsije-primary)]">
                <span class="text-sm text-slate-700">{{ __('Označi kao važno (prikazuje se na vrhu spiska).') }}</span>
            </label>
            @error('is_important')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
        </div>

        @if ($editing && ($announcement->published_at ?? null) && ($announcement->is_important ?? false))
            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">{{ __('Obaveštenje o izmeni') }}</label>
                <label class="inline-flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3">
                    <input type="hidden" name="notify_residents" value="0">
                    <input type="checkbox" name="notify_residents" value="1" @checked(old('notify_residents', false)) class="mt-0.5 h-4 w-4 rounded border-slate-300 text-amber-600 focus:ring-amber-500">
                    <span class="text-sm text-slate-700">{{ __('Pošalji obaveštenje stanarima o ovoj izmeni (samo za važne objave).') }}</span>
                </label>
                @error('notify_residents')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>
        @endif
    @else
        <div class="rounded-2xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-900">
            {{ __('Vaša objava će biti vidljiva svim komšijama tek nakon što je upravnik odobri.') }}
        </div>
    @endif

    <div>
        <label for="attachments" class="mb-2 block text-sm font-medium text-slate-700">{{ __('Prilozi') }}</label>
        <input id="attachments" name="attachments[]" type="file" multiple accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document" data-file-preview-input class="komsije-input block min-w-0 w-full max-w-full overflow-hidden rounded-2xl px-4 py-3 text-sm text-slate-600 file:mr-4 file:rounded-2xl file:border-0 file:bg-slate-900 file:px-4 file:py-2.5 file:font-medium file:text-white hover:file:bg-slate-800">
        <ul data-file-preview-list class="mt-3 hidden space-y-2 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600"></ul>
        <p class="mt-2 text-sm text-slate-500">{{ __('PDF ili DOC/DOCX. Najviše 10 fajlova, do 20 MB po fajlu.') }}</p>
        @error('attachments')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
        @error('attachments.*')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
    </div>

    @if ($editing && $existingAttachments->isNotEmpty())
        <div>
            <p class="mb-2 block text-sm font-medium text-slate-700">{{ __('Postojeći prilozi') }}</p>
            <ul class="divide-y divide-slate-100 overflow-hidden rounded-2xl border border-[var(--komsije-border)] bg-white">
                @foreach ($existingAttachments as $attachment)
                    @php
                        $isPdf = ($attachment->mime_type ?? '') === 'application/pdf';
                        $previewUrl = route('portal.announcements.attachments.download', [$announcement, $attachment]);
                        $downloadUrl = $previewUrl . '?download=1';
                    @endphp
                    <li class="flex flex-col items-start gap-2 px-4 py-3 text-sm sm:flex-row sm:items-center sm:justify-between sm:gap-3">
                        <a href="{{ $isPdf ? $previewUrl : $downloadUrl }}"
                           @if ($isPdf)
                               target="_blank"
                               rel="noopener"
                           @else
                               data-portal-download
                               data-portal-download-name="{{ $attachment->original_name }}"
                               download="{{ $attachment->original_name }}"
                           @endif
                           class="inline-flex min-w-0 w-full items-center gap-2 font-medium text-slate-700 hover:text-[var(--komsije-primary)] sm:flex-1">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 shrink-0">
                                <path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8l-5-5Z" />
                                <path d="M14 3v5h5" />
                            </svg>
                            <span class="min-w-0 break-all sm:truncate">{{ $attachment->original_name }}</span>
                        </a>
                        <label class="inline-flex w-full items-center justify-end gap-2 text-xs text-rose-600 sm:w-auto sm:justify-start">
                            <input type="checkbox" name="remove_attachments[]" value="{{ $attachment->id }}" class="h-4 w-4 rounded border-slate-300 text-rose-600 focus:ring-rose-500">
                            {{ __('Ukloni') }}
                        </label>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="flex flex-wrap items-center gap-3">
        <button type="submit" class="rounded-[1.25rem] bg-[var(--komsije-primary)] px-5 py-3 text-sm font-medium text-white shadow-lg shadow-blue-600/20 transition hover:bg-blue-700">{{ $editing ? __('Sačuvaj objavu') : __('Objavi / sačuvaj') }}</button>
        <a href="{{ $editing ? route('portal.announcements.show', $announcement) : route('portal.announcements.index') }}" class="rounded-[1.25rem] border border-[var(--komsije-border)] bg-white px-5 py-3 text-sm font-medium text-slate-700 transition hover:border-blue-200 hover:text-[var(--komsije-primary)]">{{ __('Otkaži') }}</a>
    </div>
</form>