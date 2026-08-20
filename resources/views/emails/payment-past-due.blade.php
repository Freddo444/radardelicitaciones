<x-mail::message>
Hola{{ $name ? ' '.\Illuminate\Support\Str::of($name)->before(' ') : '' }},

Intentamos cobrar tu suscripción de {{ config('app.name') }} pero el pago no se procesó. Puede ser una tarjeta vencida, fondos insuficientes o un rechazo temporal del banco.

**Tu cuenta sigue activa por ahora.** @if($graceEndsAt) Mantendrás el acceso hasta el **{{ $graceEndsAt }}** mientras lo resolvemos. @endif Actualiza tu método de pago para no perder el monitoreo de licitaciones.

<x-mail::button :url="$url" color="primary">
Actualizar método de pago
</x-mail::button>

Si ya lo corregiste o crees que es un error, escríbenos respondiendo a este correo — te ayudamos.

Saludos,<br>
Equipo {{ config('app.name') }}

<x-mail::subcopy>
Si el botón no funciona, copia y pega este enlace en tu navegador:<br>
<span class="break-all">{{ $url }}</span>
</x-mail::subcopy>
</x-mail::message>
