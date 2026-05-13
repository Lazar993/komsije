<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Support\NotificationLaunchUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class NotificationLaunchController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $target = NotificationLaunchUrl::normalize($request->query('target'));

        if ($target === null) {
            return $request->user() !== null
                ? redirect()->route('portal.dashboard')
                : redirect()->route('login');
        }

        if ($request->user() !== null) {
            return redirect()->to($target);
        }

        $request->session()->put('url.intended', $target);

        return redirect()->route('login');
    }
}