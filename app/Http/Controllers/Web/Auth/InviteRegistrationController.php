<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Auth;

use App\Http\Requests\Auth\AcceptInviteRequest;
use App\Models\User;
use App\Services\InviteService;
use App\Support\Localization\SiteLocale;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;

final class InviteRegistrationController
{
    public function __construct(private readonly InviteService $invites)
    {
    }

    public function show(string $token): View|Response
    {
        $invite = $this->invites->findByToken($token);

        if ($invite === null || ! $invite->isValid()) {
            return response()->view('auth.invite-invalid', [
                'invite' => $invite,
            ], $invite === null ? 404 : 410);
        }

        $hasExistingAccount = User::query()->where('email', $invite->email)->exists();

        return view('auth.invite-register', [
            'invite' => $invite,
            'hasExistingAccount' => $hasExistingAccount,
        ]);
    }

    public function store(AcceptInviteRequest $request, string $token): RedirectResponse
    {
        $invite = $this->invites->findByToken($token);

        if ($invite === null || ! $invite->isValid()) {
            return redirect()->route('invite.show', ['token' => $token]);
        }

        $user = $this->invites->accept($invite, $request->validated());

        Auth::login($user);
        $request->session()->regenerate();

        $locale = $user->siteLocale() ?? SiteLocale::default();

        $request->session()->put(SiteLocale::SESSION_KEY, $locale);
        $request->session()->put('current_building_id', $invite->building_id);

        Cookie::queue(cookie()->forever(SiteLocale::COOKIE_NAME, $locale));

        return redirect()->route('portal.dashboard')->with('status', __('Dobro došli u Komšije.'));
    }
}