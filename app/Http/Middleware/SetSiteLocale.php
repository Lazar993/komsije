<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Localization\SiteLocale;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

final class SetSiteLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = SiteLocale::sanitize(
            $request->user()?->locale
            ?? ($request->hasSession() ? $request->session()->get(SiteLocale::SESSION_KEY) : null)
            ?? $request->cookie(SiteLocale::COOKIE_NAME),
        );

        $appLocale = SiteLocale::appLocale($locale);

        App::setLocale($appLocale);

        if ($request->hasSession() && $request->session()->get(SiteLocale::SESSION_KEY) !== $locale) {
            $request->session()->put(SiteLocale::SESSION_KEY, $locale);
        }

        if ($request->cookie(SiteLocale::COOKIE_NAME) !== $locale) {
            Cookie::queue(cookie()->forever(SiteLocale::COOKIE_NAME, $locale));
        }

        View::share('siteLocale', $locale);
        View::share('siteLocaleOptions', SiteLocale::supported());

        return $next($request);
    }
}