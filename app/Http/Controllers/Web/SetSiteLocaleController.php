<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Support\Localization\SiteLocale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Validation\Rule;

final class SetSiteLocaleController
{
    public function __invoke(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'locale' => ['required', 'string', Rule::in(SiteLocale::choices())],
        ]);

        $locale = SiteLocale::sanitize($validated['locale']);

        if ($request->user() !== null && $request->user()->locale !== $locale) {
            $request->user()->forceFill(['locale' => $locale])->saveQuietly();
        }

        if ($request->hasSession()) {
            $request->session()->put(SiteLocale::SESSION_KEY, $locale);
        }

        App::setLocale(SiteLocale::appLocale($locale));
        Cookie::queue(cookie()->forever(SiteLocale::COOKIE_NAME, $locale));

        return back();
    }
}