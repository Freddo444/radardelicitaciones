<?php

namespace App\Console\Commands;

use App\Mail\ReEngagementMail;
use App\Mail\SetupNudgeMail;
use App\Models\Bid;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendReEngagementCommand extends Command
{
    protected $signature = 'secp:send-reengagement
        {--days=14 : Inactivity threshold}
        {--trial-only : Only target users still on a trial}
        {--force : Ignore the 30-day re-engagement cooldown (for a one-off backfill)}
        {--dry-run : List recipients without sending}';

    protected $description = 'Nudge trial and paying users who stopped signing in, showing the matches they missed';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $threshold = max(3, (int) $this->option('days'));

        // Trial and paying owners who set up a company, haven't signed in for
        // `threshold` days, and weren't re-engaged in the last 30.
        $statuses = $this->option('trial-only') ? ['trialing'] : ['active', 'trialing'];
        $cutoff = now()->subDays($threshold);

        $users = User::query()
            // Quiet since their last visit — or since signup, for anyone who
            // registered and never came back at all.
            ->where(function ($q) use ($cutoff) {
                $q->where('last_sign_in_at', '<', $cutoff)
                    ->orWhere(function ($q2) use ($cutoff) {
                        $q2->whereNull('last_sign_in_at')->where('created_at', '<', $cutoff);
                    });
            })
            ->when(! $this->option('force'), function ($q) {
                $q->where(function ($q2) {
                    $q2->whereNull('reengagement_sent_at')
                        ->orWhere('reengagement_sent_at', '<', now()->subDays(30));
                });
            })
            ->whereHas('subscription', fn ($q) => $q->whereIn('status', $statuses))
            ->get();

        $sent = 0;
        $skippedEmpty = 0;
        $skippedDisposable = 0;

        foreach ($users as $user) {
            if (! filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            if ($this->isDisposable($user->email)) {
                $skippedDisposable++;

                continue;
            }

            // Legacy rows can carry neither date; without a reference point there
            // is nothing meaningful to say, so leave them alone.
            $since = $user->last_sign_in_at ?? $user->created_at;
            if (! $since) {
                continue;
            }

            $daysAway = (int) $since->diffInDays(now());

            // Branch on what the user actually did: someone who never set up a
            // company needs a setup nudge, not a list of matches they can't have.
            $hasCompany = $user->companies()->exists();
            $missed = $hasCompany ? $this->missedBids($user) : collect();
            $missedTotal = $hasCompany ? $this->missedBidsQuery($user)?->count() ?? 0 : 0;

            if ($hasCompany && $missed->isEmpty()) {
                // "Come back, we found nothing" is worse than staying quiet.
                $skippedEmpty++;

                continue;
            }

            $mail = $hasCompany
                ? new ReEngagementMail($user, $daysAway, $missed, $missedTotal)
                : new SetupNudgeMail($user, $this->openBidCount());

            if ($dryRun) {
                $kind = $hasCompany ? "{$missedTotal} missed" : 'no company — setup nudge';
                $this->line("[DRY] → {$user->email} ({$daysAway}d away, {$kind})");

                continue;
            }

            try {
                Mail::to($user->email)->send($mail);
                $user->forceFill(['reengagement_sent_at' => now()])->save();
                $sent++;
            } catch (\Throwable $e) {
                Log::error('[ReEngagement] send failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);
                $this->warn("FAIL {$user->email}: {$e->getMessage()}");
            }
        }

        $this->info($dryRun
            ? "Dry run: {$users->count()} candidate(s), {$skippedEmpty} with no matches to show, {$skippedDisposable} disposable."
            : "Sent {$sent} re-engagement email(s); skipped {$skippedEmpty} with nothing to show, {$skippedDisposable} disposable.");
        Log::info('[ReEngagement] run complete', [
            'candidates' => $users->count(), 'sent' => $sent, 'skipped_empty' => $skippedEmpty,
            'skipped_disposable' => $skippedDisposable, 'dry_run' => $dryRun,
        ]);

        return self::SUCCESS;
    }

    private function isDisposable(string $email): bool
    {
        $domain = strtolower(trim(substr(strrchr($email, '@') ?: '', 1)));

        return $domain !== '' && in_array($domain, config('services.disposable_email_domains', []), true);
    }

    private ?int $openBidCount = null;

    /** How many bids are currently open, used as proof of value in the setup nudge. */
    private function openBidCount(): int
    {
        return $this->openBidCount ??= Bid::query()
            ->where(function ($q) {
                $q->whereNull('tender_deadline')->orWhere('tender_deadline', '>', now());
            })
            ->count();
    }

    /**
     * Still-open bids matched to the user's companies since they last signed in.
     */
    private function missedBids(User $user)
    {
        return $this->missedBidsQuery($user)
            ?->orderBy('bids.tender_deadline')
            ->limit(12)
            ->get() ?? collect();
    }

    /** Shared base query so the displayed list and the total can never disagree. */
    private function missedBidsQuery(User $user)
    {
        $since = $user->last_sign_in_at ?? $user->created_at;
        $companyIds = $user->companies()->pluck('companies.id');

        if (! $since || $companyIds->isEmpty()) {
            return null;
        }

        return Bid::query()
            ->join('company_bid', 'company_bid.bid_id', '=', 'bids.id')
            ->whereIn('company_bid.company_id', $companyIds)
            ->whereNotNull('company_bid.first_matched_at')
            ->where('company_bid.first_matched_at', '>', $since)
            ->where(function ($q) {
                $q->whereNull('bids.tender_deadline')
                    ->orWhere('bids.tender_deadline', '>', now());
            })
            ->select('bids.*')
            ->distinct();
    }
}
