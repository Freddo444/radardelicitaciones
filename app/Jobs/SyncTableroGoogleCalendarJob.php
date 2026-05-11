<?php

namespace App\Jobs;

use App\Models\GoogleCalendarToken;
use App\Models\Offer;
use App\Models\OfferEvent;
use App\Services\Calendar\GoogleCalendarSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncTableroGoogleCalendarJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $companyId,
        public string $action,
        /** @var array<string, mixed> */
        public array $payload = [],
    ) {}

    public function handle(GoogleCalendarSyncService $sync): void
    {
        $deleteActions = ['delete_offer', 'delete_offer_event', 'delete_offer_deadline'];

        if (in_array($this->action, $deleteActions, true)) {
            $tokens = GoogleCalendarToken::query()
                ->where('company_id', $this->companyId)
                ->get();
        } else {
            $query = GoogleCalendarToken::query()
                ->where('company_id', $this->companyId)
                ->where('sync_enabled', true);

            if ($this->action === 'full_resync' && isset($this->payload['token_id'])) {
                $query->where('id', (int) $this->payload['token_id']);
            }

            $tokens = $query->get();
        }

        if ($tokens->isEmpty()) {
            return;
        }

        foreach ($tokens as $token) {
            match ($this->action) {
                'upsert_offer_deadline' => $this->upsertOfferDeadline($sync, $token),
                'delete_offer_deadline' => $sync->deleteOfferDeadline($token, (int) $this->payload['offer_id']),
                'upsert_offer_event' => $this->upsertOfferEvent($sync, $token),
                'delete_offer_event' => $sync->deleteOfferEvent($token, (int) $this->payload['offer_event_id']),
                'delete_offer' => $sync->purgeOffer(
                    $token,
                    (int) $this->payload['offer_id'],
                    array_map('intval', $this->payload['offer_event_ids'] ?? [])
                ),
                'full_resync' => $sync->fullResync($token),
                default => null,
            };
        }
    }

    private function upsertOfferDeadline(GoogleCalendarSyncService $sync, GoogleCalendarToken $token): void
    {
        $offer = Offer::query()
            ->where('company_id', $this->companyId)
            ->where('id', (int) $this->payload['offer_id'])
            ->first();

        if (! $offer) {
            $sync->deleteOfferDeadline($token, (int) $this->payload['offer_id']);

            return;
        }

        $sync->upsertOfferDeadline($token, $offer);
    }

    private function upsertOfferEvent(GoogleCalendarSyncService $sync, GoogleCalendarToken $token): void
    {
        $event = OfferEvent::query()
            ->where('id', (int) $this->payload['offer_event_id'])
            ->whereHas('offer', fn ($q) => $q->where('company_id', $this->companyId))
            ->first();

        if (! $event) {
            $sync->deleteOfferEvent($token, (int) $this->payload['offer_event_id']);

            return;
        }

        $sync->upsertOfferEvent($token, $event);
    }
}
