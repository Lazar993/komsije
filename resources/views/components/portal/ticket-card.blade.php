@props([
    'ticket',
    'href' => null,
])

@php
    $statusTone = match ($ticket->status?->value) {
        'resolved' => 'success',
        'in_progress' => 'warning',
        default => 'neutral',
    };
    $wrapperClasses = 'group block rounded-[1.35rem] border border-transparent bg-slate-50 p-5 transition hover:border-blue-100 hover:bg-white';
    $viewer = auth()->user();
    $canSeeIdentity = method_exists($ticket, 'viewerCanSeeIdentity')
        ? $ticket->viewerCanSeeIdentity($viewer)
        : true;
    $isPublic = method_exists($ticket, 'isPublic') ? $ticket->isPublic() : false;
    $affectedCount = (int) ($ticket->affected_count ?? 0);
@endphp

@if ($href)
    <a href="{{ $href }}" class="{{ $wrapperClasses }}">
@else
    <div class="{{ $wrapperClasses }}">
@endif
    <div class="flex items-start justify-between gap-4">
        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2">
                <x-portal.badge :label="$ticket->status->label()" :tone="$statusTone" />
                @if ($isPublic)
                    <span class="rounded-full border border-blue-200 bg-blue-50 px-2 py-0.5 text-[11px] font-semibold uppercase tracking-[0.14em] text-blue-700">{{ __('Public') }}</span>
                @endif
                <span class="text-xs font-medium text-slate-400">{{ $ticket->created_at->diffForHumans() }}</span>
            </div>
            <h3 class="mt-3 truncate text-base font-semibold text-slate-950 transition group-hover:text-[var(--komsije-primary)]">{{ $ticket->title }}</h3>
            <p class="mt-2 text-sm leading-6 text-slate-600">{{ \Illuminate\Support\Str::limit($ticket->description, 120) }}</p>
            <div class="mt-4 flex flex-wrap gap-3 text-xs text-slate-400">
                @if ($canSeeIdentity)
                    <span>{{ $ticket->reporter?->name ?? __('Unknown') }}</span>
                    @if ($ticket->apartment?->number)
                        <span>{{ __('Stan :number', ['number' => $ticket->apartment->number]) }}</span>
                    @endif
                @else
                    <span>{{ __('Resident reported this issue') }}</span>
                @endif
                @if ($ticket->assignee?->name)
                    <span>{{ __('Dodeljeno: :name', ['name' => $ticket->assignee->name]) }}</span>
                @endif
                @if ($isPublic && $affectedCount > 0)
                    <span class="font-semibold text-blue-700">{{ trans_choice(':count resident also affected|:count residents also affected', $affectedCount, ['count' => $affectedCount]) }}</span>
                @endif
            </div>
        </div>

        <span class="hidden rounded-2xl bg-white px-3 py-2 text-xs font-medium text-slate-500 ring-1 ring-slate-200 transition group-hover:text-[var(--komsije-primary)] sm:inline-flex">{{ __('Otvori') }}</span>
    </div>
@if ($href)
    </a>
@else
    </div>
@endif