@extends('portal.layouts.app')

@section('title', $ticket->title)

@section('content')
    @php
        $conversation = $ticket->comments->sortBy('created_at')->values();
        $currentUserId = auth()->id();
        $conversationCountLabel = trans_choice(':count message|:count messages', $conversation->count(), ['count' => $conversation->count()]);
        $canSeeIdentity = $canSeeIdentity ?? $ticket->viewerCanSeeIdentity(auth()->user());
        $isAffected = $isAffected ?? false;
        $isPublic = $ticket->isPublic();
    @endphp

    <section class="grid gap-6 lg:grid-cols-[minmax(0,1.05fr)_minmax(0,0.95fr)]">
        <article class="min-w-0 overflow-hidden rounded-[2rem] border border-white/70 bg-white/80 p-6 shadow-xl shadow-slate-900/8 backdrop-blur sm:p-8">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded-full bg-slate-950 px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] text-white">{{ $ticket->status->label() }}</span>
                        <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] text-amber-950">{{ $ticket->priority->label() }}</span>
                        @if ($isPublic)
                            <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] text-blue-800">{{ __('Public') }}</span>
                        @else
                            <span class="rounded-full bg-slate-200 px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] text-slate-700">{{ __('Private') }}</span>
                        @endif
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
                    <p class="mt-2 text-sm font-medium text-slate-950">
                        {{ $canSeeIdentity ? ($ticket->reporter?->name ?? __('Unknown')) : __('Resident reported this issue') }}
                    </p>
                </div>
                <div class="rounded-3xl border border-slate-200 bg-slate-50 p-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ __('Assigned manager') }}</p>
                    <p class="mt-2 text-sm font-medium text-slate-950">{{ $ticket->assignee?->name ?? __('Unassigned') }}</p>
                </div>
                <div class="rounded-3xl border border-slate-200 bg-slate-50 p-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ __('Apartment') }}</p>
                    <p class="mt-2 text-sm font-medium text-slate-950">
                        {{ $canSeeIdentity ? ($ticket->apartment?->number ?? __('Not linked')) : __('Hidden for privacy') }}
                    </p>
                </div>
                <div class="rounded-3xl border border-slate-200 bg-slate-50 p-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ __('Created') }}</p>
                    <p class="mt-2 text-sm font-medium text-slate-950">{{ $ticket->created_at->translatedFormat('M j, Y H:i') }}</p>
                </div>
            </div>

            @if ($isPublic)
                <div class="mt-6 flex flex-wrap items-center justify-between gap-3 rounded-3xl border border-blue-200 bg-blue-50 p-5">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-slate-950">{{ __('Affected residents') }}</p>
                        <p class="mt-1 text-xs text-slate-600">{{ trans_choice(':count resident has confirmed they are also affected.|:count residents have confirmed they are also affected.', (int) ($ticket->affected_count ?? 0), ['count' => (int) ($ticket->affected_count ?? 0)]) }}</p>
                    </div>
                    @can('markAffected', $ticket)
                        <form method="POST" action="{{ route('portal.tickets.affected.toggle', $ticket) }}">
                            @csrf
                            <button type="submit" class="rounded-full bg-slate-950 px-4 py-2 text-sm font-semibold text-white transition hover:bg-sky-900">
                                {{ $isAffected ? __('Remove my mark') : __('I have this issue too') }}
                            </button>
                        </form>
                    @endcan
                </div>
            @endif

            <div class="mt-8">
                <h2 class="text-lg font-semibold text-slate-950">{{ __('Attachments') }}</h2>
                @if (! $canSeeIdentity)
                    <div class="mt-4">
                        <p class="rounded-3xl border border-dashed border-slate-300 px-4 py-6 text-sm text-slate-500">{{ __('Attachments are visible to the reporter and building managers only.') }}</p>
                    </div>
                @else
                    @php
                        $ticketGalleryId = 'ticket-' . $ticket->getKey() . '-attachments';
                        $imageAttachments = $ticket->attachments->filter(fn ($a) => str_starts_with((string) ($a->mime_type ?? ''), 'image/'))->values();
                        $otherAttachments = $ticket->attachments->reject(fn ($a) => str_starts_with((string) ($a->mime_type ?? ''), 'image/'))->values();
                    @endphp

                    @if ($ticket->attachments->isEmpty())
                        <div class="mt-4">
                            <p class="rounded-3xl border border-dashed border-slate-300 px-4 py-6 text-sm text-slate-500">{{ __('No images uploaded for this ticket.') }}</p>
                        </div>
                    @else
                        @if ($imageAttachments->isNotEmpty())
                            <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3" data-lightbox-gallery="{{ $ticketGalleryId }}">
                                @foreach ($imageAttachments as $attachment)
                                    @php $url = asset('storage/' . $attachment->path); @endphp
                                    <button
                                        type="button"
                                        class="group relative aspect-square overflow-hidden rounded-2xl border border-slate-200 bg-slate-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-500"
                                        data-lightbox-trigger="{{ $ticketGalleryId }}"
                                        data-lightbox-src="{{ $url }}"
                                        data-lightbox-alt="{{ $attachment->original_name }}"
                                        aria-label="{{ __('Open image :name', ['name' => $attachment->original_name]) }}"
                                    >
                                        <img src="{{ $url }}" alt="{{ $attachment->original_name }}" loading="lazy" class="h-full w-full object-cover transition group-hover:scale-[1.02]">
                                    </button>
                                @endforeach
                            </div>
                        @endif

                        @if ($otherAttachments->isNotEmpty())
                            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                @foreach ($otherAttachments as $attachment)
                                    <a href="{{ asset('storage/' . $attachment->path) }}" target="_blank" rel="noopener" class="break-all rounded-3xl border border-slate-200 bg-slate-50 p-4 text-sm font-medium text-slate-700 transition hover:border-slate-950 hover:text-slate-950">{{ $attachment->original_name }}</a>
                                @endforeach
                            </div>
                        @endif
                    @endif
                @endif
            </div>
        </article>

        <div class="min-w-0 space-y-6">
            @if ($canSeeIdentity)
            <article class="min-w-0 overflow-hidden rounded-[2rem] border border-white/70 bg-white/80 p-6 shadow-xl shadow-slate-900/8 backdrop-blur sm:p-8">
                <div
                    data-ticket-conversation
                    data-refresh-url="{{ route('portal.tickets.show', $ticket) }}"
                    data-refresh-interval="15000"
                    data-comment-count="{{ $conversation->count() }}"
                    data-latest-comment-id="{{ $conversation->last()?->getKey() ?? '' }}"
                    data-status-idle="{{ __('Messages stay on this ticket so everyone sees the same timeline.') }}"
                    data-status-sending="{{ __('Sending...') }}"
                    data-status-sent="{{ __('Message sent.') }}"
                    data-status-validation="{{ __('Please review the message and try again.') }}"
                    data-status-failed="{{ __('Message delivery failed.') }}"
                    data-error-send="{{ __('Unable to send the message right now. Reload the page and try again.') }}"
                    data-error-generic="{{ __('Unable to send the message right now.') }}"
                >
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="text-xl font-semibold text-slate-950">{{ __('Conversation') }}</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-500">{{ __('Keep the tenant and manager aligned with quick back-and-forth updates on this ticket.') }}</p>
                    </div>
                    <span class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] text-slate-500" data-ticket-conversation-count>{{ $conversationCountLabel }}</span>
                </div>

                <div class="mt-6 rounded-[1.75rem] border border-slate-200/80 bg-[linear-gradient(180deg,rgba(248,250,252,0.92),rgba(241,245,249,0.88))] p-3 sm:p-4" data-ticket-conversation-shell>
                    @include('portal.tickets.partials.conversation-feed', ['conversation' => $conversation, 'currentUserId' => $currentUserId, 'ticket' => $ticket])
                </div>
                </div>

                @can('comment', $ticket)
                    <form method="POST" action="{{ route('portal.tickets.comments.store', $ticket) }}" class="mt-6 rounded-[1.75rem] border border-slate-200 bg-white p-4 shadow-sm shadow-slate-900/5 sm:p-5" data-ticket-conversation-form>
                        @csrf
                        <input type="hidden" name="building_id" value="{{ $currentBuilding->getKey() }}">
                        <div class="flex items-start gap-3">
                            <div class="mt-1 flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-slate-950 text-xs font-semibold uppercase tracking-[0.18em] text-white">
                                {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr(auth()->user()?->name ?? 'U', 0, 1)) }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <label for="body" class="mb-2 block text-sm font-medium text-slate-700">{{ __('Reply') }}</label>
                                <textarea id="body" name="body" rows="4" required placeholder="{{ __('Write an update, question, or next step...') }}" class="w-full rounded-[1.4rem] border border-slate-300 bg-slate-50 px-4 py-3 outline-none transition placeholder:text-slate-400 focus:border-sky-500 focus:bg-white focus:ring-4 focus:ring-sky-500/15">{{ old('body') }}</textarea>
                                <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                                    <p class="text-xs leading-5 text-slate-500" data-ticket-conversation-form-status>{{ __('Messages stay on this ticket so everyone sees the same timeline.') }}</p>
                                    <button type="submit" class="rounded-full bg-slate-950 px-5 py-3 text-sm font-semibold text-white transition hover:bg-sky-900 disabled:cursor-not-allowed disabled:opacity-60" data-ticket-conversation-submit>{{ __('Send message') }}</button>
                                </div>
                                @error('body')<p class="mt-2 text-sm text-rose-600" data-ticket-conversation-error>{{ $message }}</p>@enderror
                                <p class="mt-2 hidden text-sm text-rose-600" data-ticket-conversation-error></p>
                            </div>
                        </div>
                    </form>
                @endcan
            </article>
            @else
                <article class="min-w-0 overflow-hidden rounded-[2rem] border border-blue-100 bg-blue-50/60 p-6 text-sm text-slate-600 shadow-sm sm:p-8">
                    <h2 class="text-base font-semibold text-slate-950">{{ __('Conversation hidden') }}</h2>
                    <p class="mt-2 leading-6">{{ __('To protect privacy, the conversation between the reporter and the manager is not shown on public tickets.') }}</p>
                </article>
            @endif

            <article class="min-w-0 overflow-hidden rounded-[2rem] border border-white/70 bg-white/80 p-6 shadow-xl shadow-slate-900/8 backdrop-blur sm:p-8">
                <h2 class="text-xl font-semibold text-slate-950">{{ __('Status history') }}</h2>

                <div class="card-deck mt-6" data-card-deck>
                    <div class="card-deck__scroller" data-card-deck-scroller>
                        @forelse ($ticket->statusHistory as $entry)
                            <div class="rounded-3xl border border-slate-200 bg-slate-50 p-5">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-sm font-semibold text-slate-950">{{ $canSeeIdentity ? ($entry->actor?->name ?? __('System')) : __('Building team') }}</p>
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