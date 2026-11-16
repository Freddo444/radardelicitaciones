<?php

namespace App\Services;

use App\Jobs\SendWatchedBidChangeNotification;
use App\Models\Bid;
use App\Models\BidWatch;
use App\Models\Company;
use App\Models\InAppNotification;

/**
 * Tells everyone watching a bid that it changed: an in-app notification per
 * watcher, and one email/Telegram per company.
 */
class WatchedBidNotifier
{
    /** @return list<int> ids of bids that anyone watches */
    public function watchedBidIds(): array
    {
        return BidWatch::withoutGlobalScopes()->distinct()->pluck('bid_id')->all();
    }

    /** @param list<string> $changes */
    public function notify(Bid $bid, array $changes): void
    {
        if ($changes === []) {
            return;
        }

        $changeText = implode(', ', $changes);

        $watchersByCompany = BidWatch::withoutGlobalScopes()
            ->where('bid_id', $bid->id)
            ->get()
            ->groupBy('company_id');

        foreach ($watchersByCompany as $companyId => $companyWatches) {
            foreach ($companyWatches as $watch) {
                InAppNotification::create([
                    'company_id' => $companyId,
                    'user_id' => $watch->user_id,
                    'bid_id' => $bid->id,
                    'type' => 'status_changed',
                    'title' => $bid->title,
                    'body' => $changeText,
                    'data' => [
                        'process_code' => $bid->process_code,
                        'changes' => $changes,
                    ],
                ]);
            }

            if ($company = Company::find($companyId)) {
                SendWatchedBidChangeNotification::dispatch($bid, $changes, $company);
            }
        }
    }
}
