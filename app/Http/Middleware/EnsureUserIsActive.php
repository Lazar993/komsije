<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks users whose account has been deactivated from using an existing
 * session or API token, so deactivation takes effect immediately.
 */
final class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response|RedirectResponse
    {
        $user = $request->user();

        if ($user !== null && ! $user->isActive()) {
            if ($request->expectsJson()) {
                $token = $user->currentAccessToken();

                if ($token instanceof PersonalAccessToken) {
                    $token->delete();
                }

                throw ValidationException::withMessages([
                    'email' => [__('Your account has been deactivated. Please contact your building manager.')],
                ]);
            }

            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => __('Your account has been deactivated. Please contact your building manager.'),
            ]);
        }

        return $next($request);
    }
}
