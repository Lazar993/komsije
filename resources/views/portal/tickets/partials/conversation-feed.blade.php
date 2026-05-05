<div class="max-h-[34rem] space-y-3 overflow-y-auto pr-1" data-ticket-conversation-feed>
    @forelse ($conversation as $comment)
        @php
            $isOwnMessage = $comment->user_id === $currentUserId;
            $speakerName = $comment->user?->name ?? __('Unknown user');
            $speakerLabel = $comment->user_id === $ticket->assigned_to
                ? __('Manager')
                : ($comment->user_id === $ticket->reported_by ? __('Tenant') : __('Participant'));
            $avatar = \Illuminate\Support\Str::of($speakerName)
                ->split('/\s+/')
                ->filter()
                ->take(2)
                ->map(fn (string $part): string => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($part, 0, 1)))
                ->join('');
        @endphp
        <div class="flex {{ $isOwnMessage ? 'justify-end' : 'justify-start' }}">
            <div class="flex max-w-[92%] items-end gap-3 sm:max-w-[85%] {{ $isOwnMessage ? 'flex-row-reverse' : '' }}">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full {{ $isOwnMessage ? 'bg-slate-950 text-white' : 'bg-white text-slate-700 shadow-sm shadow-slate-900/5 ring-1 ring-slate-200' }} text-xs font-semibold uppercase tracking-[0.18em]">
                    {{ $avatar !== '' ? $avatar : 'U' }}
                </div>

                <div class="rounded-[1.6rem] px-4 py-3 shadow-sm {{ $isOwnMessage ? 'bg-slate-950 text-white shadow-slate-900/15' : 'border border-slate-200 bg-white text-slate-800 shadow-slate-900/5' }}">
                    <div class="flex flex-wrap items-center gap-2 {{ $isOwnMessage ? 'justify-end' : '' }}">
                        <p class="text-sm font-semibold {{ $isOwnMessage ? 'text-white' : 'text-slate-950' }}">
                            {{ $isOwnMessage ? __('You') : $speakerName }}
                        </p>
                        <span class="rounded-full px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] {{ $isOwnMessage ? 'bg-white/12 text-slate-100' : 'bg-slate-100 text-slate-500' }}">{{ $speakerLabel }}</span>
                        <span class="text-[11px] font-medium uppercase tracking-[0.16em] {{ $isOwnMessage ? 'text-slate-300' : 'text-slate-400' }}">{{ $comment->created_at->translatedFormat('M j, H:i') }}</span>
                    </div>
                    <p class="mt-2 break-words text-sm leading-7 {{ $isOwnMessage ? 'text-slate-100' : 'text-slate-700' }}">{{ $comment->body }}</p>
                </div>
            </div>
        </div>
    @empty
        <div class="rounded-[1.5rem] border border-dashed border-slate-300 bg-white/70 px-4 py-8 text-center" data-ticket-conversation-empty>
            <p class="text-sm font-medium text-slate-700">{{ __('No messages yet.') }}</p>
            <p class="mt-2 text-sm leading-6 text-slate-500">{{ __('Start the conversation so the tenant and manager can resolve this faster.') }}</p>
        </div>
    @endforelse
</div>