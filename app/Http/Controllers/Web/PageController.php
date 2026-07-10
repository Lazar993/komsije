<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use Illuminate\Support\Facades\Config;

use App\Models\Page;
use Illuminate\Contracts\View\View;

class PageController
{
    public function show(string $slug): View
    {
        $page = Page::query()->published()->where('slug', $slug)->firstOrFail();

        return view('pages.show', ['page' => $page]);
    }

    public function professionals(): View
    {
        $contactEmail = (string) Config::get('mail.join_komsije.address');

        $subject = rawurlencode('Prijava zgrade za probni period - Komšije');
        $contactUrl = 'mailto:'.$contactEmail.'?subject='.$subject;

        return view('pages.za-upravnike', [
            'contactEmail' => $contactEmail,
            'contactUrl' => $contactUrl,
        ]);
    }
}
