<?php

namespace App\Console\Commands;

use App\Mail\DigestNotificationMail;
use App\Models\Bid;
use App\Models\Company;
use App\Models\NotificationLog;
use App\Models\Setting;
use App\Services\TelegramService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendDigestCommand extends Command
{
    protected $signature = 'secp:send-digest';

    protected $description = 'Send a periodic digest email/Telegram summarizing new bids found since the last digest, per company';

    public function handle(TelegramService $telegram): int
    {
        $companies = Company::all();

        if ($companies->isEmpty()) {
            $this->info('No companies found. Skipping digest.');

            return self::SUCCESS;
        }

        $sent = 0;

        foreach ($companies as $company) {
            $cid = $company->id;

            if (Setting::get('digest_enabled', '0', $cid) !== '1') {
                continue;
            }

            // Check frequency
            $freq = Setting::get('digest_frequency', 'daily_9am', $cid);
            $hour = (int) now()->format('G');

            $shouldRun = match ($freq) {
                'hourly' => true,
                'every_2h' => $hour % 2 === 0,
                'twice_daily' => in_array($hour, [9, 15]),
                'daily_9am' => $hour === 9,
                default => $hour === 9,
            };

            if (! $shouldRun) {
                continue;
            }

            $lastDigestAt = Setting::get('last_digest_at', null, $cid);
            $since = $lastDigestAt ? new \DateTime($lastDigestAt) : new \DateTime('-24 hours');
            $sinceStr = $since->format('Y-m-d H:i:s');

            // New notifications for this company since last digest.
            // Use company_bid.notified_at to avoid re-sending older matches that were already processed.
            $bids = Bid::forCompany($cid)
                ->whereNotNull('company_bid.notified_at')
                ->where('company_bid.notified_at', '>', $sinceStr)
                ->orderBy('company_bid.notified_at', 'desc')
                ->get();

            if ($bids->isEmpty()) {
                // Move the digest cursor even when there is nothing new, so old windows don't repeat.
                Setting::set('last_digest_at', now()->toDateTimeString(), $cid);

                continue;
            }

            $this->info("Company #{$cid} ({$company->razon_social}): sending digest with {$bids->count()} bid(s)...");

            $this->sendDigestEmail($bids, $cid);
            $this->sendDigestTelegram($telegram, $bids, $cid);

            Setting::set('last_digest_at', now()->toDateTimeString(), $cid);
            $sent++;
        }

        if ($sent > 0) {
            $this->info("Digest sent to {$sent} company/ies.");
            Log::info("[SendDigest] Sent digest to {$sent} companies.");
        } else {
            $this->info('No digests to send.');
        }

        return self::SUCCESS;
    }

    private function sendDigestEmail($bids, int $companyId): void
    {
        $recipient = Setting::get('notification_email', null, $companyId);

        if (empty($recipient)) {
            return;
        }

        try {
            Mail::to($recipient)->send(new DigestNotificationMail($bids));

            NotificationLog::create([
                'company_id' => $companyId,
                'bid_id' => $bids->first()->id,
                'channel' => 'email',
                'status' => 'sent',
                'error_message' => "Digest: {$bids->count()} bids",
                'created_at' => now(),
            ]);

            $this->info("  Digest email sent to {$recipient}.");
        } catch (\Throwable $e) {
            NotificationLog::create([
                'company_id' => $companyId,
                'bid_id' => $bids->first()->id,
                'channel' => 'email',
                'status' => 'failed',
                'error_message' => "Digest failed: {$e->getMessage()}",
                'created_at' => now(),
            ]);

            Log::error("[SendDigest] Email failed for company {$companyId}: {$e->getMessage()}");
        }
    }

    private function sendDigestTelegram(TelegramService $telegram, $bids, int $companyId): void
    {
        if (! $telegram->isConfigured($companyId)) {
            return;
        }

        $lines = ["📬 <b>Resumen — {$bids->count()} convocatoria(s)</b>\n"];

        foreach ($bids->take(10) as $bid) {
            $amount = $bid->amount_estimated
                ? ($bid->currency ?? 'DOP').' '.number_format($bid->amount_estimated, 2)
                : 'N/D';

            $deadline = $bid->tender_deadline
                ? $bid->tender_deadline->format('d/m/Y')
                : 'N/D';

            $lines[] = "• <b>{$bid->title}</b>\n  🏢 {$bid->buyer_name} · 💰 {$amount} · 📅 {$deadline}";
        }

        if ($bids->count() > 10) {
            $remaining = $bids->count() - 10;
            $lines[] = "\n... y {$remaining} más.";
        }

        $text = implode("\n", $lines);

        try {
            $telegram->sendMessage($text, $companyId);
            $this->info('  Digest Telegram sent.');
        } catch (\Throwable $e) {
            Log::error("[SendDigest] Telegram failed for company {$companyId}: {$e->getMessage()}");
        }
    }
}
