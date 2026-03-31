<x-mail::message>
# Has sido invitado

**{{ $inviterName }}** te ha invitado a unirte a **{{ $companyName }}** en Radar de Licitaciones.

<x-mail::button :url="$acceptUrl">
Aceptar invitacion
</x-mail::button>

Esta invitacion vence el {{ $expiresAt->format('d/m/Y') }}.

Si no esperabas esta invitacion, puedes ignorar este correo.

Gracias,<br>
{{ config('app.name') }}
</x-mail::message>
