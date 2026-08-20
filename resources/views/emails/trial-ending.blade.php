<x-mail::message>
Hola{{ $name ? ' '.\Illuminate\Support\Str::of($name)->before(' ') : '' }},

Tu prueba gratis de {{ config('app.name') }} termina @if($daysLeft <= 1) **mañana** @else en **{{ $daysLeft }} días** @endif. Después de eso, para seguir recibiendo avisos de licitaciones necesitas activar un plan.

No pierdas el monitoreo automático del DGCP ni el análisis de pliegos con IA. Activar tu suscripción toma menos de un minuto:

<x-mail::button :url="$url" color="primary">
Activar mi suscripción
</x-mail::button>

Desde **US$45/mes** monitoreamos el DGCP por ti, cruzamos cada licitación con tus rubros y te ayudamos a preparar las ofertas.

¿Tienes preguntas antes de decidir? Responde a este correo — te contamos todo.

Saludos,<br>
Equipo {{ config('app.name') }}

<x-mail::subcopy>
Si el botón no funciona, copia y pega este enlace en tu navegador:<br>
<span class="break-all">{{ $url }}</span>
</x-mail::subcopy>
</x-mail::message>
