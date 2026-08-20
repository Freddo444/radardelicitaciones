<?php

namespace App\Console\Commands;

use App\Mail\ReEngagementMail;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendReEngagementCommand extends Command
{
    protected $signature = 'secp:send-reengagement {--days=14 : Inactivity threshold} {--dry-run : List recipients without sending}';

    protected $description = 'Nudge active paying users who have not signed in for a while';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $threshold = max(3, (int) $this->option('days'));

        // Paying (active) subscription owners who set up a company, haven't
        // signed in for `threshold` days, and weren't re-engaged in the last 30.
        $users = User::query()
            ->whereNotNull('last_sign_in_at')
            ->where('last_sign_in_at', '<', now()->subDays($threshold))
            ->where(function ($q) {
                $q->whereNull('reengagement_sent_at')
                    ->orWhere('reengagement_sent_at', '<', now()->subDays(30));
            })
            ->whereHas('subscription', fn ($q) => $q->where('status', 'active'))
            ->whereExists(function ($q) {
                $q->selectRaw('1')
                    ->from('companies')
                    ->whereColumn('companies.owner_id', 'users.id');
            })
            ->get();

        $sent = 0;
        foreach ($users as $user) {
            if (! filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            $daysAway = (int) $user->last_sign_in_at->diffInDays(now());

            if ($dryRun) {
                $this->line("[DRY] → {$user->email} ({$daysAway}d away)");

                continue;
            }

            try {
                Mail::to($user->email)->send(new ReEngagementMail($user, $daysAway));
                $user->forceFill(['reengagement_sent_at' => now()])->save();
                $sent++;
            } catch (\Throwable $e) {
                Log::error('[ReEngagement] send failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);
                $this->warn("FAIL {$user->email}: {$e->getMessage()}");
            }
        }

        $this->info($dryRun ? "Dry run: {$users->count()} would be nudged." : "Sent {$sent} re-engagement email(s).");
        Log::info('[ReEngagement] run complete', ['candidates' => $users->count(), 'sent' => $sent, 'dry_run' => $dryRun]);

        return self::SUCCESS;
    }
}
