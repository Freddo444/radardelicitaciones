<x-mail::message>
## Tu prueba gratuita está lista

Hola **{{ $user->name }}**,

Te hemos creado una cuenta en **{{ config('app.name') }}** con una prueba de **{{ $trialDays }} {{ $trialDays === 1 ? 'día' : 'días' }}** para monitorear licitaciones públicas y preparar ofertas con tu equipo.

@if($trialEndsAt)
<x-mail::panel>
**Vence:** {{ $trialEndsAt->copy()->locale('es')->timezone('America/Santo_Domingo')->translatedFormat('d \d\e F \d\e Y') }} (hora de República Dominicana)

@if($trialParseLimit !== null)
**Análisis de pliegos con IA incluidos en la prueba:** {{ $trialParseLimit }}
@endif
</x-mail::panel>
@endif

<x-mail::panel>
**Correo:** {{ $user->email }}

**Contraseña temporal:** `{{ $password }}`
</x-mail::panel>

<x-mail::button :url="$loginUrl">
Entrar a Radar
</x-mail::button>

Después de iniciar sesión, completa la **configuración de tu empresa** para empezar a recibir coincidencias según tus rubros.

Por seguridad, te recomendamos **cambiar tu contraseña** en cuanto entres (Configuración → cuenta).

<p class="email-muted">Si no solicitaste esta cuenta, ignora este mensaje o contáctanos.</p>

Saludos,<br>
{{ config('app.name') }}
</x-mail::message>
