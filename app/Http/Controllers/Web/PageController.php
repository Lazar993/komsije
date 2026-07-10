<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Models\Page;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Config;

class PageController
{
    public function show(string $slug): View
    {
        $page = Page::query()->published()->where('slug', $slug)->firstOrFail();

        return view('pages.show', ['page' => $page]);
    }

    public function professionals(): View
    {
        $contactEmail = (string) Config::get('mail.from.address');

        $subject = rawurlencode('Prijava zgrade za probni period - Komšije');
        $contactUrl = 'mailto:'.$contactEmail.'?subject='.$subject;

        return view('pages.za-upravnike', [
            'contactEmail' => $contactEmail,
            'contactUrl' => $contactUrl,
        ]);
    }
}
