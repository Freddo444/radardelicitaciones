<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Scheduled commands share the host with MySQL, PHP-FPM and the queue worker.
// The limit was 4G — more than the machine's total RAM — which made it useless
// as a ceiling: a runaway command could never fail on its own budget. This is a
// guardrail, not a fix for any observed incident (the MySQL restarts we chased
// turned out to be unattended-upgrades, not memory pressure). Keep it generous
// enough for a large backlog drain but below total RAM, so a genuine runaway
// dies with a PHP memory error that reaches Sentry.
$php = 'php -d memory_limit='.env('SCHEDULE_MEMORY_LIMIT', '2G').' '.base_path('artisan');

// Staggered across the hour: these all used to start at :00, contending for the
// database, the DGCP API and CPU at the same moment.
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
