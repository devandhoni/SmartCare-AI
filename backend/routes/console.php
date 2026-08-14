<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;



Artisan::command('inspire', function () {

    $this->comment(
        Inspiring::quote()
    );

})->purpose('Display an inspiring quote');





/*
|--------------------------------------------------------------------------
| AI Health Monitoring Scheduler
|--------------------------------------------------------------------------
|
| Automatically runs AI resident monitoring every 5 minutes.
|
|--------------------------------------------------------------------------
*/


Schedule::command('monitor:health')
    ->everyFiveMinutes();