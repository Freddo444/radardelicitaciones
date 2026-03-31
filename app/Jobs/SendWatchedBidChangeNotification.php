<?php

namespace App\Jobs;

use App\Mail\BidChangeNotificationMail;
use App\Models\Bid;
use App\Models\Company;
use App\Models\NotificationLog;
use App\Models\Setting;
use App\Services\TelegramService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendWatchedBidChangeNotification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public Bid $bid,
        public array $changes,
        public Company $company,
    ) {}

    public function handle(TelegramService $telegram): void
    {
        $this->sendEmail();
        $this->sendTelegram($telegram);
    }

    private function sendEmail(): void
    {
        $recipient = Setting::get('notification_email', null, $this->company->id);

        if (empty($recipient)) {
            Log::warning("[SECP] Watch change email skipped — no recipient configured for company {$this->company->id}, bid {$this->bid->process_code}");

            return;
        }

        try {
            Mail::to($recipient)->send(new BidChangeNotificationMail($this->bid, $this->changes));

            NotificationLog::create([
                'company_id' => $this->company->id,
                'bid_id' => $this->bid->id,
                'channel' => 'email',
                'status' => 'sent',
                'error_message' => 'Cambio: '.implode(', ', $this->changes),
                'created_at' => now(),
            ]);

            Log::info("[SECP] Watch change email sent for bid {$this->bid->process_code} to {$recipient} (company {$this->company->id})");
        } catch (\Throwable $e) {
            NotificationLog::create([
                'company_id' => $this->company->id,
                'bid_id' => $this->bid->id,
                'channel' => 'email',
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'created_at' => now(),
            ]);

            Log::error("[SECP] Watch change email failed for bid {$this->bid->process_code} (company {$this->company->id})", ['error' => $e->getMessage()]);
        }
    }

    private function sendTelegram(TelegramService $telegram): void
    {
        if (! $telegram->isConfigured($this->company->id)) {
            return;
        }

        $amount = $this->bid->amount_estimated
            ? ($this->bid->currency ?? 'DOP').' '.number_format($this->bid->amount_estimated, 2)
            : 'N/D';

        $deadline = $this->bid->tender_deadline
            ? $this->bid->tender_deadline->format('d/m/Y H:i')
            : 'N/D';

        $changeLines = collect($this->changes)
            ->map(fn ($c) => "• {$c}")
            ->join("\n");

        $text = "🔔 <b>Cambio en Convocatoria Vigilada</b>\n\n"
            ."📋 <b>{$this->bid->title}</b>\n"
            ."🏢 {$this->bid->buyer_name}\n"
            ."💰 {$amount}\n"
            ."📅 Cierre: {$deadline}\n\n"
            ."<b>Cambios:</b>\n{$changeLines}\n\n"
            ."🔗 <a href=\"{$this->bid->secp_url}\">Ver en SECP</a>";

        try {
            $sent = $telegram->sendMessage($text, $this->company->id);

            NotificationLog::create([
                'company_id' => $this->company->id,
                'bid_id' => $this->bid->id,
                'channel' => 'telegram',
                'status' => $sent ? 'sent' : 'failed',
                'error_message' => 'Cambio: '.implode(', ', $this->changes),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            NotificationLog::create([
                'company_id' => $this->company->id,
                'bid_id' => $this->bid->id,
                'channel' => 'telegram',
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'created_at' => now(),
            ]);

            Log::error("[SECP] Watch change Telegram exception for bid {$this->bid->process_code} (company {$this->company->id})", ['error' => $e->getMessage()]);
        }
    }
}
