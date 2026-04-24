@php
    $compact = $compact ?? false;

    $wrapperClasses = $compact
        ? 'komsije-pill flex items-center gap-2 rounded-2xl px-3 py-3'
        : 'komsije-pill flex items-center gap-2 rounded-2xl px-3 py-3';

    $labelClasses = $compact
        ? 'text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500'
        : 'text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500';

    $selectClasses = $compact
        ? 'bg-transparent text-sm font-medium text-slate-700 outline-none'
        : 'bg-transparent text-sm font-medium text-slate-700 outline-none';
@endphp

<form method="POST" action="{{ route('locale.update') }}" class="{{ $wrapperClasses }}">
    @csrf
    <label for="site-locale-{{ $compact ? 'compact' : 'default' }}" class="{{ $labelClasses }}">{{ __('Language') }}</label>
    <select id="site-locale-{{ $compact ? 'compact' : 'default' }}" name="locale" class="{{ $selectClasses }}" onchange="this.form.submit()" aria-label="{{ __('Language') }}">
        @foreach ($siteLocaleOptions as $localeCode => $localeOption)
            <option value="{{ $localeCode }}" @selected($siteLocale === $localeCode)>{{ $localeOption['label'] ?? strtoupper($localeCode) }}</option>
        @endforeach
    </select>
</form>