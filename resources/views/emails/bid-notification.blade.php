<x-mail::message>
## Nueva convocatoria para ti

Encontramos una convocatoria que coincide con los rubros que monitorea tu empresa.

---

### {{ $bid->title }}

| Campo | Información |
| --- | --- |
| Institución | {{ $bid->buyer_name ?? 'N/D' }} |
| Código proceso | {{ $bid->process_code }} |
| Modalidad | {{ $bid->procurement_method ?? 'N/D' }} |
| Estado | {{ $bid->status ?? 'N/D' }} |
| Monto estimado | {{ $bid->currency ?? 'DOP' }} {{ $bid->amount_estimated ? number_format($bid->amount_estimated, 2) : 'N/D' }} |
| Publicado | {{ $bid->published_at ? $bid->published_at->timezone('America/Santo_Domingo')->format('d/m/Y H:i') : 'N/D' }} |
| Cierre de ofertas | {{ $bid->tender_deadline ? $bid->tender_deadline->timezone('America/Santo_Domingo')->format('d/m/Y H:i') : 'N/D' }} |

**Rubros coincidentes**

@forelse($bid->matched_rubros ?? [] as $rubro)
- `{{ $rubro['code'] }}` — {{ $rubro['name'] }}
@empty
- *Sin detalle de rubros en esta notificación*
@endforelse

<x-mail::button :url="$detailUrl">
Abrir en Radar
</x-mail::button>

@if(!empty($bid->secp_url))
<x-mail::button :url="$bid->secp_url">
Ver en portal DGCP
</x-mail::button>
@endif

<p class="email-muted">Este mensaje se envió automáticamente según tus alertas y rubros configurados.</p>
</x-mail::message>
