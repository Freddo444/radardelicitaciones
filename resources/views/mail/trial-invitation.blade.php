<x-mail::message>
# Bienvenido a Radar de Licitaciones

Hola **{{ $user->name }}**, se te ha otorgado una prueba gratuita de **{{ $trialDays }} dias** en **Radar de Licitaciones**.

Aqui estan tus credenciales de acceso:

| | |
|---|---|
| **Correo** | {{ $user->email }} |
| **Contrasena** | {{ $password }} |

<x-mail::button :url="$url">
Iniciar sesion
</x-mail::button>

Al iniciar sesion, configura tu empresa para comenzar a monitorear licitaciones.

Te recomendamos cambiar tu contrasena despues de iniciar sesion.

— Radar de Licitaciones
</x-mail::message>
