<?php

namespace App\Http\Controllers;

use App\Models\Bid;
use App\Models\Offer;

class CalendarController extends Controller
{
    public function bidIcs(Bid $bid)
    {
        if (! $bid->tender_deadline) {
            return back()->with('error', 'Esta convocatoria no tiene fecha de cierre.');
        }

        $summary = "Cierre: {$bid->title}";
        $description = implode('\n', array_filter([
            "Código: {$bid->process_code}",
            "Entidad: {$bid->buyer_name}",
            $bid->amount_estimated ? 'Monto: '.($bid->currency ?? 'DOP').' '.number_format($bid->amount_estimated, 2) : null,
            $bid->secp_url ? "Portal: {$bid->secp_url}" : null,
        ]));

        $ics = $this->generateIcs(
            summary: $summary,
            description: $description,
            dtStart: $bid->tender_deadline,
            uid: "bid-{$bid->id}@radardelicitaciones",
        );

        $filename = "cierre-{$bid->process_code}.ics";

        return response($ics, 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function offerIcs(Offer $offer)
    {
        if (! $offer->fecha_limite) {
            return back()->with('error', 'Esta oferta no tiene fecha límite.');
        }

        $summary = "Oferta: {$offer->proceso_nombre}";
        $description = implode('\n', array_filter([
            "Código: {$offer->proceso_codigo}",
            "Entidad: {$offer->entidad_nombre}",
        ]));

        $ics = $this->generateIcs(
            summary: $summary,
            description: $description,
            dtStart: $offer->fecha_limite,
            uid: "offer-{$offer->id}@radardelicitaciones",
        );

        $filename = "oferta-{$offer->proceso_codigo}.ics";

        return response($ics, 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    private function generateIcs(string $summary, string $description, \DateTimeInterface $dtStart, string $uid): string
    {
        $now = gmdate('Ymd\THis\Z');
        $start = $dtStart->format('Ymd\THis\Z');

        // 1-hour event ending at the deadline
        $end = (clone $dtStart)->modify('+1 hour')->format('Ymd\THis\Z');

        // 30-minute reminder before
        $alarm = <<<ALARM
        BEGIN:VALARM
        TRIGGER:-PT30M
        ACTION:DISPLAY
        DESCRIPTION:Recordatorio: $summary
        END:VALARM
        ALARM;

        $alarm = implode("\r\n", array_map('trim', explode("\n", $alarm)));

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Radar de Licitaciones//ES',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'BEGIN:VEVENT',
            "UID:{$uid}",
            "DTSTAMP:{$now}",
            "DTSTART:{$start}",
            "DTEND:{$end}",
            "SUMMARY:{$this->escapeIcs($summary)}",
            "DESCRIPTION:{$this->escapeIcs($description)}",
            $alarm,
            'END:VEVENT',
            'END:VCALENDAR',
        ];

        return implode("\r\n", $lines);
    }

    private function escapeIcs(string $text): string
    {
        return str_replace([',', ';'], ['\\,', '\\;'], $text);
    }
}
