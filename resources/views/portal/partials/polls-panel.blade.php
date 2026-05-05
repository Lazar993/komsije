@if ($polls->isNotEmpty())
    <article class="komsije-surface min-w-0 overflow-hidden rounded-[2rem] p-6 sm:p-7 xl:col-span-2">
        <div class="flex items-center justify-between gap-4">
            <div class="min-w-0">
                <p class="text-sm font-semibold text-[var(--komsije-primary)]">{{ __('Ankete') }}</p>
                <h2 class="mt-1 text-2xl font-semibold tracking-tight text-slate-950">{{ __('Aktivne ankete') }}</h2>
            </div>
            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600">{{ __('Aktivno: :count', ['count' => $polls->count()]) }}</span>
        </div>

        <div class="mt-5 grid gap-4 lg:grid-cols-2">
            @foreach ($polls as $poll)
                <div @class([
                    'lg:col-span-2' => $loop->first && $polls->count() === 3,
                ])>
                    <x-portal.poll-card :poll="$poll" :featured="$loop->first && $polls->count() === 3" />
                </div>
            @endforeach
        </div>
    </article>
@endif
