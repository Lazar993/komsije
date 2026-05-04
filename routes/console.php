<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('notifications:send-digest --frequency=daily')
    ->dailyAt('08:00')
    ->withoutOverlapping();

Schedule::command('notifications:send-digest --frequency=weekly')
    ->weeklyOn(1, '09:00')
    ->withoutOverlapping();

Schedule::command('app:prune-old-content')
    ->dailyAt('03:00')
    ->withoutOverlapping();
