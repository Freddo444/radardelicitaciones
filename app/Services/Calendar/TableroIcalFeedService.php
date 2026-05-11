<?php

namespace App\Services\Calendar;

use App\Models\Company;
use App\Models\Offer;
use App\Models\OfferEvent;
use Carbon\CarbonInterface;

class TableroIcalFeedService
{
    public function render(Company $company): string
    {
        $now = gmdate('Ymd\THis\Z');

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Radar de Licitaciones//Tablero//ES',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'X-WR-CALNAME:'.$this->escapeIcs('Radar — Tablero ('.($company->nombre_comercial ?: $company->razon_social ?: 'Empresa').')'),
        ];

        $offers = Offer::query()
            ->where('company_id', $company->id)
            ->with(['events', 'bid:id,secp_url,amount_estimated,currency'])
            ->orderBy('fecha_limite')
            ->get();

        foreach ($offers as $offer) {
            if ($offer->fecha_limite) {
                $lines = array_merge($lines, $this->veventLines(
                    uid: "offer-deadline-{$offer->id}@radardelicitaciones",
                    summary: 'Cierre oferta: '.$offer->proceso_nombre,
                    description: $this->deadlineDescription($offer),
                    dtStart: $offer->fecha_limite,
                    dtStamp: $now,
                ));
            }

            foreach ($offer->events as $event) {
                if (! $event->event_date) {
                    continue;
                }
                $lines = array_merge($lines, $this->veventLines(
                    uid: "offer-event-{$event->id}@radardelicitaciones",
                    summary: $event->typeLabel().($event->description ? ': '.$event->description : ''),
                    description: $this->timelineDescription($offer, $event),
                    dtStart: $event->event_date,
                    dtStamp: $now,
                ));
            }
        }

        $lines[] = 'END:VCALENDAR';

        return implode("\r\n", $lines);
    }

    private function deadlineDescription(Offer $offer): string
    {
        $parts = array_filter([
            'Código: '.$offer->proceso_codigo,
            'Entidad: '.$offer->entidad_nombre,
            'Estado en tablero: '.(Offer::$estados[$offer->estado] ?? $offer->estado),
            $offer->bid?->amount_estimated ? 'Monto estimado: '.($offer->bid->currency ?? 'DOP').' '.number_format((float) $offer->bid->amount_estimated, 2) : null,
            $offer->bid?->secp_url ? 'Portal: '.$offer->bid->secp_url : null,
            route('ofertas.show', $offer, absolute: true),
        ]);

        return implode("\n", $parts);
    }

    private function timelineDescription(Offer $offer, OfferEvent $event): string
    {
        $parts = array_filter([
            'Proceso: '.$offer->proceso_nombre,
            'Código: '.$offer->proceso_codigo,
            route('ofertas.show', $offer, absolute: true),
        ]);

        return implode("\n", $parts);
    }

    /**
     * @return list<string>
     */
    private function veventLines(string $uid, string $summary, string $description, CarbonInterface $dtStart, string $dtStamp): array
    {
        $start = $dtStart->clone()->utc()->format('Ymd\THis\Z');
        $end = $dtStart->clone()->utc()->addHour()->format('Ymd\THis\Z');
        $alarm = implode("\r\n", [
            'BEGIN:VALARM',
            'TRIGGER:-PT30M',
            'ACTION:DISPLAY',
            'DESCRIPTION:'.$this->escapeIcs('Recordatorio: '.$summary),
            'END:VALARM',
        ]);

        return [
            'BEGIN:VEVENT',
            'UID:'.$uid,
            'DTSTAMP:'.$dtStamp,
            'DTSTART:'.$start,
            'DTEND:'.$end,
            'SUMMARY:'.$this->escapeIcs($summary),
            'DESCRIPTION:'.$this->escapeIcs($description),
            $alarm,
            'END:VEVENT',
        ];
    }

    private function escapeIcs(string $text): string
    {
        $text = str_replace('\\', '\\\\', $text);
        $text = str_replace([',', ';'], ['\\,', '\\;'], $text);

        return str_replace(["\r\n", "\r", "\n"], ['\\n', '\\n', '\\n'], $text);
    }
}
