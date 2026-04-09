<x-mail::message>
## Invitación a unirte a una empresa

Hola,

**{{ $inviterName }}** te invitó a colaborar en **{{ $companyName }}** dentro de **{{ config('app.name') }}**.

<x-mail::panel>
**Empresa:** {{ $companyName }}

**Invitado por:** {{ $inviterName }}

**Válida hasta:** {{ $expiresAt->copy()->locale('es')->timezone('America/Santo_Domingo')->translatedFormat('d \d\e F \d\e Y') }}
</x-mail::panel>

<x-mail::button :url="$acceptUrl">
Aceptar invitación
</x-mail::button>

Si el botón no funciona, copia y pega este enlace en tu navegador:

<span class="break-all">{{ $acceptUrl }}</span>

<p class="email-muted">Si no esperabas esta invitación, puedes ignorar este correo con tranquilidad.</p>

Saludos,<br>
{{ config('app.name') }}
</x-mail::message>
