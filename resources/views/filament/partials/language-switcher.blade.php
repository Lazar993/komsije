<form method="POST" action="{{ route('locale.update') }}" class="fi-user-menu-language-switcher px-3 py-2">
    @csrf
    <label for="filament-site-locale" class="sr-only">{{ __('Language') }}</label>
    <select
        id="filament-site-locale"
        name="locale"
        class="w-full rounded-lg border-gray-300 bg-white text-sm text-gray-700 shadow-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
        onchange="this.form.submit()"
        aria-label="{{ __('Language') }}"
    >
        @foreach ($siteLocaleOptions as $localeCode => $localeOption)
            <option value="{{ $localeCode }}" @selected($siteLocale === $localeCode)>
                {{ $localeOption['label'] ?? strtoupper($localeCode) }}
            </option>
        @endforeach
    </select>
</form>