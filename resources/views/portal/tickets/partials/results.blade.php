<div class="mt-6 space-y-4">
    @forelse ($tickets as $ticket)
        <div class="rounded-[1.5rem] border border-[var(--komsije-border)] bg-white p-3 shadow-sm">
            <x-portal.ticket-card :ticket="$ticket" :href="route('portal.tickets.show', $ticket)" />

            @can('update', $ticket)
                <div class="mt-3 flex justify-end px-2 pb-1">
                    <a href="{{ route('portal.tickets.edit', $ticket) }}" class="inline-flex items-center gap-2 rounded-[1rem] bg-slate-100 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-200">{{ __('Izmeni') }}</a>
                </div>
            @endcan
        </div>
    @empty
        <div class="rounded-[1.5rem] border border-dashed border-slate-300 bg-slate-50 px-5 py-12 text-center text-sm text-slate-500">{{ __('No tickets match the selected filters.') }}</div>
    @endforelse
</div>

<div class="mt-6">{{ $tickets->links() }}</div>