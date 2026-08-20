<x-mail::message>
Hola{{ $name ? ' '.\Illuminate\Support\Str::of($name)->before(' ') : '' }},

Tu prueba gratis de {{ config('app.name') }} llegó a su fin. Mientras la usabas, seguimos vigilando el DGCP — y las licitaciones siguen saliendo cada semana.

Tu empresa, tus rubros y tu configuración quedaron guardados. Activa un plan y retomas el monitoreo justo donde lo dejaste, sin volver a configurar nada.

<x-mail::button :url="$url" color="primary">
Activar mi suscripción
</x-mail::button>

Desde **US$45/mes**: monitoreo automático del DGCP, avisos por correo y Telegram, análisis de pliegos con IA y preparación de sobres. Una sola licitación ganada lo paga con creces.

¿Algo te frenó durante la prueba? Responde a este correo — queremos saberlo.

Saludos,<br>
Equipo {{ config('app.name') }}

<x-mail::subcopy>
Si el botón no funciona, copia y pega este enlace en tu navegador:<br>
<span class="break-all">{{ $url }}</span>
</x-mail::subcopy>
</x-mail::message>
