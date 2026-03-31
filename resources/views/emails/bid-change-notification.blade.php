<x-mail::message>
# Cambio Detectado en Convocatoria Vigilada

Se detectaron cambios en una convocatoria que usted está vigilando.

---

**{{ $bid->title }}**

| Campo | Detalle |
|---|---|
| Institución | {{ $bid->buyer_name ?? 'N/D' }} |
| Código proceso | {{ $bid->process_code }} |
| Estado actual | {{ $bid->status ?? 'N/D' }} |
| Monto estimado | {{ $bid->currency ?? 'DOP' }} {{ $bid->amount_estimated ? number_format($bid->amount_estimated, 2) : 'N/D' }} |
| Cierre de ofertas | {{ $bid->tender_deadline ? $bid->tender_deadline->format('d/m/Y H:i') : 'N/D' }} |

**Cambios detectados:**
@foreach($changes as $change)
- {{ $change }}
@endforeach

<x-mail::button :url="$bid->secp_url ?? '#'" color="primary">
Ver en Portal SECP
</x-mail::button>

---

*Radar de Licitaciones — Notificación de cambio en convocatoria vigilada*
</x-mail::message>
