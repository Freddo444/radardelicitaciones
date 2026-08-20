<?php

namespace App\Console\Commands;

use App\Mail\SubscriptionEndedMail;
use App\Models\Subscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ReconcileSubscriptionsCommand extends Command
{
    protected $signature = 'subscriptions:reconcile {--dry-run : Report without changing anything}';

    protected $description = 'Enforce dunning grace expiry and flag stale active subscriptions (missed-webhook safety net)';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        // 1) Grace window lapsed on a past_due sub → suspend + notify.
        $lapsed = Subscription::query()
            ->where('status', 'past_due')
            ->whereNotNull('grace_ends_at')
            ->where('grace_ends_at', '<', now())
            ->with('owner')
            ->get();

        $suspended = 0;
        foreach ($lapsed as $sub) {
            if ($dryRun) {
                $this->line("[DRY] suspend #{$sub->id} (grace ended {$sub->grace_ends_at->toDateString()})");

                continue;
            }

            $sub->update(['status' => 'suspended']);
            $owner = $sub->owner;
            if ($owner && filter_var($owner->email, FILTER_VALIDATE_EMAIL)) {
                try {
                    Mail::to($owner->email)->send(new SubscriptionEndedMail($sub, 'suspended'));
                } catch (\Throwable $e) {
                    Log::error('[Reconcile] suspend notice failed', ['subscription' => $sub->id, 'error' => $e->getMessage()]);
                }
            }
            $suspended++;
        }

        // 2) Stale active subs whose period ended days ago with no renewal —
        //    likely a missed gateway webhook. Flag for manual review; never
        //    auto-cancel, to avoid cutting off a customer who actually paid.
        $stale = Subscription::query()
            ->where('status', 'active')
            ->whereNotNull('current_period_end')
            ->where('current_period_end', '<', now()->subDays(3))
            ->pluck('id');

        if ($stale->isNotEmpty()) {
            Log::warning('[Reconcile] stale active subscriptions — verify against gateway', [
                'subscription_ids' => $stale->all(),
            ]);
            $this->warn($stale->count().' active subscription(s) past their period end — logged for manual review.');
        }

        $this->info($dryRun
            ? "Dry run: {$lapsed->count()} would be suspended; {$stale->count()} stale."
            : "Suspended {$suspended} past-due subscription(s); {$stale->count()} stale flagged.");

        Log::info('[Reconcile] run complete', ['suspended' => $suspended, 'stale' => $stale->count(), 'dry_run' => $dryRun]);

        return self::SUCCESS;
    }
}
