<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

$php = 'php -d memory_limit=4G ' . base_path('artisan');

Schedule::exec("{$php} secp:poll")
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/secp-poll.log'));

// Periodic digest — per-company frequency checked inside the command
Schedule::exec("{$php} secp:send-digest")
    ->hourly()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/secp-digest.log'));

Schedule::exec("{$php} secp:scrape")
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/secp-scrape.log'));

Schedule::exec("{$php} secp:sync-providers")
    ->weekly()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/secp-sync-providers.log'));

Schedule::exec("{$php} secp:sync-contracts")
    ->monthly()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/secp-sync-contracts.log'));

Schedule::exec("{$php} secp:sync-pacc")
    ->monthly()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/secp-sync-pacc.log'));

Schedule::exec("{$php} secp:sync-institutions")
    ->quarterly()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/secp-sync-institutions.log'));

Schedule::exec("{$php} secp:import-catalog")
    ->monthly()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/secp-import-catalog.log'));

Schedule::exec("{$php} telescope:prune --hours=72")->daily();
