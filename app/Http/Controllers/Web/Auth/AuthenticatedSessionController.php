<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Auth;

use App\Http\Requests\Auth\LoginRequest;
use App\Models\Building;
use App\Support\Localization\SiteLocale;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class AuthenticatedSessionController
{
    use AuthorizesRequests;

    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->safe()->only(['email', 'password']);

        if (! Auth::attempt($credentials, (bool) $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => [__('The provided credentials are incorrect.')],
            ]);
        }

        $request->session()->regenerate();

        $user = $request->user();
        $locale = $user?->siteLocale() ?? SiteLocale::default();

        $request->session()->put(SiteLocale::SESSION_KEY, $locale);
        Cookie::queue(cookie()->forever(SiteLocale::COOKIE_NAME, $locale));

        $buildingId = Building::query()
            ->when(! $user->isSuperAdmin(), fn ($query) => $query->whereHas('users', fn ($buildingQuery) => $buildingQuery->whereKey($user->getKey())))
            ->orderBy('name')
            ->value('id');

        if ($buildingId !== null) {
            $request->session()->put('current_building_id', $buildingId);
        } else {
            $request->session()->forget('current_building_id');
        }

        return redirect()->intended(route('portal.dashboard'));
    }

    public function destroy(): RedirectResponse
    {
        Auth::guard('web')->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('login');
    }
}