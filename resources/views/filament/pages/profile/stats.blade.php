<div class="grid gap-4 md:grid-cols-3">
    <div class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-white/10 dark:bg-white/5">
        <p class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ __('Total tickets') }}</p>
        <p class="mt-3 text-3xl font-semibold text-slate-950 dark:text-white">{{ $stats['total'] }}</p>
    </div>
    <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 dark:border-amber-400/20 dark:bg-amber-500/10">
        <p class="text-sm font-medium text-amber-800 dark:text-amber-200">{{ __('Active tickets') }}</p>
        <p class="mt-3 text-3xl font-semibold text-amber-900 dark:text-amber-100">{{ $stats['active'] }}</p>
    </div>
    <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 dark:border-emerald-400/20 dark:bg-emerald-500/10">
        <p class="text-sm font-medium text-emerald-800 dark:text-emerald-200">{{ __('Resolved tickets') }}</p>
        <p class="mt-3 text-3xl font-semibold text-emerald-900 dark:text-emerald-100">{{ $stats['resolved'] }}</p>
    </div>
</div>