<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Scheduled commands share the host with MySQL, two PHP-FPM pools and the queue
// worker. A 4G limit exceeded the machine's own RAM, so a runaway command could
// not fail on its own — it grew until the kernel OOM-killer picked a victim, and
// that victim was usually MySQL. Cap it well below total memory so a runaway
// command dies loudly (and lands in Sentry) instead of taking the database down.
// Raise SCHEDULE_MEMORY_LIMIT temporarily if a one-off backfill needs more.
$php = 'php -d memory_limit='.env('SCHEDULE_MEMORY_LIMIT', '768M').' '.base_path('artisan');

// Staggered across the hour: these used to all start at :00, so their peak
// memory landed at the same moment on a host that cannot absorb it.
Schedule::exec("{$php} secp:poll")
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/secp-poll.log'));

// Periodic digest — per-company frequency checked inside the command
Schedule::exec("{$php} secp:send-digest")
    ->hourlyAt(10)
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/secp-digest.log'));

// Nudge trial users who signed up but never finished company setup
Schedule::exec("{$php} secp:send-setup-reminders")
    ->hourlyAt(20)
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/secp-setup-reminders.log'));

Schedule::exec("{$php} secp:scrape")
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/secp-scrape.log'));

Schedule::exec("{$php} secp:backfill-portal-docs")
    ->hourlyAt(40)
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/secp-backfill-portal-docs.log'));

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

Schedule::exec("{$php} billing:reconcile-azul-orphans")
    ->daily()
    ->appendOutputTo(storage_path('logs/billing-reconcile-orphans.log'));

// Enforce dunning-grace expiry + flag stale active subscriptions
Schedule::exec("{$php} subscriptions:reconcile")
    ->dailyAt('03:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/subscriptions-reconcile.log'));

// Lifecycle emails — sent once each per user, gated inside the commands
Schedule::exec("{$php} secp:send-trial-ending")
    ->dailyAt('09:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/lifecycle-trial-ending.log'));

Schedule::exec("{$php} secp:send-trial-expired")
    ->dailyAt('09:15')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/lifecycle-trial-expired.log'));

Schedule::exec("{$php} secp:send-reengagement")
    ->dailyAt('09:30')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/lifecycle-reengagement.log'));

// Proactive vault document expiry alerts (email + Telegram)
Schedule::exec("{$php} secp:send-expiry-alerts")
    ->dailyAt('08:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/expiry-alerts.log'));
