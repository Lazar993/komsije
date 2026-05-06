<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Localization\SiteLocale;
use Illuminate\Cookie\Middleware\EncryptCookies as Middleware;

final class EncryptCookies extends Middleware
{
    /**
     * @var list<string>
     */
    protected $except = [
        SiteLocale::COOKIE_NAME,
    ];
}