<x-mail::message>
Hola{{ $name ? ' '.\Illuminate\Support\Str::of($name)->before(' ') : '' }}, ¡bienvenido a {{ config('app.name') }}!

Ya tienes todo listo para dejar de revisar el portal del DGCP a mano. Nosotros lo vigilamos por ti y te avisamos cuando salga una licitación que encaja con tu empresa.

Para empezar a recibir avisos, solo falta un paso:

<x-mail::button :url="$url" color="primary">
Configurar mi empresa
</x-mail::button>

Con tu número **RPE** autocompletamos casi todo desde la DGCP en menos de 2 minutos. Una vez configurada, esto es lo que Radar hace por ti:

- **Monitorea** el DGCP y cruza cada licitación con tus rubros UNSPSC
- **Te avisa** por correo o Telegram cuando aparece algo relevante
- **Analiza los pliegos con IA** y te arma los sobres de la oferta

¿Dudas para empezar? Responde a este correo — te acompañamos.

Saludos,<br>
Equipo {{ config('app.name') }}

<x-mail::subcopy>
Si el botón no funciona, copia y pega este enlace en tu navegador:<br>
<span class="break-all">{{ $url }}</span>
</x-mail::subcopy>
</x-mail::message>
