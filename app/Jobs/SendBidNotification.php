<?php

namespace App\Jobs;

use App\Mail\BidNotificationMail;
use App\Models\Bid;
use App\Models\Company;
use App\Models\CompanyBid;
use App\Models\InAppNotification;
use App\Models\NotificationLog;
use App\Models\Setting;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendBidNotification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public Bid $bid,
        public Company $company,
    ) {}

    public function handle(): void
    {
        $this->createInAppNotifications();
        $this->sendMatchEmailIfConfigured();

        // Mark as notified on the company_bid pivot
        CompanyBid::where('bid_id', $this->bid->id)
            ->where('company_id', $this->company->id)
            ->update(['notified_at' => now()]);
    }

    private function sendMatchEmailIfConfigured(): void
    {
        if (Setting::get('digest_enabled', '0', $this->company->id) === '1') {
            return;
        }

        $raw = Setting::get('notification_email', null, $this->company->id);
        $recipient = is_string($raw) ? trim($raw) : '';

        if ($recipient === '' || ! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        try {
            Mail::to($recipient)->send(new BidNotificationMail($this->bid));

            NotificationLog::create([
                'company_id' => $this->company->id,
                'bid_id' => $this->bid->id,
                'channel' => 'email',
                'status' => 'sent',
                'error_message' => 'Nueva coincidencia: '.$this->bid->process_code,
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            NotificationLog::create([
                'company_id' => $this->company->id,
                'bid_id' => $this->bid->id,
                'channel' => 'email',
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'created_at' => now(),
            ]);

            Log::error('[SendBidNotification] Email failed', [
                'company_id' => $this->company->id,
                'bid_id' => $this->bid->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function createInAppNotifications(): void
    {
        $pivot = CompanyBid::where('bid_id', $this->bid->id)
            ->where('company_id', $this->company->id)
            ->first();

        $rubros = collect($pivot?->matched_rubros ?? [])
            ->pluck('name')
            ->join(', ');

        $amount = $this->bid->amount_estimated
            ? ($this->bid->currency ?? 'DOP').' '.number_format($this->bid->amount_estimated, 2)
            : null;

        $body = $this->bid->buyer_name ?? '';
        if ($amount) {
            $body .= " — {$amount}";
        }

        foreach ($this->company->users as $user) {
            InAppNotification::create([
                'company_id' => $this->company->id,
                'user_id' => $user->id,
                'bid_id' => $this->bid->id,
                'type' => 'new_match',
                'title' => $this->bid->title,
                'body' => $body,
                'data' => [
                    'process_code' => $this->bid->process_code,
                    'rubros' => $rubros,
                ],
            ]);
        }
    }
}
