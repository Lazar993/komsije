@extends('portal.layouts.app')

@section('title', __('Tickets'))

@section('content')
    <section class="komsije-surface rounded-[2rem] p-6 sm:p-8">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-semibold text-[var(--komsije-primary)]">{{ __('Kvarovi') }}</p>
                <h1 class="mt-3 text-3xl font-semibold tracking-tight text-slate-950">{{ __('Sve prijave za :building', ['building' => $currentBuilding->name]) }}</h1>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600">{{ __('Pregledajte prijavljene kvarove, filtrirajte po statusu i otvorite stavku za detalje ili izmenu.') }}</p>
            </div>

            @can('create', [App\Models\Ticket::class, $currentBuilding])
                <a href="{{ route('portal.tickets.create') }}" class="inline-flex items-center justify-center gap-2 rounded-[1.25rem] bg-[var(--komsije-primary)] px-5 py-3 text-sm font-medium text-white shadow-lg shadow-blue-600/20 transition hover:bg-blue-700">
                    <x-portal.app-icon name="plus" class="h-4 w-4" />
                    <span>{{ __('Prijavi kvar') }}</span>
                </a>
            @endcan
        </div>

        <form method="GET" action="{{ route('portal.tickets.index') }}" class="mt-6 grid gap-4 rounded-[1.5rem] border border-[var(--komsije-border)] bg-slate-50 p-5 lg:grid-cols-4" data-ticket-filters>
            <div>
                <label for="status" class="mb-2 block text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ __('Status') }}</label>
                <select id="status" name="status" class="komsije-input w-full rounded-2xl px-4 py-3 text-sm">
                    <option value="">{{ __('All statuses') }}</option>
                    @foreach (App\Enums\TicketStatus::cases() as $status)
                        <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="priority" class="mb-2 block text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ __('Priority') }}</label>
                <select id="priority" name="priority" class="komsije-input w-full rounded-2xl px-4 py-3 text-sm">
                    <option value="">{{ __('All priorities') }}</option>
                    @foreach (App\Enums\TicketPriority::cases() as $priority)
                        <option value="{{ $priority->value }}" @selected(request('priority') === $priority->value)>{{ $priority->label() }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="assigned_to" class="mb-2 block text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ __('Assigned manager') }}</label>
                <select id="assigned_to" name="assigned_to" class="komsije-input w-full rounded-2xl px-4 py-3 text-sm">
                    <option value="">{{ __('Anyone') }}</option>
                    @foreach ($managerOptions as $managerId => $managerName)
                        <option value="{{ $managerId }}" @selected((string) request('assigned_to') === (string) $managerId)>{{ $managerName }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end gap-3">
                <button type="submit" class="flex-1 rounded-[1rem] bg-[var(--komsije-dark)] px-4 py-3 text-sm font-medium text-white transition hover:bg-slate-800">{{ __('Primeni') }}</button>
                <a href="{{ route('portal.tickets.index') }}" class="rounded-[1rem] border border-[var(--komsije-border)] bg-white px-4 py-3 text-sm font-medium text-slate-700 transition hover:border-blue-200 hover:text-[var(--komsije-primary)]" data-ticket-filters-reset>{{ __('Resetuj') }}</a>
            </div>
        </form>

        <div data-ticket-results aria-live="polite">
            @include('portal.tickets.partials.results', ['tickets' => $tickets])
        </div>
    </section>
@endsection