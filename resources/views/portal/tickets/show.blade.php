@extends('portal.layouts.app')

@section('title', $ticket->title)

@section('content')
    <section class="grid gap-6 lg:grid-cols-[minmax(0,1.05fr)_minmax(0,0.95fr)]">
        <article class="min-w-0 overflow-hidden rounded-[2rem] border border-white/70 bg-white/80 p-6 shadow-xl shadow-slate-900/8 backdrop-blur sm:p-8">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded-full bg-slate-950 px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] text-white">{{ $ticket->status->label() }}</span>
                        <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] text-amber-950">{{ $ticket->priority->label() }}</span>
                    </div>
                    <h1 class="mt-4 break-words text-2xl font-semibold text-slate-950 sm:text-3xl">{{ $ticket->title }}</h1>
                </div>

                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('portal.tickets.index') }}" class="rounded-full border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:border-slate-950 hover:text-slate-950">{{ __('Back') }}</a>
                    @can('update', $ticket)
                        <a href="{{ route('portal.tickets.edit', $ticket) }}" class="rounded-full bg-slate-950 px-4 py-2 text-sm font-medium text-white transition hover:bg-sky-900">{{ __('Edit') }}</a>
                    @endcan
                </div>
            </div>

            <p class="mt-6 break-words text-base leading-8 text-slate-700">{{ $ticket->description }}</p>

            <div class="mt-8 grid gap-4 sm:grid-cols-2">
                <div class="rounded-3xl border border-slate-200 bg-slate-50 p-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ __('Reporter') }}</p>
                    <p class="mt-2 text-sm font-medium text-slate-950">{{ $ticket->reporter?->name ?? __('Unknown') }}</p>
                </div>
                <div class="rounded-3xl border border-slate-200 bg-slate-50 p-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ __('Assigned manager') }}</p>
                    <p class="mt-2 text-sm font-medium text-slate-950">{{ $ticket->assignee?->name ?? __('Unassigned') }}</p>
                </div>
                <div class="rounded-3xl border border-slate-200 bg-slate-50 p-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ __('Apartment') }}</p>
                    <p class="mt-2 text-sm font-medium text-slate-950">{{ $ticket->apartment?->number ?? __('Not linked') }}</p>
                </div>
                <div class="rounded-3xl border border-slate-200 bg-slate-50 p-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ __('Created') }}</p>
                    <p class="mt-2 text-sm font-medium text-slate-950">{{ $ticket->created_at->translatedFormat('M j, Y H:i') }}</p>
                </div>
            </div>

            <div class="mt-8">
                <h2 class="text-lg font-semibold text-slate-950">{{ __('Attachments') }}</h2>
                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    @forelse ($ticket->attachments as $attachment)
                        <a href="{{ asset('storage/' . $attachment->path) }}" target="_blank" class="break-all rounded-3xl border border-slate-200 bg-slate-50 p-4 text-sm font-medium text-slate-700 transition hover:border-slate-950 hover:text-slate-950">{{ $attachment->original_name }}</a>
                    @empty
                        <p class="rounded-3xl border border-dashed border-slate-300 px-4 py-6 text-sm text-slate-500">{{ __('No images uploaded for this ticket.') }}</p>
                    @endforelse
                </div>
            </div>
        </article>

        <div class="min-w-0 space-y-6">
            <article class="min-w-0 overflow-hidden rounded-[2rem] border border-white/70 bg-white/80 p-6 shadow-xl shadow-slate-900/8 backdrop-blur sm:p-8">
                <h2 class="text-xl font-semibold text-slate-950">{{ __('Conversation') }}</h2>

                <div class="mt-6 space-y-4">
                    @forelse ($ticket->comments as $comment)
                        <div class="rounded-3xl border border-slate-200 bg-slate-50 p-5">
                            <div class="flex items-center justify-between gap-3">
                                <p class="text-sm font-semibold text-slate-950">{{ $comment->user?->name ?? __('Unknown user') }}</p>
                                <p class="text-xs uppercase tracking-[0.16em] text-slate-500">{{ $comment->created_at->diffForHumans() }}</p>
                            </div>
                            <p class="mt-3 break-words text-sm leading-7 text-slate-700">{{ $comment->body }}</p>
                        </div>
                    @empty
                        <p class="rounded-3xl border border-dashed border-slate-300 px-4 py-6 text-sm text-slate-500">{{ __('No comments yet.') }}</p>
                    @endforelse
                </div>

                @can('comment', $ticket)
                    <form method="POST" action="{{ route('portal.tickets.comments.store', $ticket) }}" class="mt-6 space-y-4">
                        @csrf
                        <input type="hidden" name="building_id" value="{{ $currentBuilding->getKey() }}">
                        <div>
                            <label for="body" class="mb-2 block text-sm font-medium text-slate-700">{{ __('Add comment') }}</label>
                            <textarea id="body" name="body" rows="4" required class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 outline-none transition focus:border-sky-500 focus:ring-4 focus:ring-sky-500/15">{{ old('body') }}</textarea>
                            @error('body')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
                        </div>
                        <button type="submit" class="rounded-full bg-slate-950 px-5 py-3 text-sm font-semibold text-white transition hover:bg-sky-900">{{ __('Post comment') }}</button>
                    </form>
                @endcan
            </article>

            <article class="min-w-0 overflow-hidden rounded-[2rem] border border-white/70 bg-white/80 p-6 shadow-xl shadow-slate-900/8 backdrop-blur sm:p-8">
                <h2 class="text-xl font-semibold text-slate-950">{{ __('Status history') }}</h2>

                <div class="card-deck mt-6" data-card-deck>
                    <div class="card-deck__scroller" data-card-deck-scroller>
                        @forelse ($ticket->statusHistory as $entry)
                            <div class="rounded-3xl border border-slate-200 bg-slate-50 p-5">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-sm font-semibold text-slate-950">{{ $entry->actor?->name ?? __('System') }}</p>
                                    <p class="text-xs uppercase tracking-[0.16em] text-slate-500">{{ $entry->created_at->translatedFormat('M j, Y H:i') }}</p>
                                </div>
                                <p class="mt-3 text-sm text-slate-700">{{ $entry->from_status?->label() ?? __('None') }} → {{ $entry->to_status?->label() ?? __('None') }}</p>
                                {{-- @if ($entry->note)
                                    <p class="mt-2 text-sm leading-7 text-slate-600">{{ $entry->note }}</p>
                                @endif --}}
                            </div>
                        @empty
                            <p class="rounded-3xl border border-dashed border-slate-300 px-4 py-6 text-sm text-slate-500">{{ __('No status changes have been recorded yet.') }}</p>
                        @endforelse
                    </div>
                    <p class="card-deck__counter" data-card-deck-counter aria-live="polite">1/{{ $ticket->statusHistory->count() }}</p>
                </div>
            </article>
        </div>
    </section>
@endsection