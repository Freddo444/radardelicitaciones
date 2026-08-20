<x-mail::message>
Hola{{ $name ? ' '.\Illuminate\Support\Str::of($name)->before(' ') : '' }},

Notamos que no pasas por {{ config('app.name') }} desde hace un tiempo — y mientras tanto no dejamos de trabajar. Seguimos cruzando cada nueva licitación del DGCP con los rubros de tu empresa.

Es posible que tengas convocatorias esperándote en tu tablero. Vale la pena una mirada rápida antes de que cierren:

<x-mail::button :url="$url" color="primary">
Ver mis licitaciones
</x-mail::button>

Recuerda que también puedes activar avisos por **correo o Telegram** para enterarte al instante y no tener que entrar a revisar.

¿Hay algo que podamos ajustar para que te sea más útil? Responde a este correo.

Saludos,<br>
Equipo {{ config('app.name') }}

<x-mail::subcopy>
Si el botón no funciona, copia y pega este enlace en tu navegador:<br>
<span class="break-all">{{ $url }}</span>
</x-mail::subcopy>
</x-mail::message>
