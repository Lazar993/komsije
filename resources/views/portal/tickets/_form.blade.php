@php
    $editing = isset($ticket);
    $isBuildingAdmin = auth()->user()->isBuildingAdmin($currentBuilding->getKey());
    $defaultApartmentId = count($apartments) === 1 ? (string) array_key_first($apartments) : null;
    $selectedApartmentId = (string) old('apartment_id', $editing ? (string) ($ticket->apartment_id ?? '') : ($defaultApartmentId ?? ''));
    $selectedPriority = (string) old('priority', $editing ? $ticket->priority->value : App\Enums\TicketPriority::Medium->value);
    $selectedVisibility = (string) old('visibility', $editing ? ($ticket->visibility?->value ?? App\Enums\TicketVisibility::Private->value) : App\Enums\TicketVisibility::Private->value);
@endphp

<form method="POST" action="{{ $editing ? route('portal.tickets.update', $ticket) : route('portal.tickets.store') }}" enctype="multipart/form-data" class="space-y-6">
    @csrf
    @if ($editing)
        @method('PUT')
    @endif

    <input type="hidden" name="building_id" value="{{ $currentBuilding->getKey() }}">

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="lg:col-span-2">
            <label for="title" class="mb-2 block text-sm font-medium text-slate-700">{{ __('Title') }}</label>
            <input id="title" name="title" type="text" value="{{ old('title', $ticket->title ?? '') }}" class="komsije-input w-full rounded-2xl px-4 py-3" placeholder="{{ __('Optional - if left blank, the description will be used.') }}">
            @error('title')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
        </div>

        <div class="lg:col-span-2">
            <label for="description" class="mb-2 block text-sm font-medium text-slate-700">{{ __('Description') }}</label>
            <textarea id="description" name="description" rows="6" required class="komsije-input w-full rounded-2xl px-4 py-3" data-ticket-description>{{ old('description', $ticket->description ?? '') }}</textarea>
            @error('description')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
        </div>

        <div class="lg:col-span-2">
            <span class="mb-2 block text-sm font-medium text-slate-700">{{ __('Visibility') }}</span>
            <div class="grid gap-3 sm:grid-cols-2" role="radiogroup" aria-label="{{ __('Visibility') }}">
                @foreach (($visibilities ?? App\Enums\TicketVisibility::cases()) as $visibility)
                    @php
                        $isPrivate = $visibility === App\Enums\TicketVisibility::Private;
                        $isSelected = $selectedVisibility === $visibility->value;
                    @endphp
                    <label class="komsije-input flex cursor-pointer items-start gap-3 rounded-2xl border p-4 transition {{ $isSelected ? 'border-[var(--komsije-primary)] ring-2 ring-blue-100' : '' }}" data-visibility-option="{{ $visibility->value }}">
                        <input type="radio" name="visibility" value="{{ $visibility->value }}" class="mt-1 h-4 w-4" @checked($isSelected) data-ticket-visibility-input>
                        <span class="min-w-0">
                            <span class="flex items-center gap-2">
                                <span class="text-sm font-semibold text-slate-900">{{ $visibility->label() }}</span>
                                @if ($isPrivate)
                                    <span class="rounded-full bg-slate-900/90 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-[0.16em] text-white">{{ __('Default') }}</span>
                                @endif
                            </span>
                            <span class="mt-2 block text-xs leading-5 text-slate-500">{{ $visibility->description() }}</span>
                        </span>
                    </label>
                @endforeach
            </div>
            <p class="mt-2 hidden text-xs text-blue-700" data-ticket-visibility-hint></p>
            @error('visibility')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="apartment_id" class="mb-2 block text-sm font-medium text-slate-700">{{ __('Apartment') }}</label>
            <select id="apartment_id" name="apartment_id" class="komsije-input w-full rounded-2xl px-4 py-3">
                <option value="">{{ __('No apartment linked') }}</option>
                @foreach ($apartments as $apartmentId => $apartmentLabel)
                    <option value="{{ $apartmentId }}" @selected($selectedApartmentId === (string) $apartmentId)>{{ $apartmentLabel }}</option>
                @endforeach
            </select>
            @error('apartment_id')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="priority" class="mb-2 block text-sm font-medium text-slate-700">{{ __('Priority') }}</label>
            <select id="priority" name="priority" required class="komsije-input w-full rounded-2xl px-4 py-3">
                @foreach ($priorities as $priority)
                    <option value="{{ $priority->value }}" @selected($selectedPriority === $priority->value)>{{ $priority->label() }}</option>
                @endforeach
            </select>
            @error('priority')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="assigned_to" class="mb-2 block text-sm font-medium text-slate-700">{{ __('Assigned manager') }}</label>
            <select id="assigned_to" name="assigned_to" class="komsije-input w-full rounded-2xl px-4 py-3">
                <option value="">{{ __('Unassigned') }}</option>
                @foreach ($managerOptions as $managerId => $managerName)
                    <option value="{{ $managerId }}" @selected((string) old('assigned_to', $ticket->assigned_to ?? '') === (string) $managerId)>{{ $managerName }}</option>
                @endforeach
            </select>
            @error('assigned_to')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
        </div>

        @if ($isBuildingAdmin)
            <div>
                <label for="status" class="mb-2 block text-sm font-medium text-slate-700">{{ __('Status') }}</label>
                <select id="status" name="status" class="komsije-input w-full rounded-2xl px-4 py-3">
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected(old('status', $ticket->status->value ?? 'new') === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </select>
                @error('status')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>

            @if ($editing)
                <div class="lg:col-span-2">
                    <label for="status_note" class="mb-2 block text-sm font-medium text-slate-700">{{ __('Status note') }}</label>
                    <input id="status_note" name="status_note" type="text" value="{{ old('status_note') }}" class="komsije-input w-full rounded-2xl px-4 py-3">
                    @error('status_note')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>
            @endif
        @endif

        <div class="lg:col-span-2">
            <label for="attachments" class="mb-2 block text-sm font-medium text-slate-700">{{ __('Images') }}</label>
            <input id="attachments" name="attachments[]" type="file" multiple accept="image/*" class="w-full rounded-2xl border border-dashed border-[var(--komsije-border)] bg-slate-50 px-4 py-3 text-sm text-slate-600">
            @error('attachments')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
            @error('attachments.*')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="flex flex-wrap items-center gap-3">
        <button type="submit" class="rounded-[1.25rem] bg-[var(--komsije-primary)] px-5 py-3 text-sm font-medium text-white shadow-lg shadow-blue-600/20 transition hover:bg-blue-700">{{ $editing ? __('Sačuvaj izmene') : __('Pošalji prijavu') }}</button>
        <a href="{{ $editing ? route('portal.tickets.show', $ticket) : route('portal.tickets.index') }}" class="rounded-[1.25rem] border border-[var(--komsije-border)] bg-white px-5 py-3 text-sm font-medium text-slate-700 transition hover:border-blue-200 hover:text-[var(--komsije-primary)]">{{ __('Otkaži') }}</a>
    </div>
</form>

@unless ($editing)
<script>
// Smart visibility suggestion: nudge users toward PUBLIC for shared/common-area issues
// and PRIVATE for apartment- or person-specific issues. The user always remains in
// control — we only set the default once, and only when the user has not interacted.
(function () {
    const form = document.currentScript.previousElementSibling;
    if (!form) return;
    const desc = form.querySelector('[data-ticket-description]');
    const title = form.querySelector('#title');
    const inputs = form.querySelectorAll('[data-ticket-visibility-input]');
    const hint = form.querySelector('[data-ticket-visibility-hint]');
    if (!desc || inputs.length === 0) return;

    const publicHints = ['lift', 'hodnik', 'ulaz', 'gara', 'krov', 'fasad', 'voda u zgradi', 'struja u zgradi', 'elevator', 'hallway', 'entrance', 'garage', 'roof', 'common'];
    const privateHints = ['stan', 'komšija', 'komsija', 'apartment', 'neighbor', 'lično', 'licno'];

    let userTouched = false;
    inputs.forEach((i) => i.addEventListener('change', () => { userTouched = true; updateHint(); }));

    function detect() {
        const text = ((title?.value || '') + ' ' + (desc.value || '')).toLowerCase();
        if (publicHints.some((k) => text.includes(k))) return 'public';
        if (privateHints.some((k) => text.includes(k))) return 'private';
        return null;
    }

    function updateHint() {
        if (!hint) return;
        const suggestion = detect();
        const current = form.querySelector('[data-ticket-visibility-input]:checked')?.value;
        if (suggestion && current && suggestion !== current) {
            hint.textContent = suggestion === 'public'
                ? @json(__('Tip: this looks like a shared building issue. Consider Public so neighbors avoid duplicate reports.'))
                : @json(__('Tip: this looks personal. Private keeps it between you and the manager.'));
            hint.classList.remove('hidden');
        } else {
            hint.classList.add('hidden');
        }
    }

    function maybeSuggest() {
        if (userTouched) { updateHint(); return; }
        const suggestion = detect();
        if (!suggestion) { updateHint(); return; }
        const target = form.querySelector(`[data-ticket-visibility-input][value="${suggestion}"]`);
        if (target && !target.checked) {
            target.checked = true;
        }
        updateHint();
    }

    desc.addEventListener('input', maybeSuggest);
    title?.addEventListener('input', maybeSuggest);
})();
</script>
@endunless
