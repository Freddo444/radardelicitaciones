<?php

namespace App\Services\Calendar;

use App\Models\GoogleCalendarEventMap;
use App\Models\GoogleCalendarToken;
use App\Models\Offer;
use App\Models\OfferEvent;
use Carbon\CarbonInterface;
use Google\Client as GoogleClient;
use Google\Service\Calendar;
use Google\Service\Calendar\Event;
use Google\Service\Calendar\EventDateTime;
use Illuminate\Support\Facades\Log;

class GoogleCalendarSyncService
{
    private const TZ = 'America/Santo_Domingo';

    public function refreshClient(GoogleCalendarToken $token): ?GoogleClient
    {
        $clientId = config('services.google_calendar.client_id');
        $clientSecret = config('services.google_calendar.client_secret');
        if (! $clientId || ! $clientSecret) {
            return null;
        }

        $client = new GoogleClient;
        $client->setClientId($clientId);
        $client->setClientSecret($clientSecret);

        $access = [
            'access_token' => $token->access_token,
            'refresh_token' => $token->refresh_token,
        ];
        if ($token->expires_at) {
            $access['expires_in'] = max(1, $token->expires_at->timestamp - time());
        }
        $client->setAccessToken($access);

        if ($token->refresh_token && $client->isAccessTokenExpired()) {
            try {
                $client->fetchAccessTokenWithRefreshToken($token->refresh_token);
                $newToken = $client->getAccessToken();
                if (is_array($newToken) && isset($newToken['access_token'])) {
                    $token->forceFill([
                        'access_token' => $newToken['access_token'],
                        'expires_at' => isset($newToken['expires_in'])
                            ? now()->addSeconds((int) $newToken['expires_in'])
                            : now()->addHour(),
                    ])->saveQuietly();
                }
            } catch (\Throwable $e) {
                Log::warning('google_calendar.refresh_failed', [
                    'token_id' => $token->id,
                    'message' => $e->getMessage(),
                ]);

                return null;
            }
        }

        return $client;
    }

