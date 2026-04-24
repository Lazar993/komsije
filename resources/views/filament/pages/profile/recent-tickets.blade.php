<div class="space-y-3">
    @forelse ($tickets as $ticket)
        @php
            $badgeClasses = match ($ticket->status?->value) {
                'resolved' => 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-200 dark:ring-emerald-400/20',
                'in_progress' => 'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-200 dark:ring-amber-400/20',
                default => 'bg-blue-50 text-blue-700 ring-blue-200 dark:bg-blue-500/10 dark:text-blue-200 dark:ring-blue-400/20',
            };
        @endphp

        <div class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-white/10 dark:bg-white/5">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div class="min-w-0">
                    <h3 class="truncate text-sm font-semibold text-slate-950 dark:text-white">{{ $ticket->title }}</h3>
                    <div class="mt-2 flex flex-wrap gap-3 text-xs text-slate-500 dark:text-slate-400">
                        <span>{{ $ticket->created_at->translatedFormat('d M Y, H:i') }}</span>
                        @if ($ticket->building?->name)
                            <span>{{ $ticket->building->name }}</span>
                        @endif
                        @if ($ticket->apartment?->number)
                            <span>{{ __('Apartment :number', ['number' => $ticket->apartment->number]) }}</span>
                        @endif
                    </div>
                </div>

                <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium ring-1 ring-inset {{ $badgeClasses }}">
                    {{ $ticket->status->label() }}
                </span>
            </div>
        </div>
    @empty
        <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-5 py-10 text-sm text-slate-500 dark:border-white/10 dark:bg-white/5 dark:text-slate-400">
            {{ __('No tickets reported yet.') }}
        </div>
    @endforelse
</div>