<?php

namespace App\Jobs;

use App\Models\Bid;
use App\Models\Company;
use App\Models\CompanyBid;
use App\Models\InAppNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

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

        // Mark as notified on the company_bid pivot
        CompanyBid::where('bid_id', $this->bid->id)
            ->where('company_id', $this->company->id)
            ->update(['notified_at' => now()]);
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
