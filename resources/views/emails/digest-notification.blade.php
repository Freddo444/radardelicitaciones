<x-mail::message>
# Resumen — {{ $bids->count() }} convocatoria(s)

Se encontraron nuevas convocatorias que coinciden con sus rubros monitoreados.

---

@foreach($bids as $bid)
**{{ $bid->title }}**

| Campo | Detalle |
|---|---|
| Institución | {{ $bid->buyer_name ?? 'N/D' }} |
| Código proceso | {{ $bid->process_code }} |
| Monto estimado | {{ $bid->currency ?? 'DOP' }} {{ $bid->amount_estimated ? number_format($bid->amount_estimated, 2) : 'N/D' }} |
| Cierre de ofertas | {{ $bid->tender_deadline ? $bid->tender_deadline->format('d/m/Y H:i') : 'N/D' }} |

---

@endforeach

*Radar de Licitaciones — Notificación de resumen*
</x-mail::message>
