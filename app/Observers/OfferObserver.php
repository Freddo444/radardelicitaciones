<?php

namespace App\Observers;

use App\Jobs\SyncTableroGoogleCalendarJob;
use App\Models\Offer;
use App\Models\OfferEvent;

class OfferObserver
{
    public function saved(Offer $offer): void
    {
        if (! $offer->wasRecentlyCreated && ! $offer->wasChanged(['fecha_limite', 'proceso_nombre', 'proceso_codigo', 'entidad_nombre', 'estado'])) {
            return;
        }

        SyncTableroGoogleCalendarJob::dispatch(
            $offer->company_id,
            'upsert_offer_deadline',
            ['offer_id' => $offer->id],
        );
    }

    public function deleting(Offer $offer): void
    {
        $eventIds = OfferEvent::query()->where('offer_id', $offer->id)->pluck('id')->all();

        SyncTableroGoogleCalendarJob::dispatch(
            $offer->company_id,
            'delete_offer',
            ['offer_id' => $offer->id, 'offer_event_ids' => $eventIds],
        );
    }
}
