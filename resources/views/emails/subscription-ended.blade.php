<x-mail::message>
Hola{{ $name ? ' '.\Illuminate\Support\Str::of($name)->before(' ') : '' }},

@if($reason === 'suspended')
Tu suscripción de {{ config('app.name') }} se suspendió porque no pudimos completar el cobro después de varios intentos. Sabemos que a veces es solo la tarjeta — reactivarla toma un minuto y recuperas todo tu historial y configuración intactos.
@else
Confirmamos la cancelación de tu suscripción de {{ config('app.name') }}. Gracias por haberla usado. Tus datos quedan guardados por si decides volver.
@endif

<x-mail::button :url="$url" color="primary">
@if($reason === 'suspended') Reactivar mi suscripción @else Volver a suscribirme @endif
</x-mail::button>

Mientras tanto seguimos monitoreando el DGCP — cuando reactives, retomamos justo donde lo dejaste.

¿Algo que podamos mejorar? Responde a este correo, lo leemos.

Saludos,<br>
Equipo {{ config('app.name') }}

<x-mail::subcopy>
Si el botón no funciona, copia y pega este enlace en tu navegador:<br>
<span class="break-all">{{ $url }}</span>
</x-mail::subcopy>
</x-mail::message>
