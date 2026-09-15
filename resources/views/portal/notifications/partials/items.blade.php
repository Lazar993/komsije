@foreach ($items as $item)
    <li>
        <a
            href="{{ $item['url'] }}"
            data-notification-item
            data-notification-read-url="{{ route('portal.notifications.read', $item['id']) }}"
            class="relative flex gap-3 px-4 py-3 transition hover:bg-blue-50/60 {{ $item['is_read'] ? '' : 'bg-blue-50/40' }}"
        >
            <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-600">
                <x-portal.app-icon :name="$item['icon']" class="h-4 w-4" />
            </span>
            <span class="min-w-0 flex-1">
                <span class="block truncate pr-3 text-sm font-semibold text-slate-900">{{ $item['title'] }}</span>
                @if ($item['message'] !== '')
                    <span class="mt-0.5 block line-clamp-2 text-xs text-slate-500">{{ $item['message'] }}</span>
                @endif
                @if ($item['created_at'])
                    <span class="mt-1 block text-[11px] font-medium text-slate-400">{{ $item['created_at']->diffForHumans() }}</span>
                @endif
            </span>
            @unless ($item['is_read'])
                <span data-notification-dot class="absolute right-3 top-3 h-2 w-2 rounded-full bg-[var(--komsije-primary)]" aria-hidden="true"></span>
            @endunless
        </a>
    </li>
@endforeach
