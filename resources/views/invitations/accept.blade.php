<!DOCTYPE html>
<html lang="es" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aceptar invitacion — Radar de Licitaciones</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://analytics.radardelicitaciones.com/script.js" data-website-id="3a71e47e-8466-4078-b759-462a63b46135"></script>
</head>
<body class="h-full flex items-center justify-center">

<div class="w-full max-w-md px-6">
    <div class="text-center mb-8">
        <h1 class="text-2xl font-bold text-gray-900">Invitacion</h1>
        <p class="mt-2 text-sm text-gray-600">
            <strong>{{ $invitation->inviter->name }}</strong> te ha invitado a unirte a
            <strong>{{ $invitation->company->razon_social }}</strong>.
        </p>
    </div>

    <div class="rounded-lg bg-white p-6 shadow-sm">
        <form method="POST" action="{{ route('invitation.accept', $invitation->token) }}">
            @csrf

            @if($existingUser)
                {{-- Existing user — just confirm --}}
                <p class="text-sm text-gray-700 mb-4">
                    Ya tienes una cuenta con <strong>{{ $existingUser->email }}</strong>.
                    Haz clic para unirte a la empresa.
                </p>
                <button type="submit" class="w-full rounded-md bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">
                    Unirme a {{ $invitation->company->razon_social }}
                </button>
            @else
                {{-- New user — must register --}}
                <p class="text-sm text-gray-500 mb-4">Crea tu cuenta para unirte.</p>

                <div class="space-y-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Nombre</label>
                        <input type="text" name="name" id="name" required value="{{ old('name') }}"
                               class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500">
                        @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" disabled value="{{ $invitation->email }}"
                               class="mt-1 block w-full rounded-md border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-500">
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">Contrasena</label>
                        <input type="password" name="password" id="password" required minlength="8"
                               class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500">
                        @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirmar contrasena</label>
                        <input type="password" name="password_confirmation" id="password_confirmation" required
                               class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>

                <button type="submit" class="mt-6 w-full rounded-md bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">
                    Crear cuenta y unirme
                </button>
            @endif
        </form>
    </div>

    <p class="mt-4 text-center text-xs text-gray-400">
        Invitacion vence {{ $invitation->expires_at->format('d/m/Y') }}
    </p>
</div>

</body>
</html>
