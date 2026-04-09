<x-mail::message>
## Resumen de convocatorias

Hay **{{ $bids->count() }} {{ $bids->count() === 1 ? 'convocatoria nueva' : 'convocatorias nuevas' }}** que coinciden con tus rubros.

---

@foreach($bids as $bid)
### {{ $bid->title }}

| Campo | Información |
| --- | --- |
| Institución | {{ $bid->buyer_name ?? 'N/D' }} |
| Código proceso | {{ $bid->process_code }} |
| Monto estimado | {{ $bid->currency ?? 'DOP' }} {{ $bid->amount_estimated ? number_format($bid->amount_estimated, 2) : 'N/D' }} |
| Cierre de ofertas | {{ $bid->tender_deadline ? $bid->tender_deadline->timezone('America/Santo_Domingo')->format('d/m/Y H:i') : 'N/D' }} |

---
@endforeach

<x-mail::button :url="$convocatoriasUrl">
Ver todas en Radar
</x-mail::button>

<p class="email-muted">Puedes ajustar frecuencia y canales de notificación en Configuración.</p>
</x-mail::message>
