<!DOCTYPE html>
<html lang="es" class="h-full bg-white">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="h-full">
    <main class="grid min-h-full place-items-center bg-white px-6 py-24 sm:py-32 lg:px-8">
        <div class="text-center">
            <img src="/images/logo Grupo Alzare.png" alt="Grupo Alzare" class="mx-auto mb-8 h-12 w-auto">
            <p class="text-base font-semibold text-blue-600">@yield('code')</p>
            <h1 class="mt-4 text-5xl font-semibold tracking-tight text-gray-900 sm:text-7xl">@yield('heading')</h1>
            <p class="mt-6 text-lg/8 text-gray-600">@yield('message')</p>
            <div class="mt-10 flex items-center justify-center gap-x-6">
                <a href="/"
                   class="rounded-md bg-blue-600 px-3.5 py-2.5 text-sm font-semibold text-white shadow-xs hover:bg-blue-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                    Ir al inicio
                </a>
                <a href="javascript:history.back()" class="text-sm font-semibold text-gray-900">
                    Volver <span aria-hidden="true">&rarr;</span>
                </a>
            </div>
        </div>
    </main>
</body>
</html>
