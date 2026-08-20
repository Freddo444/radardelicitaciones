<x-mail::message>
Hola,

Estos documentos de **{{ $companyName }}** están por vencer. Renuévalos a tiempo para no quedar fuera de una licitación por un documento vencido.

<x-mail::table>
| Documento | Vence | Faltan |
|:----------|:------|:-------|
@foreach($documents as $doc)
| {{ $doc->name }} | {{ $doc->expires_at?->format('d/m/Y') }} | {{ max(0, (int) now()->startOfDay()->diffInDays($doc->expires_at, false)) }} día(s) |
@endforeach
</x-mail::table>

<x-mail::button :url="$url" color="primary">
Ver mis documentos
</x-mail::button>

Sube la versión actualizada en la bóveda de documentos y Radar la usará automáticamente en tus próximas ofertas.

Saludos,<br>
Equipo {{ config('app.name') }}

<x-mail::subcopy>
Si el botón no funciona, copia y pega este enlace en tu navegador:<br>
<span class="break-all">{{ $url }}</span>
</x-mail::subcopy>
</x-mail::message>
