<?php

namespace App\Console\Commands;

use App\Mail\TrialEndingMail;
use App\Models\Subscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendTrialEndingCommand extends Command
{
    protected $signature = 'secp:send-trial-ending {--dry-run : List recipients without sending}';

    protected $description = 'Remind trial users a couple of days before their free trial ends';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        // Trials ending within the next 2 days, not yet reminded.
        $subs = Subscription::query()
            ->where('status', 'trialing')
            ->whereNotNull('trial_ends_at')
            ->whereNull('trial_ending_notified_at')
            ->whereBetween('trial_ends_at', [now(), now()->addDays(2)])
            ->with('owner')
            ->get();

        $sent = 0;
        foreach ($subs as $sub) {
            $user = $sub->owner;
            if (! $user || ! filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            $daysLeft = max(1, (int) ceil(now()->floatDiffInDays($sub->trial_ends_at)));

            if ($dryRun) {
                $this->line("[DRY] → {$user->email} ({$daysLeft}d left)");

                continue;
            }

            try {
                Mail::to($user->email)->send(new TrialEndingMail($user, $daysLeft));
                $sub->forceFill(['trial_ending_notified_at' => now()])->save();
                $sent++;
            } catch (\Throwable $e) {
                Log::error('[TrialEnding] send failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);
                $this->warn("FAIL {$user->email}: {$e->getMessage()}");
            }
        }

        $this->info($dryRun ? "Dry run: {$subs->count()} would be reminded." : "Sent {$sent} trial-ending reminder(s).");
        Log::info('[TrialEnding] run complete', ['candidates' => $subs->count(), 'sent' => $sent, 'dry_run' => $dryRun]);

        return self::SUCCESS;
    }
}
