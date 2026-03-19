<?php

namespace App\Console\Commands;

use App\Mail\DigestNotificationMail;
use App\Models\Bid;
use App\Models\NotificationLog;
use App\Models\Setting;
use App\Services\TelegramService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendDigestCommand extends Command
{
    protected $signature = 'secp:send-digest';

    protected $description = 'Send a digest email/Telegram with all bids notified since last digest';

    public function handle(TelegramService $telegram): int
    {
        $mode = Setting::get('notification_mode', 'instant');

        if ($mode !== 'digest') {
            $this->info('Notification mode is not "digest". Skipping.');

            return self::SUCCESS;
        }

        $lastDigestAt = Setting::get('last_digest_at');
        $since = $lastDigestAt ? new \DateTime($lastDigestAt) : new \DateTime('-24 hours');

        // Get bids that were notified (have notified_at) since the last digest
        $bids = Bid::where('notified_at', '>=', $since)
            ->orderByDesc('notified_at')
            ->get();

        if ($bids->isEmpty()) {
            $this->info('No new bids since last digest.');

            return self::SUCCESS;
        }

        $this->info("Sending digest with {$bids->count()} bid(s)...");

        $this->sendDigestEmail($bids);
        $this->sendDigestTelegram($telegram, $bids);

        Setting::set('last_digest_at', now()->toDateTimeString());

        $this->info('Digest sent.');
        Log::info("[SendDigest] Sent digest with {$bids->count()} bid(s).");

        return self::SUCCESS;
    }

    private function sendDigestEmail($bids): void
    {
        $recipient = Setting::get('notification_email');

        if (empty($recipient)) {
            return;
        }

        try {
            Mail::to($recipient)->send(new DigestNotificationMail($bids));

            NotificationLog::create([
                'bid_id' => $bids->first()->id,
                'channel' => 'email',
                'status' => 'sent',
                'error_message' => "Digest: {$bids->count()} bids",
                'created_at' => now(),
            ]);

            $this->info("Digest email sent to {$recipient}.");
        } catch (\Throwable $e) {
            NotificationLog::create([
                'bid_id' => $bids->first()->id,
                'channel' => 'email',
                'status' => 'failed',
                'error_message' => "Digest failed: {$e->getMessage()}",
                'created_at' => now(),
            ]);

            Log::error("[SendDigest] Email failed: {$e->getMessage()}");
        }
    }

    private function sendDigestTelegram(TelegramService $telegram, $bids): void
    {
        if (! $telegram->isConfigured()) {
            return;
        }

        $lines = ["📬 <b>Resumen SECP — {$bids->count()} convocatoria(s)</b>\n"];

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
            $telegram->sendMessage($text);
            $this->info('Digest Telegram sent.');
        } catch (\Throwable $e) {
            Log::error("[SendDigest] Telegram failed: {$e->getMessage()}");
        }
    }
}
