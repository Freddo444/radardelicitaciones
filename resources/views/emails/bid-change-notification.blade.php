<x-mail::message>
## Cambio en una convocatoria vigilada

Detectamos novedades en una convocatoria que tienes en seguimiento.

---

### {{ $bid->title }}

| Campo | Información |
| --- | --- |
| Institución | {{ $bid->buyer_name ?? 'N/D' }} |
| Código proceso | {{ $bid->process_code }} |
| Estado actual | {{ $bid->status ?? 'N/D' }} |
| Monto estimado | {{ $bid->currency ?? 'DOP' }} {{ $bid->amount_estimated ? number_format($bid->amount_estimated, 2) : 'N/D' }} |
| Cierre de ofertas | {{ $bid->tender_deadline ? $bid->tender_deadline->timezone('America/Santo_Domingo')->format('d/m/Y H:i') : 'N/D' }} |

**Qué cambió**

@foreach($changes as $change)
- {{ $change }}
@endforeach

<x-mail::button :url="$detailUrl">
Abrir en Radar
</x-mail::button>

@if(!empty($bid->secp_url))
<x-mail::button :url="$bid->secp_url">
Ver en portal DGCP
</x-mail::button>
@endif

<p class="email-muted">Notificación automática de Radar de Licitaciones.</p>
</x-mail::message>
