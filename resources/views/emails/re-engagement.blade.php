<x-mail::message>
Hola{{ $name ? ' '.\Illuminate\Support\Str::of($name)->before(' ') : '' }},

No te vemos por {{ config('app.name') }} desde hace {{ $daysAway }} días — pero el radar no paró. Mientras no estabas, cruzamos cada convocatoria nueva del DGCP con los rubros de tu empresa y **encontramos {{ $total }} {{ $total === 1 ? 'licitación que coincide' : 'licitaciones que coinciden' }}**, todas todavía abiertas.

@foreach($highlights as $bid)
---
### {{ $bid->title }}

| | |
| --- | --- |
| Institución | {{ $bid->buyer_name ?? 'N/D' }} |
| Monto estimado | {{ $bid->currency ?? 'DOP' }} {{ $bid->amount_estimated ? number_format($bid->amount_estimated, 2) : 'N/D' }} |
| Cierre de ofertas | {{ $bid->tender_deadline ? $bid->tender_deadline->timezone('America/Santo_Domingo')->format('d/m/Y H:i') : 'N/D' }} |
@endforeach

---

@if($total > $highlights->count())
Y {{ $total - $highlights->count() }} más esperándote en tu tablero.
@endif

@if($soonestDays)
**{{ $soonestDays <= 1 ? 'Una de ellas cierra mañana' : 'Una de ellas cierra en '.$soonestDays.' días' }}.** Si no aplicas antes del cierre, esa oportunidad se pierde — no hay prórroga.
@endif

<x-mail::button :url="$url" color="primary">
Ver mis licitaciones
</x-mail::button>

Ojo con las fechas de cierre — algunas vencen pronto. Si prefieres no tener que entrar a revisar, activa los avisos por **correo o Telegram** y te escribimos apenas aparezca algo que encaje.

¿Hay algo que podamos ajustar para que te sea más útil? Responde a este correo.

Saludos,<br>
Equipo {{ config('app.name') }}

<x-mail::subcopy>
Si el botón no funciona, copia y pega este enlace en tu navegador:<br>
<span class="break-all">{{ $url }}</span><br><br>
¿Prefieres no recibir estos recordatorios? <a href="{{ $unsubscribeUrl }}">Date de baja</a> — seguirás recibiendo lo esencial de tu cuenta.
</x-mail::subcopy>
</x-mail::message>
