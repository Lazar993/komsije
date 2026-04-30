<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

final class EventServiceProvider extends ServiceProvider
{
    /**
     * Listeners are resolved by Laravel's automatic event discovery (any class
     * in app/Listeners with a typed handle() parameter is matched to that
     * event automatically). Do NOT also list them here - that would register
     * them twice and every queued listener would run twice.
     */
    protected $listen = [];
}
