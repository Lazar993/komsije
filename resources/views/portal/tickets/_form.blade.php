@php
    $editing = isset($ticket);
    $isBuildingAdmin = auth()->user()->isBuildingAdmin($currentBuilding->getKey());
    $isQuickReport = ! $editing && ! $isBuildingAdmin;
    $defaultApartmentId = count($apartments) === 1 ? (string) array_key_first($apartments) : null;
    $selectedApartmentId = (string) old('apartment_id', $editing ? (string) ($ticket->apartment_id ?? '') : ($defaultApartmentId ?? ''));
    $selectedPriority = (string) old('priority', $editing ? $ticket->priority->value : App\Enums\TicketPriority::Medium->value);
@endphp

<form method="POST" action="{{ $editing ? route('portal.tickets.update', $ticket) : route('portal.tickets.store') }}" enctype="multipart/form-data" class="space-y-6 {{ $isQuickReport ? 'pb-24 md:pb-0' : '' }}">
    @csrf
    @if ($editing)
        @method('PUT')
    @endif

    <input type="hidden" name="building_id" value="{{ $currentBuilding->getKey() }}">

    @if ($isQuickReport)
        @if ($defaultApartmentId !== null)
            <input type="hidden" name="apartment_id" value="{{ $defaultApartmentId }}">

            <div class="rounded-[1.5rem] border border-[var(--komsije-border)] bg-slate-50 px-4 py-4">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">{{ __('Stan') }}</p>
                <p class="mt-2 text-sm font-medium text-slate-900">{{ $apartments[$defaultApartmentId] }}</p>
            </div>
        @endif

        <div>
            <label for="description" class="mb-2 block text-sm font-medium text-slate-700">{{ __('Šta se desilo?') }}</label>
            <textarea id="description" name="description" rows="7" required autofocus placeholder="{{ __('Na primer: Lift ne radi od jutros, vrata se ne zatvaraju i ostali stanari ne mogu da ga koriste.') }}" class="komsije-input w-full rounded-[1.75rem] px-4 py-4 text-base">{{ old('description', $ticket->description ?? '') }}</textarea>
            @error('description')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
            <p class="mt-3 text-sm leading-6 text-slate-500">{{ __('Pošaljite osnovnu informaciju odmah, a detalje možete dodati kasnije.') }}</p>
        </div>

        <div class="rounded-[1.5rem] border border-[var(--komsije-border)] bg-white px-4 py-4">
            <details>
                <summary class="cursor-pointer list-none text-sm font-medium text-slate-700 marker:hidden">{{ __('Dodaj lokaciju ili hitnost') }}</summary>

                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    @if ($defaultApartmentId === null)
                        <div>
                            <label for="apartment_id" class="mb-2 block text-sm font-medium text-slate-700">{{ __('Stan (opciono)') }}</label>
                            <select id="apartment_id" name="apartment_id" class="komsije-input w-full rounded-2xl px-4 py-3">
                                <option value="">{{ __('No apartment linked') }}</option>
                                @foreach ($apartments as $apartmentId => $apartmentLabel)
                                    <option value="{{ $apartmentId }}" @selected($selectedApartmentId === (string) $apartmentId)>{{ $apartmentLabel }}</option>
                                @endforeach
                            </select>
                            @error('apartment_id')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
                        </div>
                    @endif

                    <div>
                        <label for="priority" class="mb-2 block text-sm font-medium text-slate-700">{{ __('Priority') }}</label>
                        <select id="priority" name="priority" class="komsije-input w-full rounded-2xl px-4 py-3">
                            @foreach ($priorities as $priority)
                                <option value="{{ $priority->value }}" @selected($selectedPriority === $priority->value)>{{ $priority->label() }}</option>
                            @endforeach
                        </select>
                        @error('priority')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="{{ $defaultApartmentId === null ? 'sm:col-span-2' : '' }}">
                        <label for="title" class="mb-2 block text-sm font-medium text-slate-700">{{ __('Naslov (opciono)') }}</label>
                        <input id="title" name="title" type="text" value="{{ old('title', $ticket->title ?? '') }}" class="komsije-input w-full rounded-2xl px-4 py-3">
                        @error('title')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                </div>
            </details>
        </div>

        <div>
            <label for="attachments" class="mb-2 block text-sm font-medium text-slate-700">{{ __('Images') }}</label>
            <input id="attachments" name="attachments[]" type="file" multiple accept="image/*" capture="environment" class="w-full rounded-2xl border border-dashed border-[var(--komsije-border)] bg-slate-50 px-4 py-3 text-sm text-slate-600">
            @error('attachments')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
            @error('attachments.*')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
            <p class="mt-3 text-sm leading-6 text-slate-500">{{ __('Dodajte fotografiju iz galerije ili odmah otvorite kameru.') }}</p>
        </div>

        <div class="hidden flex-wrap items-center gap-3 md:flex">
            <button type="submit" class="rounded-[1.25rem] bg-[var(--komsije-primary)] px-5 py-3 text-sm font-medium text-white shadow-lg shadow-blue-600/20 transition hover:bg-blue-700">{{ __('Pošalji prijavu') }}</button>
            <a href="{{ route('portal.tickets.index') }}" class="rounded-[1.25rem] border border-[var(--komsije-border)] bg-white px-5 py-3 text-sm font-medium text-slate-700 transition hover:border-blue-200 hover:text-[var(--komsije-primary)]">{{ __('Otkaži') }}</a>
        </div>

        <div class="fixed inset-x-4 bottom-24 z-20 md:hidden">
            <div class="komsije-surface rounded-[1.5rem] p-3 shadow-[0_20px_48px_-24px_rgba(15,23,42,0.35)]">
                <button type="submit" class="inline-flex w-full items-center justify-center rounded-[1.2rem] bg-[var(--komsije-primary)] px-5 py-4 text-base font-medium text-white shadow-lg shadow-blue-600/20 transition hover:bg-blue-700">{{ __('Pošalji prijavu') }}</button>
            </div>
        </div>
    @else
        <div class="grid gap-6 lg:grid-cols-2">
            <div class="lg:col-span-2">
                <label for="title" class="mb-2 block text-sm font-medium text-slate-700">{{ __('Title') }}</label>
                <input id="title" name="title" type="text" value="{{ old('title', $ticket->title ?? '') }}" required class="komsije-input w-full rounded-2xl px-4 py-3">
                @error('title')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div class="lg:col-span-2">
                <label for="description" class="mb-2 block text-sm font-medium text-slate-700">{{ __('Description') }}</label>
                <textarea id="description" name="description" rows="6" required class="komsije-input w-full rounded-2xl px-4 py-3">{{ old('description', $ticket->description ?? '') }}</textarea>
                @error('description')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="apartment_id" class="mb-2 block text-sm font-medium text-slate-700">{{ __('Apartment') }}</label>
                <select id="apartment_id" name="apartment_id" class="komsije-input w-full rounded-2xl px-4 py-3">
                    <option value="">{{ __('No apartment linked') }}</option>
                    @foreach ($apartments as $apartmentId => $apartmentLabel)
                        <option value="{{ $apartmentId }}" @selected((string) old('apartment_id', $ticket->apartment_id ?? '') === (string) $apartmentId)>{{ $apartmentLabel }}</option>
                    @endforeach
                </select>
                @error('apartment_id')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="priority" class="mb-2 block text-sm font-medium text-slate-700">{{ __('Priority') }}</label>
                <select id="priority" name="priority" required class="komsije-input w-full rounded-2xl px-4 py-3">
                    @foreach ($priorities as $priority)
                        <option value="{{ $priority->value }}" @selected(old('priority', $ticket->priority->value ?? 'medium') === $priority->value)>{{ $priority->label() }}</option>
                    @endforeach
                </select>
                @error('priority')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>

            @if ($isBuildingAdmin)
                <div>
                    <label for="assigned_to" class="mb-2 block text-sm font-medium text-slate-700">{{ __('Assign to manager') }}</label>
                    <select id="assigned_to" name="assigned_to" class="komsije-input w-full rounded-2xl px-4 py-3">
                        <option value="">{{ __('Unassigned') }}</option>
                        @foreach ($managerOptions as $managerId => $managerName)
                            <option value="{{ $managerId }}" @selected((string) old('assigned_to', $ticket->assigned_to ?? '') === (string) $managerId)>{{ $managerName }}</option>
                        @endforeach
                    </select>
                    @error('assigned_to')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>

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
    @endif
</form>