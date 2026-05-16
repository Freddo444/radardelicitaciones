<?php

namespace App\Console\Commands;

use App\Models\PendingRegistration;
use Illuminate\Console\Command;
use Sentry\Severity;

class ReconcileAzulOrphansCommand extends Command
{
    protected $signature = 'billing:reconcile-azul-orphans';

    protected $description = 'Report unclaimed Azul registration payments older than 24 hours';

    public function handle(): int
    {
        $orphans = PendingRegistration::whereNull('claimed_at')
            ->whereNull('refunded_at')
            ->where('created_at', '<', now()->subHours(24))
            ->orderBy('created_at')
            ->get();

        if ($orphans->isEmpty()) {
            $this->info('No orphan Azul payments found.');

            return self::SUCCESS;
        }

        $this->warn("Found {$orphans->count()} orphan Azul payment(s) — card charged, no account created:");

        foreach ($orphans as $orphan) {
            $plan = $orphan->plan;
            $amount = ! empty($plan['charged_usd']) ? 'US$'.number_format((float) $plan['charged_usd'], 2) : 'amount unknown';
            $this->line("  [{$orphan->created_at->toDateTimeString()}] Order: {$orphan->order_number} | RRN: {$orphan->rrn} | {$amount}");
        }

        \Sentry\captureMessage('Orphan Azul payments require admin review', Severity::warning(), [
            'extra' => ['count' => $orphans->count(), 'order_numbers' => $orphans->pluck('order_number')->all()],
        ]);

        return self::SUCCESS;
    }
}
