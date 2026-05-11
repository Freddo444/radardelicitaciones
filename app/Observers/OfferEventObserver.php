<?php

namespace App\Observers;

use App\Jobs\SyncTableroGoogleCalendarJob;
use App\Models\Offer;
use App\Models\OfferEvent;

class OfferEventObserver
{
    public function saved(OfferEvent $offerEvent): void
    {
        if (! $offerEvent->wasRecentlyCreated && ! $offerEvent->wasChanged(['event_date', 'description', 'event_type'])) {
            return;
        }

        $companyId = Offer::query()->where('id', $offerEvent->offer_id)->value('company_id');
        if (! $companyId) {
            return;
        }

        SyncTableroGoogleCalendarJob::dispatch(
            (int) $companyId,
            'upsert_offer_event',
            ['offer_event_id' => $offerEvent->id],
        );
    }

    public function deleted(OfferEvent $offerEvent): void
    {
        $companyId = Offer::query()->where('id', $offerEvent->offer_id)->value('company_id');
        if (! $companyId) {
            return;
        }

        SyncTableroGoogleCalendarJob::dispatch(
            (int) $companyId,
            'delete_offer_event',
            ['offer_event_id' => $offerEvent->id],
        );
    }
}
