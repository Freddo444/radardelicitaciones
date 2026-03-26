<?php

use App\Models\Setting;
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

// Periodic digest — summarizes new bids via email/Telegram
Schedule::call(function () {
    if (Setting::get('digest_enabled') !== '1') {
        return;
    }

    $freq = Setting::get('digest_frequency', 'daily_9am');
    $hour = (int) now()->format('G');

    $shouldRun = match ($freq) {
        'hourly' => true,
        'every_2h' => $hour % 2 === 0,
        'twice_daily' => in_array($hour, [9, 15]),
        'daily_9am' => $hour === 9,
        default => $hour === 9,
    };

    if ($shouldRun) {
        Artisan::call('secp:send-digest');
    }
})->hourly()->appendOutputTo(storage_path('logs/secp-digest.log'));

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
