<?php

namespace App\Http\Controllers;

use App\Models\Bid;
use App\Models\Company;
use App\Models\Offer;
use App\Models\OfferEvent;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TableroController extends Controller
{
    /**
     * Kanban board view.
     */
    public function index()
    {
        return view('tablero.index');
    }

    /**
     * JSON cards grouped by column/estado.
     */
    public function cards(Request $request): JsonResponse
    {
        $query = Offer::with('bid:id,process_code,secp_url,mipymes,mipymes_mujeres,tender_deadline');

        if ($search = $request->input('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('proceso_nombre', 'like', "%{$search}%")
                    ->orWhere('entidad_nombre', 'like', "%{$search}%")
                    ->orWhere('proceso_codigo', 'like', "%{$search}%");
            });
        }

        if ($entity = $request->input('entity')) {
            $query->where('entidad_nombre', 'like', "%{$entity}%");
        }

        if ($request->filled('deadline_from')) {
            $query->where('fecha_limite', '>=', $request->input('deadline_from'));
        }

        if ($request->filled('deadline_to')) {
            $query->where('fecha_limite', '<=', $request->input('deadline_to'));
        }

        if ($request->filled('amount_min')) {
            $query->whereHas('bid', fn ($q) => $q->where('amount_estimated', '>=', $request->input('amount_min')));
        }

        if ($request->filled('amount_max')) {
            $query->whereHas('bid', fn ($q) => $q->where('amount_estimated', '<=', $request->input('amount_max')));
        }

        $offers = $query->orderBy('fecha_limite')->get();

        $columns = [
            'borrador' => ['label' => 'Oportunidades',  'icon' => 'building',    'color' => 'gray'],
            'en_preparacion' => ['label' => 'En proceso',     'icon' => 'clipboard',   'color' => 'blue'],
            'listo' => ['label' => 'Lista',          'icon' => 'check-circle', 'color' => 'green'],
            'enviado' => ['label' => 'Entregada',      'icon' => 'paper-plane', 'color' => 'purple'],
            'adjudicada' => ['label' => 'Adjudicada',     'icon' => 'trophy',      'color' => 'yellow'],
            'perdida' => ['label' => 'Perdida',        'icon' => 'x-circle',    'color' => 'red'],
            'impugnacion' => ['label' => 'Impugnación',    'icon' => 'scale',       'color' => 'orange'],
        ];

        $grouped = [];
        foreach ($columns as $estado => $meta) {
            $grouped[$estado] = [
                'label' => $meta['label'],
                'icon' => $meta['icon'],
                'color' => $meta['color'],
                'cards' => [],
            ];
        }

        foreach ($offers as $offer) {
            $estado = $offer->estado;
            if (! isset($grouped[$estado])) {
                continue;
            }

            $days = $offer->diasRestantes();
            $deadlineLabel = null;
            $deadlineColor = 'gray';
            if ($days !== null) {
                if ($days < 0) {
                    $deadlineLabel = 'Vencida';
                    $deadlineColor = 'red';
                } elseif ($days === 0) {
                    $deadlineLabel = 'Hoy';
                    $deadlineColor = 'red';
                } elseif ($days <= 3) {
                    $deadlineLabel = "{$days}d";
                    $deadlineColor = 'amber';
                } elseif ($days <= 7) {
                    $deadlineLabel = "{$days}d";
                    $deadlineColor = 'yellow';
                } else {
                    $deadlineLabel = "{$days}d";
                    $deadlineColor = 'green';
                }
            }

            $grouped[$estado]['cards'][] = [
                'id' => $offer->id,
                'title' => $offer->proceso_nombre,
                'entity' => $offer->entidad_nombre,
                'process_code' => $offer->proceso_codigo,
                'amount' => $offer->bid?->amount_estimated,
                'currency' => $offer->bid?->currency ?? 'DOP',
                'estado' => $estado,
                'estado_label' => Offer::$estados[$estado] ?? $estado,
                'deadline' => $offer->fecha_limite?->format('d/m/Y'),
                'deadline_label' => $deadlineLabel,
                'deadline_color' => $deadlineColor,
                'mipymes' => $offer->bid?->mipymes,
                'secp_url' => $offer->bid?->secp_url,
                'bid_id' => $offer->bid_id,
            ];
        }

        return response()->json(['columns' => $grouped]);
    }

    /**
     * Move a card to a new estado.
     */
    public function move(Request $request, Offer $offer): JsonResponse
    {
        $request->validate([
            'estado' => 'required|in:'.implode(',', array_keys(Offer::$estados)),
        ]);

        $newEstado = $request->input('estado');

        // Validate transitions
        $allowed = $this->allowedTransitions($offer->estado);
        if (! in_array($newEstado, $allowed)) {
            return response()->json([
                'error' => "No se puede mover de '{$offer->estado}' a '{$newEstado}'",
            ], 422);
        }

        $updates = ['estado' => $newEstado];
        if ($newEstado === 'enviado' && ! $offer->enviado_at) {
            $updates['enviado_at'] = now();
        }

        $offer->update($updates);

        return response()->json(['ok' => true, 'estado' => $newEstado]);
    }

    /**
     * Add a bid to the tablero (create borrador offer).
     */
    public function addBid(Request $request): JsonResponse
    {
        $request->validate(['bid_id' => 'required|exists:bids,id']);

        $bid = Bid::findOrFail($request->input('bid_id'));

        // Check if offer already exists for this bid
        $existing = Offer::where('bid_id', $bid->id)->first();
        if ($existing) {
            return response()->json([
                'exists' => true,
                'offer_id' => $existing->id,
                'estado' => $existing->estado,
            ]);
        }

        $company = Company::instance();

        $offer = Offer::create([
            'company_id' => $company->id,
            'bid_id' => $bid->id,
            'proceso_codigo' => $bid->process_code,
            'proceso_nombre' => $bid->title,
            'entidad_nombre' => $bid->buyer_name,
            'fecha_limite' => $bid->tender_deadline,
            'estado' => 'borrador',
        ]);

        return response()->json([
            'exists' => false,
            'offer_id' => $offer->id,
            'estado' => 'borrador',
        ], 201);
    }

    /**
     * Calendar events for a given month.
     */
    public function calendar(Request $request): JsonResponse
    {
        $month = $request->input('month', now()->format('Y-m'));
        $start = Carbon::parse($month.'-01')->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $events = [];

        // Offer deadlines
        $offers = Offer::with('bid:id,process_code')
            ->whereNotNull('fecha_limite')
            ->whereBetween('fecha_limite', [$start, $end])
            ->get();

        foreach ($offers as $offer) {
            $events[] = [
                'date' => $offer->fecha_limite->format('Y-m-d'),
                'type' => 'deadline',
                'title' => $offer->proceso_nombre,
                'entity' => $offer->entidad_nombre,
                'estado' => $offer->estado,
                'color' => $this->estadoColor($offer->estado),
                'offer_id' => $offer->id,
                'time' => $offer->fecha_limite->format('h:i A'),
            ];
        }

        // Offer events
        $offerEvents = OfferEvent::whereHas('offer')
            ->with('offer:id,proceso_nombre,estado')
            ->whereBetween('event_date', [$start, $end])
            ->get();

        foreach ($offerEvents as $event) {
            $events[] = [
                'date' => Carbon::parse($event->event_date)->format('Y-m-d'),
                'type' => 'event',
                'title' => $event->description,
                'entity' => $event->offer?->proceso_nombre,
                'estado' => $event->offer?->estado ?? 'borrador',
                'color' => $this->estadoColor($event->offer?->estado ?? 'borrador'),
                'offer_id' => $event->offer_id,
                'time' => Carbon::parse($event->event_date)->format('h:i A'),
            ];
        }

        return response()->json([
            'month' => $month,
            'events' => $events,
        ]);
    }

    private function estadoColor(string $estado): string
    {
        return match ($estado) {
            'borrador' => 'gray',
            'en_preparacion' => 'blue',
            'listo' => 'green',
            'enviado' => 'purple',
            'adjudicada' => 'yellow',
            'perdida' => 'red',
            'impugnacion' => 'orange',
            default => 'gray',
        };
    }

    /**
     * Allowed estado transitions.
     */
    private function allowedTransitions(string $currentEstado): array
    {
        return match ($currentEstado) {
            'borrador' => ['en_preparacion'],
            'en_preparacion' => ['borrador', 'listo'],
            'listo' => ['en_preparacion', 'enviado'],
            'enviado' => ['adjudicada', 'perdida', 'impugnacion'],
            'adjudicada' => [],
            'perdida' => ['impugnacion'],
            'impugnacion' => ['en_preparacion', 'adjudicada', 'perdida'],
            default => [],
        };
    }
}
