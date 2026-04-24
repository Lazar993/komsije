<?php

declare(strict_types=1);

namespace App\Support\Localization;

final class SiteLocale
{
    public const COOKIE_NAME = 'site_locale';

    public const SESSION_KEY = 'site_locale';

    /**
     * @return array<string, array{app_locale?: string, label?: string, name?: string}>
     */
    public static function supported(): array
    {
        $supported = config('app.supported_locales', []);

        return is_array($supported) && $supported !== []
            ? $supported
            : [
                'sr' => ['app_locale' => 'sr_Latn', 'label' => 'SR', 'name' => 'Serbian'],
                'en' => ['app_locale' => 'en', 'label' => 'EN', 'name' => 'English'],
            ];
    }

    /**
     * @return list<string>
     */
    public static function choices(): array
    {
        return array_keys(static::supported());
    }

    public static function default(): string
    {
        return array_key_first(static::supported()) ?? 'sr';
    }

    public static function isSupported(?string $locale): bool
    {
        return is_string($locale) && array_key_exists($locale, static::supported());
    }

    public static function sanitize(?string $locale): string
    {
        return static::isSupported($locale) ? $locale : static::default();
    }

    public static function appLocale(?string $locale): string
    {
        $locale = static::sanitize($locale);

        return static::supported()[$locale]['app_locale'] ?? $locale;
    }
}