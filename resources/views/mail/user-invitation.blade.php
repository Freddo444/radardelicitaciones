<x-mail::message>
## Tu cuenta está lista

Hola **{{ $user->name }}**,

Te han dado acceso a **{{ config('app.name') }}**. Aquí tienes tus credenciales:

<x-mail::panel>
**Correo:** {{ $user->email }}

**Contraseña temporal:** `{{ $password }}`
</x-mail::panel>

<x-mail::button :url="$loginUrl">
Iniciar sesión
</x-mail::button>

Después de entrar, configura tu perfil y revisa las convocatorias y el tablero de tu empresa.

Te recomendamos **cambiar tu contraseña** en Configuración cuando puedas.

<p class="email-muted">Si no esperabas este correo, ignora el mensaje o avisa al administrador de tu organización.</p>

Saludos,<br>
{{ config('app.name') }}
</x-mail::message>
