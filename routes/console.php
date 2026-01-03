<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Schedule automatic email fetching
Schedule::command('freescout:fetch-emails')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->onOneServer()
    ->runInBackground();

// Schedule daily security audit
Schedule::command('security:audit')
    ->dailyAt('08:00')
    ->runInBackground();
