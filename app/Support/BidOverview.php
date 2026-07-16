<?php

namespace App\Support;

use App\Models\Bid;
use Carbon\Carbon;

/**
 * Builds presentation data for a bid's general overview: the cronograma
 * timeline (from raw_data date fields) and institution/contact info.
 *
 * Shared by the convocatorias slide-over drawer (JSON) and the oferta
 * workspace's "Resumen" tab (server-rendered) so both views stay in sync.
 */
class BidOverview
{
    /**
     * Timeline of process milestones, sorted chronologically.
     *
     * @return array<int, array{date: string, label: string, is_past: bool, countdown: ?string, sort: int}>
     */
    public static function cronograma(Bid $bid): array
    {
        $raw = $bid->raw_data ?? [];
        $events = [];

        $dateFields = [
            'fecha_publicacion' => 'Publicación del aviso de convocatoria',
            'fecha_enmienda' => 'Plazo para enmiendas y adendas',
            'fecha_fin_recepcion_ofertas' => 'Fecha límite de recepción de ofertas',
            'fecha_apertura_ofertas' => 'Apertura de sobres',
            'fecha_habilitacion_oferente' => 'Habilitación de oferentes',
            'fecha_estimada_adjudicacion' => 'Adjudicación estimada',
            'fecha_suscripcion' => 'Suscripción del contrato',
        ];

        foreach ($dateFields as $field => $label) {
            $value = $raw[$field] ?? null;
            if (! $value) {
                continue;
            }

            try {
                $dt = Carbon::parse($value);
                $isPast = $dt->isPast();
                $isNext = ! $isPast && $dt->isFuture();

                $countdown = null;
                if ($isNext && $dt->diffInDays(now()) <= 14) {
                    $countdown = $dt->diffForHumans(['parts' => 2, 'short' => true]);
                }

                $events[] = [
                    'date' => $dt->format('d/m/Y h:i A'),
                    'label' => $label,
                    'is_past' => $isPast,
                    'countdown' => $countdown,
                    'sort' => $dt->timestamp,
                ];
            } catch (\Throwable) {
                continue;
            }
        }

        usort($events, fn ($a, $b) => $a['sort'] <=> $b['sort']);

        return $events;
    }

    /**
     * Institution / contact block for the bid.
     *
     * @return array<string, mixed>
     */
    public static function institution(Bid $bid): array
    {
        $raw = $bid->raw_data ?? [];

        return [
            'institucion' => $bid->buyer_name,
            'encargado' => $raw['nombre_encargado'] ?? $raw['encargado'] ?? null,
            'email' => $raw['correo_electronico'] ?? $raw['email_contacto'] ?? null,
            'telefono' => $raw['telefono'] ?? $raw['telefono_contacto'] ?? null,
            'modalidad' => $bid->procurement_method,
            'objeto' => $raw['tipo_objeto'] ?? $raw['objeto_compra'] ?? null,
            'duracion_contrato' => isset($raw['duracion_contrato'])
                ? str_replace(['dias', 'anos'], ['días', 'años'], preg_replace('/(\d+)\s*(?=[a-záéíóú])/iu', '$1 ', $raw['duracion_contrato']))
                : null,
            'proveedores_notificados' => $raw['proveedores_notificados'] ?? null,
        ];
    }
}
