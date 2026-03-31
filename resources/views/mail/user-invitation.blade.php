<x-mail::message>
# Te invitaron a Radar de Licitaciones

Hola **{{ $user->name }}**, tu cuenta fue creada en **Radar de Licitaciones**.

Aquí están tus credenciales de acceso:

| | |
|---|---|
| **Correo** | {{ $user->email }} |
| **Contraseña** | {{ $password }} |

<x-mail::button :url="$url">
Iniciar sesión
</x-mail::button>

Te recomendamos cambiar tu contraseña después de iniciar sesión.

— Radar de Licitaciones
</x-mail::message>
