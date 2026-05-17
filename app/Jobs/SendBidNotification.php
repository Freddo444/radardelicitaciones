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

    public int $bidId;

    public int $companyId;

    public function __construct(Bid $bid, Company $company)
    {
        $this->bidId = $bid->id;
        $this->companyId = $company->id;
    }

    public function handle(): void
    {
        $bid = Bid::find($this->bidId);
        $company = Company::find($this->companyId);

        if (! $bid || ! $company) {
            // Raced with cleanup or company deletion — notification is moot
            return;
        }

        if (! $this->markAsNotifiedIfPending($bid, $company)) {
            return;
        }

        $this->createInAppNotifications($bid, $company);
        $this->sendMatchEmailIfConfigured($bid, $company);
    }

    private function markAsNotifiedIfPending(Bid $bid, Company $company): bool
    {
        return CompanyBid::where('bid_id', $bid->id)
            ->where('company_id', $company->id)
            ->whereNull('notified_at')
            ->update(['notified_at' => now()]) > 0;
    }

    private function sendMatchEmailIfConfigured(Bid $bid, Company $company): void
    {
        if (Setting::get('digest_enabled', '0', $company->id) === '1') {
            return;
        }

        $raw = Setting::get('notification_email', null, $company->id);
        $recipient = is_string($raw) ? trim($raw) : '';

        if ($recipient === '' || ! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        try {
            Mail::to($recipient)->send(new BidNotificationMail($bid));

            NotificationLog::create([
                'company_id' => $company->id,
                'bid_id' => $bid->id,
                'channel' => 'email',
                'status' => 'sent',
                'error_message' => 'Nueva coincidencia: '.$bid->process_code,
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            NotificationLog::create([
                'company_id' => $company->id,
                'bid_id' => $bid->id,
                'channel' => 'email',
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'created_at' => now(),
            ]);

            Log::error('[SendBidNotification] Email failed', [
                'company_id' => $company->id,
                'bid_id' => $bid->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function createInAppNotifications(Bid $bid, Company $company): void
    {
        $pivot = CompanyBid::where('bid_id', $bid->id)
            ->where('company_id', $company->id)
            ->first();

        $rubros = collect($pivot?->matched_rubros ?? [])
            ->pluck('name')
            ->join(', ');

        $amount = $bid->amount_estimated
            ? ($bid->currency ?? 'DOP').' '.number_format($bid->amount_estimated, 2)
            : null;

        $body = $bid->buyer_name ?? '';
        if ($amount) {
            $body .= " — {$amount}";
        }

        foreach ($company->users as $user) {
            InAppNotification::create([
                'company_id' => $company->id,
                'user_id' => $user->id,
                'bid_id' => $bid->id,
                'type' => 'new_match',
                'title' => $bid->title,
                'body' => $body,
                'data' => [
                    'process_code' => $bid->process_code,
                    'rubros' => $rubros,
                ],
            ]);
        }
    }
}
