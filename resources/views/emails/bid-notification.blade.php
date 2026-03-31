<x-mail::message>
# Nueva Convocatoria

Se encontró una nueva convocatoria que coincide con sus rubros monitoreados.

---

**{{ $bid->title }}**

| Campo | Detalle |
|---|---|
| Institución | {{ $bid->buyer_name ?? 'N/D' }} |
| Código proceso | {{ $bid->process_code }} |
| Modalidad | {{ $bid->procurement_method ?? 'N/D' }} |
| Estado | {{ $bid->status ?? 'N/D' }} |
| Monto estimado | {{ $bid->currency ?? 'DOP' }} {{ $bid->amount_estimated ? number_format($bid->amount_estimated, 2) : 'N/D' }} |
| Publicado | {{ $bid->published_at ? $bid->published_at->format('d/m/Y H:i') : 'N/D' }} |
| Cierre de ofertas | {{ $bid->tender_deadline ? $bid->tender_deadline->format('d/m/Y H:i') : 'N/D' }} |

**Rubros coincidentes:**
@foreach($bid->matched_rubros ?? [] as $rubro)
- `{{ $rubro['code'] }}` — {{ $rubro['name'] }}
@endforeach

<x-mail::button :url="$bid->secp_url ?? '#'" color="green">
Ver en DGCP
</x-mail::button>

---

*Radar de Licitaciones — Notificación automática*
</x-mail::message>
