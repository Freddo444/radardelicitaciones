<!DOCTYPE html>
<html lang="es" class="h-full bg-white">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }} — Prueba gratis</title>
    <link rel="apple-touch-icon" sizes="180x180" href="/images/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/images/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/images/favicon-16x16.png">
    <link rel="shortcut icon" href="/favicon.ico">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <x-umami-script />
</head>
<body class="h-full">

<div class="flex min-h-full items-center justify-center px-4 py-12 sm:px-6 lg:px-8">
    <div class="w-full max-w-sm space-y-10">

        <div>
            <img src="/images/LOGO.png" alt="Radar de Licitaciones" class="mx-auto -mb-4 w-72 object-contain">
            <h2 class="text-center text-lg font-bold text-gray-900">Prueba gratis por 14 días</h2>
            <p class="mt-1 text-center text-sm text-gray-500">2 análisis de pliegos con IA incluidos. Sin tarjeta de crédito.</p>
        </div>

        <form method="POST" action="{{ route('register.trial.store') }}" class="space-y-6">
            @csrf

            @if($errors->any())
            <div class="rounded-md bg-red-50 p-3 text-sm text-red-700">
                {{ $errors->first() }}
            </div>
            @endif

            <div>
                <input id="name" type="text" name="name" required
                       value="{{ old('name') }}"
                       placeholder="Nombre completo"
                       autocomplete="name"
                       class="block w-full rounded-t-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:relative focus:outline-2 focus:-outline-offset-2 focus:outline-blue-600 sm:text-sm/6"/>
                <input id="email" type="email" name="email" required
                       value="{{ old('email') }}"
                       placeholder="Correo electrónico"
                       autocomplete="email"
                       class="-mt-px block w-full bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:relative focus:outline-2 focus:-outline-offset-2 focus:outline-blue-600 sm:text-sm/6"/>
                <input id="password" type="password" name="password" required
                       placeholder="Contraseña"
                       autocomplete="new-password"
                       class="-mt-px block w-full bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:relative focus:outline-2 focus:-outline-offset-2 focus:outline-blue-600 sm:text-sm/6"/>
                <input id="password_confirmation" type="password" name="password_confirmation" required
                       placeholder="Confirmar contraseña"
                       autocomplete="new-password"
                       class="-mt-px block w-full rounded-b-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:relative focus:outline-2 focus:-outline-offset-2 focus:outline-blue-600 sm:text-sm/6"/>
            </div>

            <div class="flex gap-3 rounded-md border border-gray-200 bg-gray-50/80 p-3">
                <input type="hidden" name="newsletter" value="0">
                <input id="newsletter" type="checkbox" name="newsletter" value="1" class="mt-0.5 size-4 shrink-0 rounded border-gray-300 text-blue-600 focus:ring-blue-600" @checked((string) old('newsletter', '1') !== '0')>
                <label for="newsletter" class="text-sm leading-snug text-gray-700">
                    Quiero recibir correos con novedades, tips y ofertas de Radar de Licitaciones.
                </label>
            </div>

            <button type="submit"
                    class="flex w-full justify-center rounded-md bg-blue-600 px-3 py-1.5 text-sm/6 font-semibold text-white shadow-xs hover:bg-blue-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                Comenzar prueba gratis
            </button>
        </form>

        <p class="text-center text-sm text-gray-500">
            ¿Quieres acceso completo?
            <a href="{{ route('register.show') }}" class="font-medium text-blue-600 hover:text-blue-500">Suscríbete ahora</a>
        </p>

        <p class="text-center text-sm text-gray-500">
            ¿Ya tienes cuenta?
            <a href="{{ route('login') }}" class="font-medium text-blue-600 hover:text-blue-500">Inicia sesión</a>
        </p>

        <a href="/" class="block text-center text-sm text-gray-400 hover:text-gray-600 transition-colors">&larr; Volver al sitio</a>

    </div>
</div>

<x-umami-track />
</body>
</html>
