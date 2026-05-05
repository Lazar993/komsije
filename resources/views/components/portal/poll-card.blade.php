@props([
    'poll',
    'featured' => false,
])

@php
    $hasVoted = (bool) ($poll->has_voted ?? false);
    $selectedOptionId = (int) optional($poll->votes->first())->poll_option_id;
    $totalVotes = (int) ($poll->votes_count ?? 0);
    $isOpen = (bool) $poll->is_active && ($poll->ends_at === null || $poll->ends_at->isFuture());
@endphp

<article class="h-full rounded-[1.35rem] border border-slate-200/90 bg-white p-5 shadow-sm lg:p-6">
    <div class="flex items-start justify-between gap-4 lg:gap-6">
        <div class="min-w-0">
            <h3 class="text-base font-semibold text-slate-950 lg:text-lg">{{ $poll->title }}</h3>
            @if ($poll->description)
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ $poll->description }}</p>
            @endif
        </div>
        <span class="shrink-0 rounded-full bg-blue-50 px-3 py-1 text-xs font-medium text-[var(--komsije-primary)] lg:px-3.5">{{ trans_choice(':count vote|:count votes', $totalVotes, ['count' => $totalVotes]) }}</span>
    </div>

    <div class="mt-4 flex flex-wrap items-center gap-2 text-xs text-slate-500">
        @if ($poll->ends_at)
            <span class="rounded-full bg-slate-100 px-2.5 py-1">{{ __('Ends') }}: {{ $poll->ends_at->translatedFormat('d M Y, H:i') }}</span>
        @else
            <span class="rounded-full bg-slate-100 px-2.5 py-1">{{ __('No end date') }}</span>
        @endif

        @if (! $isOpen)
            <span class="rounded-full bg-amber-100 px-2.5 py-1 text-amber-800">{{ __('Voting closed') }}</span>
        @endif
    </div>

    @if (! $hasVoted && $isOpen)
        <form method="POST" action="{{ route('portal.polls.vote', $poll) }}" class="mt-5 space-y-3">
            @csrf

            <div class="grid gap-3 {{ $featured ? 'xl:grid-cols-2' : '' }}">
                @foreach ($poll->options as $option)
                    <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 px-3 py-3 text-sm text-slate-700 transition hover:border-blue-200 hover:bg-blue-50/60">
                        <input
                            type="radio"
                            name="poll_option_id"
                            value="{{ $option->id }}"
                            class="mt-1 h-4 w-4 border-slate-300 text-[var(--komsije-primary)] focus:ring-[var(--komsije-primary)]"
                            required
                        >
                        <span class="leading-5">{{ $option->text }}</span>
                    </label>
                @endforeach
            </div>

            <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl bg-[var(--komsije-primary)] px-4 py-3 text-sm font-medium text-white transition hover:bg-blue-700 sm:w-auto">
                {{ __('Submit vote') }}
            </button>

            @error('poll_option_id')
                <p class="text-sm font-medium text-rose-600">{{ $message }}</p>
            @enderror
        </form>
    @else
        <div class="mt-5 space-y-3">
            @foreach ($poll->options as $option)
                @php
                    $optionVotes = (int) ($option->votes_count ?? 0);
                    $percentage = $totalVotes > 0 ? (int) round(($optionVotes / $totalVotes) * 100) : 0;
                    $isSelected = $selectedOptionId === (int) $option->id;
                @endphp

                <div class="rounded-xl border {{ $isSelected ? 'border-blue-200 bg-blue-50/60' : 'border-slate-200 bg-slate-50' }} px-3 py-3">
                    <div class="flex items-center justify-between gap-3 text-sm">
                        <p class="font-medium text-slate-800">{{ $option->text }}</p>
                        <p class="text-xs font-semibold text-slate-500">{{ $optionVotes }} · {{ $percentage }}%</p>
                    </div>
                    <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-200">
                        <div class="h-full rounded-full {{ $isSelected ? 'bg-[var(--komsije-primary)]' : 'bg-slate-400' }}" style="width: {{ $percentage }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>

        <p class="mt-4 text-sm font-medium text-emerald-700">{{ $hasVoted ? __('You already voted.') : __('Voting is closed.') }}</p>
    @endif
</article>
