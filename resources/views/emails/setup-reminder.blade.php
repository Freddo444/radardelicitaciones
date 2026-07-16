<x-mail::message>
Hola{{ $name ? ' '.\Illuminate\Support\Str::of($name)->before(' ') : '' }},

Creaste tu cuenta en {{ config('app.name') }} pero aún no configuraste tu empresa — y ese es justo el paso que activa todo lo demás.

En cuanto lo completes (toma menos de 2 minutos), empezamos a cruzar las licitaciones del DGCP con los rubros de tu empresa y te avisamos cuando aparezca algo que encaja.

<x-mail::button :url="$url" color="primary">
Terminar de configurar mi empresa
</x-mail::button>

Si tienes tu número **RPE** a mano, autocompletamos casi todo desde la DGCP. Si no, puedes llenarlo manualmente y sincronizar después.

¿Alguna duda o algo no funcionó? Responde a este correo — lo leemos.

Saludos,<br>
Equipo {{ config('app.name') }}

<x-mail::subcopy>
Si el botón no funciona, copia y pega este enlace en tu navegador:<br>
<span class="break-all">{{ $url }}</span>
</x-mail::subcopy>
</x-mail::message>
