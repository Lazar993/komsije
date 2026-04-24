<div class="mt-6 space-y-4">
    @forelse ($announcements as $announcement)
        <div class="rounded-[1.5rem] border border-[var(--komsije-border)] bg-white p-3 shadow-sm">
            <x-portal.announcement-card
                :announcement="$announcement"
                :href="route('portal.announcements.show', $announcement)"
                :unread="! $announcement->is_read"
                :show-state="auth()->user()->isBuildingAdmin($currentBuilding->getKey())"
            />

            <div class="mt-3 flex items-center justify-between gap-3 px-2 pb-1">
                <p class="text-xs uppercase tracking-[0.16em] text-slate-400">{{ __('Pregleda') }}: {{ $announcement->reads_count }}</p>
                @can('update', $announcement)
                    <a href="{{ route('portal.announcements.edit', $announcement) }}" class="inline-flex items-center gap-2 rounded-[1rem] bg-slate-100 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-200">{{ __('Izmeni') }}</a>
                @endcan
            </div>
        </div>
    @empty
        <div class="rounded-[1.5rem] border border-dashed border-slate-300 bg-slate-50 px-5 py-12 text-center text-sm text-slate-500">{{ __('No announcements are available for this building yet.') }}</div>
    @endforelse
</div>

<div class="mt-6">{{ $announcements->links() }}</div>