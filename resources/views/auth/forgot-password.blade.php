<!DOCTYPE html>
<html lang="es" class="h-full bg-white">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }} — Restablecer contraseña</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <x-umami-script />
</head>
<body class="h-full">

<div class="flex min-h-full items-center justify-center px-4 py-12 sm:px-6 lg:px-8">
    <div class="w-full max-w-sm space-y-10">

        <div>
            <img src="/images/LOGO.png" alt="Radar de Licitaciones" class="mx-auto -mb-4 w-72 object-contain">
            <h2 class="mt-4 text-center text-2xl/9 font-bold tracking-tight text-gray-900">Restablecer contraseña</h2>
            <p class="mt-1 text-center text-sm text-gray-500">Ingresa tu correo y te enviaremos un enlace para crear una nueva contraseña.</p>
        </div>

        @if(session('status'))
        <div class="rounded-md bg-green-50 p-3 text-sm text-green-700">
            {{ session('status') }}
        </div>
        @endif

        @if($errors->any())
        <div class="rounded-md bg-red-50 p-3 text-sm text-red-700">
            {{ $errors->first() }}
        </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="space-y-6">
            @csrf

            <div>
                <input id="email" type="email" name="email" required
                       value="{{ old('email') }}"
                       placeholder="Correo electrónico"
                       autocomplete="email"
                       class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-blue-600 sm:text-sm/6"/>
            </div>

            <button type="submit"
                    class="flex w-full justify-center rounded-md bg-blue-600 px-3 py-1.5 text-sm/6 font-semibold text-white shadow-xs hover:bg-blue-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                Enviar enlace
            </button>
        </form>

        <p class="text-center text-sm text-gray-500">
            <a href="{{ route('login') }}" class="font-semibold text-blue-600 hover:text-blue-500">
                &larr; Volver al inicio de sesión
            </a>
        </p>

    </div>
</div>

<x-umami-track />
</body>
</html>
