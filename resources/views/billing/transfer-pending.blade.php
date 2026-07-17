<!DOCTYPE html>
<html lang="es" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }} — Pago pendiente</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <x-umami-script />
</head>
<body class="h-full">
<div class="flex min-h-full flex-col items-center justify-center px-4 py-12">
    <div class="w-full max-w-lg rounded-2xl bg-white p-8 text-center shadow-sm ring-1 ring-gray-900/5 sm:p-10">

        <div class="mx-auto flex size-14 items-center justify-center rounded-full bg-amber-100">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="size-7 text-amber-600" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
            </svg>
        </div>

        <h1 class="mt-6 text-xl font-bold text-gray-900">Pago pendiente de confirmación</h1>
        <p class="mt-3 text-sm leading-relaxed text-gray-600">
            Recibimos tu comprobante de transferencia y lo estamos verificando. Este proceso es manual y normalmente toma unas horas en horario laboral.
        </p>

        <div class="mt-6 rounded-lg bg-blue-50 p-4 text-left text-sm text-blue-800">
            <p class="font-semibold">¿Qué sigue?</p>
            <p class="mt-1">Te enviaremos un correo a <strong>{{ auth()->user()->email }}</strong> en cuanto confirmemos el pago. En ese momento tu cuenta quedará activa y podrás usar todas las funciones.</p>
        </div>

        <p class="mt-6 text-xs text-gray-500">
            ¿Ya pasaron varias horas o tienes una duda? Escríbenos a
            <a href="mailto:{{ config('services.support.email') }}" class="font-medium text-blue-600 hover:text-blue-500">{{ config('services.support.email') }}</a>.
        </p>

        <form method="POST" action="{{ route('logout') }}" class="mt-8">
            @csrf
            <button type="submit" class="text-sm font-medium text-gray-500 hover:text-gray-700">Cerrar sesión</button>
        </form>
    </div>
</div>
<x-umami-track />
</body>
</html>
