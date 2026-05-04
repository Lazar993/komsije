@php
    $isBuildingAdmin = auth()->user()->isBuildingAdmin($currentBuilding->getKey());
@endphp

@if ($announcements->isEmpty())
    <div class="mt-6 rounded-[1.5rem] border border-dashed border-slate-300 bg-slate-50 px-5 py-12 text-center text-sm text-slate-500">
        {{ __('No announcements are available for this building yet.') }}
    </div>
@else
    <div class="mt-6 rounded-[1.5rem] border border-[var(--komsije-border)] bg-white">
        <ul class="divide-y divide-slate-100">
            @foreach ($announcements as $announcement)
                @php
                    $unread = ! $announcement->is_read;
                    $important = (bool) $announcement->is_important;
                    $rowBg = $important ? 'bg-amber-50/40 hover:bg-amber-50' : 'hover:bg-slate-50';
                    $accent = $important
                        ? 'bg-amber-400'
                        : ($unread ? 'bg-[var(--komsije-primary)]' : 'bg-transparent');
                @endphp
                <li class="group relative transition first:rounded-t-[1.4rem] last:rounded-b-[1.4rem] {{ $rowBg }}">
                    <span aria-hidden="true" class="absolute inset-y-0 left-0 z-[1] w-1 group-first:rounded-tl-[1.4rem] group-last:rounded-bl-[1.4rem] {{ $accent }}"></span>

                    <div class="relative flex items-start gap-4 px-5 py-4 pl-6 sm:px-6 sm:pl-7">
                        <a href="{{ route('portal.announcements.show', $announcement) }}"
                           class="absolute inset-0 z-0"
                           aria-label="{{ $announcement->title }}"></a>

                        <div class="relative z-[1] min-w-0 flex-1 pointer-events-none">
                            <div class="flex flex-wrap items-center gap-2">
                                @if ($important)
                                    <x-portal.badge :label="__('Važno')" tone="warning" />
                                @endif
                                @if ($unread)
                                    <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-[var(--komsije-primary)]">
                                        <span class="h-1.5 w-1.5 rounded-full bg-[var(--komsije-primary)]"></span>
                                        {{ __('New') }}
                                    </span>
                                @endif
                                @if ($isBuildingAdmin)
                                    <x-portal.badge
                                        :label="$announcement->published_at ? __('Published') : __('Čeka odobrenje')"
                                        :tone="$announcement->published_at ? 'success' : 'warning'"
                                    />
                                @elseif ($announcement->published_at === null)
                                    <x-portal.badge :label="__('Čeka odobrenje')" tone="warning" />
                                @endif
                            </div>

                            <h3 class="mt-1.5 truncate text-base text-slate-950 transition group-hover:text-[var(--komsije-primary)] {{ $unread ? 'font-semibold' : 'font-medium' }}">
                                {{ $announcement->title }}
                            </h3>

                            <p class="mt-1 line-clamp-1 text-sm text-slate-600">
                                {{ \Illuminate\Support\Str::limit(strip_tags($announcement->content), 160) }}
                            </p>

                            <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-400">
                                <span class="font-medium text-slate-500">{{ $announcement->author?->name ?? __('Unknown author') }}</span>
                                <span aria-hidden="true">·</span>
                                <span>{{ $announcement->published_at?->diffForHumans() ?? __('Draft') }}</span>
                                @if ($announcement->published_at)
                                    <span aria-hidden="true" class="hidden sm:inline">·</span>
                                    <span class="hidden sm:inline">{{ $announcement->published_at->translatedFormat('d M Y, H:i') }}</span>
                                @endif
                                <span aria-hidden="true">·</span>
                                <span class="inline-flex items-center gap-1">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="h-3.5 w-3.5">
                                        <path d="M2.5 12s3.5-7 9.5-7 9.5 7 9.5 7-3.5 7-9.5 7S2.5 12 2.5 12Z" />
                                        <circle cx="12" cy="12" r="2.75" />
                                    </svg>
                                    {{ $announcement->reads_count }}
                                </span>
                                @if (($announcement->attachments_count ?? 0) > 0)
                                    <span aria-hidden="true">·</span>
                                    <span class="inline-flex items-center gap-1" title="{{ __('Prilozi') }}">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="h-3.5 w-3.5">
                                            <path d="M21.44 11.05 12.97 19.5a5 5 0 0 1-7.07-7.07l8.49-8.49a3.5 3.5 0 0 1 4.95 4.95l-8.49 8.49a2 2 0 0 1-2.83-2.83l7.78-7.78" />
                                        </svg>
                                        {{ $announcement->attachments_count }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="relative z-[2] flex shrink-0 items-center gap-1 self-center">
                            @can('update', $announcement)
                                <details class="komsije-row-menu relative">
                                    <summary
                                        class="flex h-9 w-9 cursor-pointer list-none items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 [&::-webkit-details-marker]:hidden"
                                        aria-label="{{ __('Akcije') }}"
                                        onclick="event.stopPropagation();"
                                    >
                                        <svg viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5">
                                            <circle cx="12" cy="5.5" r="1.6" />
                                            <circle cx="12" cy="12" r="1.6" />
                                            <circle cx="12" cy="18.5" r="1.6" />
                                        </svg>
                                    </summary>
                                    <div class="absolute right-0 top-full z-30 mt-1 w-44 overflow-hidden rounded-2xl border border-slate-200 bg-white py-1 shadow-lg shadow-slate-900/5">
                                        <a href="{{ route('portal.announcements.edit', $announcement) }}"
                                           class="flex items-center gap-2 px-4 py-2 text-sm text-slate-700 transition hover:bg-slate-50">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                                                <path d="M16.5 4.5a2.121 2.121 0 1 1 3 3L8 19l-4 1 1-4 11.5-11.5Z" />
                                            </svg>
                                            {{ __('Izmeni') }}
                                        </a>
                                    </div>
                                </details>
                            @endcan
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="pointer-events-none hidden h-4 w-4 text-slate-300 transition group-hover:translate-x-0.5 group-hover:text-[var(--komsije-primary)] sm:block">
                                <path d="M9 6l6 6-6 6" />
                            </svg>
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>
    </div>
@endif

<div class="mt-6">{{ $announcements->links() }}</div>

@once
    <script>
        document.addEventListener('click', (event) => {
            document.querySelectorAll('details.komsije-row-menu[open]').forEach((details) => {
                if (! details.contains(event.target)) {
                    details.removeAttribute('open');
                }
            });
        });
    </script>
@endonce