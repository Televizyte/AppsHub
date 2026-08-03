<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/**
 * Push notification scheduler
 * Runs every minute and dispatches due pushes into the queue.
 */
Schedule::command('push:dispatch-due --limit=50')
    ->everyMinute()
    ->withoutOverlapping()
    ->onOneServer();
