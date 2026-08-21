<x-mail::message>
Hola{{ $name ? ' '.\Illuminate\Support\Str::of($name)->before(' ') : '' }},

Creaste tu cuenta en {{ config('app.name') }}, pero nunca llegaste a configurar tu empresa — y ahí es donde empieza todo.

@if($openBids > 0)
Ahora mismo hay **{{ number_format($openBids) }} licitaciones abiertas** en el portal del DGCP. Algunas son para tu empresa; el problema es que sin tus rubros configurados no podemos decirte cuáles.
@else
Cada semana se publican nuevas licitaciones en el portal del DGCP. Sin tus rubros configurados no podemos decirte cuáles son para tu empresa.
@endif

Configurarlo toma **menos de 5 minutos**: ingresas tu número RPE y traemos automáticamente los datos y rubros de tu empresa desde la DGCP.

<x-mail::button :url="$url" color="primary">
Configurar mi empresa
</x-mail::button>

En cuanto termines, empezamos a cruzar cada convocatoria nueva con tus rubros y te avisamos apenas aparezca algo que encaje. Sin revisar el portal a mano.

¿No tienes RPE todavía, o algo te frenó? Responde a este correo — te ayudamos a terminarlo.

Saludos,<br>
Equipo {{ config('app.name') }}

<x-mail::subcopy>
Si el botón no funciona, copia y pega este enlace en tu navegador:<br>
<span class="break-all">{{ $url }}</span><br><br>
¿Prefieres no recibir estos recordatorios? <a href="{{ $unsubscribeUrl }}">Date de baja</a> — seguirás recibiendo lo esencial de tu cuenta.
</x-mail::subcopy>
</x-mail::message>
