<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(
        Inspiring::quote()
    );
})->purpose(
    'Display an inspiring quote'
);


/*
|--------------------------------------------------------------------------
| Step 53.8B
| Daily Executive AI Intelligence Snapshot
|--------------------------------------------------------------------------
|
| Captures one executive intelligence snapshot near the end of each day.
|
| The command itself also protects against duplicate same-day snapshots.
|
*/

Schedule::command(
    'ai:capture-executive-snapshot'
)
->dailyAt('23:55')
->withoutOverlapping()
->onOneServer();