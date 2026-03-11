<x-mail::message>
# Te invitaron a SECP Monitor

Hola **{{ $user->name }}**, tu cuenta fue creada en **SECP Monitor**.

Aquí están tus credenciales de acceso:

| | |
|---|---|
| **Correo** | {{ $user->email }} |
| **Contraseña** | {{ $password }} |

<x-mail::button :url="$url">
Iniciar sesión
</x-mail::button>

Te recomendamos cambiar tu contraseña después de iniciar sesión.

— Grupo Alzare SRL
</x-mail::message>
