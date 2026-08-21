<!DOCTYPE html>
<html lang="es" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }} — Preferencias de correo</title>
    <link rel="icon" type="image/png" sizes="32x32" href="/images/favicon-32x32.png">
    @vite(['resources/css/app.css'])
</head>
<body class="h-full">
<div class="mx-auto flex min-h-full max-w-lg items-center px-6">
    <div class="w-full text-center">
        <img src="/images/LOGO.png" alt="{{ config('app.name') }}" class="mx-auto w-56 object-contain">

        <div class="mt-6 rounded-xl border border-gray-200 bg-white p-8 shadow-sm">
            <div class="mx-auto flex size-12 items-center justify-center rounded-full bg-green-100">
                <svg class="size-6 text-green-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                </svg>
            </div>

            <h1 class="mt-5 text-xl font-semibold text-gray-900">Listo, no te escribimos más</h1>
            <p class="mt-2 text-sm text-gray-600">
                Dejamos de enviar recordatorios y avisos de novedades a
                <span class="font-medium text-gray-900">{{ $email }}</span>.
            </p>
            <p class="mt-4 text-sm text-gray-500">
                Seguirás recibiendo solo lo esencial de tu cuenta: comprobantes de pago, avisos de facturación
                y las alertas de licitaciones que hayas configurado.
            </p>

            <a href="{{ route('landing') }}"
               class="mt-6 inline-flex rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500">
                Volver a {{ config('app.name') }}
            </a>
        </div>

        <p class="mt-4 text-xs text-gray-400">
            ¿Te diste de baja por error? Escríbenos a {{ config('services.support.email') }} y lo revertimos.
        </p>
    </div>
</div>
</body>
</html>
