<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Models\Page;
use Illuminate\Contracts\View\View;

class PageController
{
    public function show(string $slug): View
    {
        $page = Page::query()->published()->where('slug', $slug)->firstOrFail();

        return view('pages.show', ['page' => $page]);
    }
}
