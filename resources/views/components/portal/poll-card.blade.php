@props([
    'poll',
    'featured' => false,
])

@php
    $hasVoted = (bool) ($poll->has_voted ?? false);
    $isOpen = $poll->isOpenForVoting();
    $canVote = $isOpen && $poll->eligibleVoters()->whereKey(auth()->id())->exists();
    $questions = $poll->questions->isNotEmpty()
        ? $poll->questions
        : collect([(object) ['id' => null, 'text' => $poll->title, 'options' => $poll->options]]);
@endphp

<article class="h-full min-w-0 rounded-[1.35rem] border border-slate-200/90 bg-white p-5 shadow-sm lg:p-6">
    <h3 class="break-words text-base font-semibold text-slate-950 lg:text-lg">{{ $poll->title }}</h3>
    @if ($poll->description)
        <p class="mt-2 break-words text-sm leading-6 text-slate-600">{{ $poll->description }}</p>
    @endif
    <p class="mt-3 text-sm font-medium text-[var(--komsije-primary)]">{{ $poll->participationLabel() }}</p>

    <div class="mt-4 flex flex-wrap items-center gap-2 text-xs text-slate-500">
        @if ($poll->ends_at)
            <span class="rounded-full bg-slate-100 px-2.5 py-1">{{ __('Ends') }}: {{ $poll->ends_at->translatedFormat('d M Y, H:i') }}</span>
        @else
            <span class="rounded-full bg-slate-100 px-2.5 py-1">{{ __('No end date') }}</span>
        @endif

        @if (! $isOpen)
            <span class="rounded-full bg-amber-100 px-2.5 py-1 text-amber-800">{{ __('Voting closed') }}</span>
            @if ($poll->majority_type !== null)
                <span @class([
                    'rounded-full px-2.5 py-1 font-semibold',
                    'bg-emerald-100 text-emerald-800' => $poll->outcome(),
                    'bg-rose-100 text-rose-800' => ! $poll->outcome(),
                ])>{{ $poll->outcomeLabel() }}</span>
            @endif
        @endif
    </div>

    @if (! $hasVoted && $canVote)
        <form method="POST" action="{{ route('portal.polls.vote', $poll) }}" class="mt-5 space-y-5" data-submit-loading>
            @csrf

            @foreach ($questions as $question)
                <fieldset class="min-w-0">
                    <legend class="mb-3 break-words text-sm font-semibold text-slate-900">{{ $question->text }}</legend>
                    <div class="grid gap-3 {{ $featured ? 'xl:grid-cols-2' : '' }}">
                        @foreach ($question->options as $option)
                            <label class="flex min-w-0 cursor-pointer items-start gap-3 rounded-lg border border-slate-200 px-3 py-3 text-sm text-slate-700 transition hover:border-blue-200 hover:bg-blue-50/60">
                                <input
                                    type="radio"
                                    name="{{ $question->id ? 'answers['.$question->id.']' : 'poll_option_id' }}"
                                    value="{{ $option->id }}"
                                    @checked((string) old($question->id ? 'answers.'.$question->id : 'poll_option_id') === (string) $option->id)
                                    class="mt-1 h-4 w-4 shrink-0 border-slate-300 text-[var(--komsije-primary)] focus:ring-[var(--komsije-primary)]"
                                    required
                                >
                                <span class="min-w-0 break-words leading-5">{{ $option->text }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error($question->id ? 'answers.'.$question->id : 'poll_option_id')
                        <p class="mt-2 text-sm font-medium text-rose-600">{{ $message }}</p>
                    @enderror
                </fieldset>
            @endforeach

            @error('answers')
                <p class="text-sm font-medium text-rose-600">{{ $message }}</p>
            @enderror

            <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-[var(--komsije-primary)] px-4 py-3 text-sm font-medium text-white transition hover:bg-blue-700 sm:w-auto">
                <x-filament::loading-indicator class="hidden h-4 w-4 animate-spin" data-submit-loader />
                <span data-submit-label>{{ __('Submit vote') }}</span>
            </button>
        </form>
    @elseif (! $isOpen)
        <div class="mt-5 space-y-5">
            @foreach ($poll->results() as $result)
                <section class="min-w-0">
                    <h4 class="break-words text-sm font-semibold text-slate-900">{{ $result['question'] }}</h4>
                    @if ($poll->majority_type !== null)
                        <p class="mt-1 text-xs font-medium {{ $result['successful'] ? 'text-emerald-700' : 'text-rose-700' }}">{{ $result['successful'] ? __('Successful') : __('Unsuccessful') }}</p>
                    @endif
                    <dl class="mt-2 divide-y divide-slate-100">
                        @foreach ($result['options'] as $option)
                            <div class="flex items-start justify-between gap-3 py-2 text-sm">
                                <dt class="min-w-0 break-words text-slate-700">{{ $option['option'] }}</dt>
                                <dd class="shrink-0 font-semibold text-slate-600">{{ trans_choice(':count vote|:count votes', $option['votes'], ['count' => $option['votes']]) }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </section>
            @endforeach
        </div>
    @elseif ($hasVoted)
        <p class="mt-4 text-sm font-medium text-emerald-700">{{ __('You already voted.') }}</p>
    @else
        <p class="mt-4 text-sm text-slate-600">{{ __('You are not eligible to vote in this poll.') }}</p>
    @endif
</article>
