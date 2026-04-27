@php
    $compact = $compact ?? false;
    $mobileLabelHidden = $mobileLabelHidden ?? false;

    $wrapperClasses = $compact
        ? 'komsije-pill flex items-center gap-1.5 rounded-2xl px-2.5 py-2.5 sm:gap-2 sm:px-3 sm:py-3'
        : 'komsije-pill flex items-center gap-2 rounded-2xl px-3 py-3';

    $labelClasses = $compact
        ? ($mobileLabelHidden
            ? 'hidden text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500 sm:inline'
            : 'text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500')
        : 'text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500';

    $selectClasses = $compact
        ? 'w-14 bg-transparent text-xs font-medium text-slate-700 outline-none sm:w-auto sm:text-sm'
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