<?php

namespace App\Jobs;

use App\Models\Bid;
use App\Models\InAppNotification;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendBidNotification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(public Bid $bid) {}

    /**
     * New rubro matches only get in-app bell notifications.
     * Email + Telegram are reserved for explicitly watched bids (via SendWatchedBidChangeNotification).
     */
    public function handle(): void
    {
        $this->createInAppNotification();

        $this->bid->update(['notified_at' => now()]);
    }

    private function createInAppNotification(): void
    {
        $amount = $this->bid->amount_estimated
            ? ($this->bid->currency ?? 'DOP').' '.number_format($this->bid->amount_estimated, 2)
            : null;

        $rubros = collect($this->bid->matched_rubros ?? [])
            ->pluck('name')
            ->join(', ');

        $body = $this->bid->buyer_name ?? '';
        if ($amount) {
            $body .= " — {$amount}";
        }

        // Create for all users
        foreach (User::all() as $user) {
            InAppNotification::create([
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
