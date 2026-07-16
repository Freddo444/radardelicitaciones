<?php

namespace App\Console\Commands;

use App\Mail\SetupReminderMail;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendSetupRemindersCommand extends Command
{
    protected $signature = 'secp:send-setup-reminders {--dry-run : List recipients without sending}';

    protected $description = 'Nudge trial users who signed up but never created a company to finish setup';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        // Signed up 6–72h ago, still no company, not yet reminded.
        // Lower bound gives them a chance to finish on their own; upper bound
        // avoids nagging stale signups (e.g. after a scheduler outage).
        $users = User::query()
            ->whereNull('setup_reminder_sent_at')
            ->whereHas('subscription', function ($q) {
                $q->where('status', 'trialing')
                    ->whereBetween('created_at', [now()->subHours(72), now()->subHours(6)]);
            })
            ->whereNotExists(function ($q) {
                $q->selectRaw('1')
                    ->from('companies')
                    ->whereColumn('companies.owner_id', 'users.id');
            })
            ->get();

        if ($users->isEmpty()) {
            $this->info('No trial users pending a setup reminder.');

            return self::SUCCESS;
        }

        $sent = 0;
        foreach ($users as $user) {
            if (! filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            if ($dryRun) {
                $this->line("[DRY] → {$user->email}");

                continue;
            }

            try {
                Mail::to($user->email)->send(new SetupReminderMail($user));
                $user->forceFill(['setup_reminder_sent_at' => now()])->save();
                $sent++;
            } catch (\Throwable $e) {
                Log::error('[SetupReminder] send failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);
                $this->warn("FAIL {$user->email}: {$e->getMessage()}");
            }
        }

        $this->info($dryRun ? "Dry run: {$users->count()} would be reminded." : "Sent {$sent} setup reminder(s).");
        Log::info('[SetupReminder] run complete', ['candidates' => $users->count(), 'sent' => $sent, 'dry_run' => $dryRun]);

        return self::SUCCESS;
    }
}
