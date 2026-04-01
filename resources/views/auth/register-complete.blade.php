<!DOCTYPE html>
<html lang="es" class="h-full bg-white">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }} — Completar registro</title>
    <link rel="apple-touch-icon" sizes="180x180" href="/images/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/images/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/images/favicon-16x16.png">
    <link rel="shortcut icon" href="/favicon.ico">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://analytics.radardelicitaciones.com/script.js" data-website-id="3a71e47e-8466-4078-b759-462a63b46135"></script>
</head>
<body class="h-full">

<div class="flex min-h-full items-center justify-center px-4 py-12 sm:px-6 lg:px-8">
    <div class="w-full max-w-sm space-y-8">

        <div>
            <img src="/images/LOGO.png" alt="Radar de Licitaciones" class="mx-auto -mb-4 w-72 object-contain">
            <p class="text-center text-sm text-gray-500">Paso 2 de 2 — Crea tu cuenta</p>
        </div>

        <div class="rounded-md bg-green-50 p-3 text-sm text-green-700">
            ¡Pago confirmado! Completa tus datos para activar tu cuenta.
        </div>

        @php $plan = session('register_plan'); @endphp
        <div class="rounded-md bg-blue-50 px-4 py-3 text-sm text-blue-800">
            <strong>Tu plan:</strong> {{ $plan['max_companies'] }} empresa(s), {{ $plan['max_users'] }} usuarios — US${{ number_format($plan['amount'], 2) }}/mes
        </div>

        @if($errors->any())
        <div class="rounded-md bg-red-50 p-3 text-sm text-red-700">
            <ul class="list-disc pl-4 space-y-1">
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form method="POST" action="{{ route('register.store') }}" class="space-y-5">
            @csrf

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700">Nombre completo</label>
                <input id="name" type="text" name="name" required value="{{ old('name') }}" autocomplete="name"
                       class="mt-1 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-blue-600 sm:text-sm/6"/>
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Correo electrónico</label>
                <input id="email" type="email" name="email" required value="{{ old('email') }}" autocomplete="email"
                       class="mt-1 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-blue-600 sm:text-sm/6"/>
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">Contraseña</label>
                <input id="password" type="password" name="password" required autocomplete="new-password" minlength="8"
                       class="mt-1 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-blue-600 sm:text-sm/6"/>
                <p class="mt-1 text-xs text-gray-500">Mínimo 8 caracteres</p>
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirmar contraseña</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
                       class="mt-1 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-blue-600 sm:text-sm/6"/>
            </div>

            <button type="submit"
                    class="flex w-full justify-center rounded-md bg-blue-600 px-3 py-2.5 text-sm/6 font-semibold text-white shadow-xs hover:bg-blue-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                Crear cuenta
            </button>
        </form>

    </div>
</div>

</body>
</html>
