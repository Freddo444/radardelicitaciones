<?php

namespace App\Console\Commands;

use App\Mail\TrialExpiredMail;
use App\Models\Subscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendTrialExpiredCommand extends Command
{
    protected $signature = 'secp:send-trial-expired {--dry-run : List recipients without sending}';

    protected $description = 'Win-back email to trial users whose free trial just expired';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        // Trials that ended in the last 7 days (avoid nagging ancient signups),
        // still on a trialing subscription, not yet sent a win-back.
        $subs = Subscription::query()
            ->where('status', 'trialing')
            ->whereNotNull('trial_ends_at')
            ->whereNull('trial_expired_notified_at')
            ->whereBetween('trial_ends_at', [now()->subDays(7), now()])
            ->with('owner')
            ->get();

        $sent = 0;
        foreach ($subs as $sub) {
            $user = $sub->owner;
            if (! $user || ! filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            if ($dryRun) {
                $this->line("[DRY] → {$user->email}");

                continue;
            }

            try {
                Mail::to($user->email)->send(new TrialExpiredMail($user));
                $sub->forceFill(['trial_expired_notified_at' => now()])->save();
                $sent++;
            } catch (\Throwable $e) {
                Log::error('[TrialExpired] send failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);
                $this->warn("FAIL {$user->email}: {$e->getMessage()}");
            }
        }

        $this->info($dryRun ? "Dry run: {$subs->count()} would be emailed." : "Sent {$sent} win-back email(s).");
        Log::info('[TrialExpired] run complete', ['candidates' => $subs->count(), 'sent' => $sent, 'dry_run' => $dryRun]);

        return self::SUCCESS;
    }
}
