<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfferEvent extends Model
{
    protected $fillable = [
        'offer_id', 'event_type', 'description',
        'event_date', 'alert_days_before', 'alerted_at', 'status',
    ];

    protected $casts = [
        'event_date' => 'datetime',
        'alerted_at' => 'datetime',
    ];

    public static array $types = [
        'visita_campo' => 'Visita de campo',
        'aclaraciones_deadline' => 'Cierre de aclaraciones',
        'entrega_oferta' => 'Entrega de oferta',
        'apertura_sobres' => 'Apertura de sobres',
        'adjudicacion_estimada' => 'Adjudicación estimada',
        'custom' => 'Evento personalizado',
    ];

    public static array $defaultAlertDays = [
        'entrega_oferta' => 3,
        'aclaraciones_deadline' => 1,
        'visita_campo' => 1,
        'apertura_sobres' => 0,
        'adjudicacion_estimada' => 0,
        'custom' => 1,
    ];

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    public function typeLabel(): string
    {
        return self::$types[$this->event_type] ?? $this->event_type;
    }

    public function daysUntil(): ?int
    {
        if (! $this->event_date) {
            return null;
        }

        // Calendar days, not 24h periods: an event tomorrow is "En 1 día",
        // never "Hoy", regardless of the clock time (matches Offer::diasRestantes).
        return (int) now()->startOfDay()->diffInDays($this->event_date->copy()->startOfDay(), false);
    }

    public function isPast(): bool
    {
        return $this->event_date && $this->event_date->isPast();
    }
}