    public function upsertOfferDeadline(GoogleCalendarToken $token, Offer $offer): void
    {
        if (! $offer->fecha_limite) {
            $this->deleteMapped($token, GoogleCalendarEventMap::TYPE_OFFER_DEADLINE, $offer->id);

            return;
        }

        $client = $this->refreshClient($token);
        if (! $client) {
            return;
        }

        try {
            $calendar = new Calendar($client);
            $event = $this->buildDeadlineEvent($offer);
            $calendarId = $token->calendar_id ?: 'primary';

            $map = GoogleCalendarEventMap::query()->firstOrNew([
                'google_calendar_token_id' => $token->id,
                'syncable_type' => GoogleCalendarEventMap::TYPE_OFFER_DEADLINE,
                'syncable_id' => $offer->id,
            ]);

            if ($map->google_event_id) {
                $calendar->events->update($calendarId, $map->google_event_id, $event);
            } else {
                $created = $calendar->events->insert($calendarId, $event);
                $map->google_event_id = $created->getId();
            }

            $map->last_synced_at = now();
            $map->save();
            $this->touchTokenSuccess($token);
        } catch (\Throwable $e) {
            $this->touchTokenError($token, $e->getMessage());
            Log::warning('google_calendar.upsert_deadline_failed', [
                'token_id' => $token->id,
                'offer_id' => $offer->id,
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function deleteOfferDeadline(GoogleCalendarToken $token, int $offerId): void
    {
        $this->deleteMapped($token, GoogleCalendarEventMap::TYPE_OFFER_DEADLINE, $offerId);
    }

    public function upsertOfferEvent(GoogleCalendarToken $token, OfferEvent $offerEvent): void
    {
        if (! $offerEvent->event_date) {
            $this->deleteMapped($token, GoogleCalendarEventMap::TYPE_OFFER_EVENT, $offerEvent->id);

            return;
        }

        $offer = $offerEvent->offer;
        if (! $offer) {
            return;
        }

        $client = $this->refreshClient($token);
        if (! $client) {
            return;
        }

        try {
            $calendar = new Calendar($client);
            $event = $this->buildTimelineEvent($offer, $offerEvent);
            $calendarId = $token->calendar_id ?: 'primary';

            $map = GoogleCalendarEventMap::query()->firstOrNew([
                'google_calendar_token_id' => $token->id,
                'syncable_type' => GoogleCalendarEventMap::TYPE_OFFER_EVENT,
                'syncable_id' => $offerEvent->id,
            ]);

            if ($map->google_event_id) {
                $calendar->events->update($calendarId, $map->google_event_id, $event);
            } else {
                $created = $calendar->events->insert($calendarId, $event);
                $map->google_event_id = $created->getId();
            }

            $map->last_synced_at = now();
            $map->save();
            $this->touchTokenSuccess($token);
        } catch (\Throwable $e) {
            $this->touchTokenError($token, $e->getMessage());
            Log::warning('google_calendar.upsert_timeline_failed', [
                'token_id' => $token->id,
                'offer_event_id' => $offerEvent->id,
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function deleteOfferEvent(GoogleCalendarToken $token, int $offerEventId): void
    {
        $this->deleteMapped($token, GoogleCalendarEventMap::TYPE_OFFER_EVENT, $offerEventId);
    }

    public function purgeOffer(GoogleCalendarToken $token, int $offerId, array $offerEventIds = []): void
    {
        if ($offerEventIds === []) {
            $offerEventIds = OfferEvent::query()->where('offer_id', $offerId)->pluck('id')->all();
        }
        foreach ($offerEventIds as $eid) {
            $this->deleteOfferEvent($token, (int) $eid);
        }
        $this->deleteOfferDeadline($token, $offerId);
    }

    public function fullResync(GoogleCalendarToken $token): void
    {
        GoogleCalendarEventMap::query()->where('google_calendar_token_id', $token->id)->delete();

        $offers = Offer::query()
            ->where('company_id', $token->company_id)
            ->with('events')
            ->get();

        foreach ($offers as $offer) {
            $this->upsertOfferDeadline($token, $offer);
            foreach ($offer->events as $ev) {
                $this->upsertOfferEvent($token, $ev);
            }
        }
    }

    public function disconnect(GoogleCalendarToken $token): void
    {
        $client = $this->refreshClient($token);
        $calendarId = $token->calendar_id ?: 'primary';

        if ($client) {
            $calendar = new Calendar($client);
            foreach ($token->eventMap()->cursor() as $map) {
                try {
                    $calendar->events->delete($calendarId, $map->google_event_id);
                } catch (\Throwable) {
                    //
                }
            }
        }

        $token->eventMap()->delete();
        $token->newQuery()->whereKey($token->getKey())->delete();
    }

    private function deleteMapped(GoogleCalendarToken $token, string $type, int $syncableId): void
    {
        $map = GoogleCalendarEventMap::query()
            ->where('google_calendar_token_id', $token->id)
            ->where('syncable_type', $type)
            ->where('syncable_id', $syncableId)
            ->first();

        if (! $map) {
            return;
        }

        $client = $this->refreshClient($token);
        if ($client) {
            try {
                $calendar = new Calendar($client);
                $calendar->events->delete($token->calendar_id ?: 'primary', $map->google_event_id);
            } catch (\Throwable) {
                //
            }
        }

        $map->newQuery()->whereKey($map->getKey())->delete();
        $this->touchTokenSuccess($token);
    }

    private function buildDeadlineEvent(Offer $offer): Event
    {
        $start = $offer->fecha_limite->clone()->timezone(self::TZ);
        $end = $start->clone()->addHour();

        $description = implode("\n", array_filter([
            'Código: '.$offer->proceso_codigo,
            'Entidad: '.$offer->entidad_nombre,
            'Estado: '.(Offer::$estados[$offer->estado] ?? $offer->estado),
            route('ofertas.show', $offer, absolute: true),
        ]));

        $event = new Event;
        $event->setSummary('Cierre oferta: '.$offer->proceso_nombre);
        $event->setDescription($description);
        $event->setStart($this->dateTime($start));
        $event->setEnd($this->dateTime($end));

        return $event;
    }

    private function buildTimelineEvent(Offer $offer, OfferEvent $offerEvent): Event
    {
        $start = $offerEvent->event_date->clone()->timezone(self::TZ);
        $end = $start->clone()->addHour();

        $description = implode("\n", array_filter([
            'Proceso: '.$offer->proceso_nombre,
            'Código: '.$offer->proceso_codigo,
            route('ofertas.show', $offer, absolute: true),
        ]));

        $summary = $offerEvent->typeLabel();
        if ($offerEvent->description) {
            $summary .= ': '.$offerEvent->description;
        }

        $event = new Event;
        $event->setSummary($summary);
        $event->setDescription($description);
        $event->setStart($this->dateTime($start));
        $event->setEnd($this->dateTime($end));

        return $event;
    }

    private function dateTime(CarbonInterface $moment): EventDateTime
    {
        $dt = new EventDateTime;
        $dt->setDateTime($moment->format(\DateTimeInterface::RFC3339));
        $dt->setTimeZone(self::TZ);

        return $dt;
    }

    private function touchTokenSuccess(GoogleCalendarToken $token): void
    {
        $token->forceFill([
            'last_synced_at' => now(),
            'last_sync_error' => null,
        ])->saveQuietly();
    }

    private function touchTokenError(GoogleCalendarToken $token, string $message): void
    {
        $token->forceFill([
            'last_sync_error' => mb_substr($message, 0, 500),
        ])->saveQuietly();
    }
}
