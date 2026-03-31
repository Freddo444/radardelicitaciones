<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('secp:poll')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/secp-poll.log'));

// Periodic digest — per-company frequency checked inside the command
Schedule::command('secp:send-digest')
    ->hourly()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/secp-digest.log'));

Schedule::command('secp:scrape')
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/secp-scrape.log'));

Schedule::command('secp:sync-providers')
    ->weekly()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/secp-sync-providers.log'));

Schedule::command('secp:sync-contracts')
    ->monthly()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/secp-sync-contracts.log'));

Schedule::command('secp:sync-pacc')
    ->monthly()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/secp-sync-pacc.log'));

Schedule::command('secp:sync-institutions')
    ->quarterly()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/secp-sync-institutions.log'));

Schedule::command('telescope:prune --hours=72')->daily();
