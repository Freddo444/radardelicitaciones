<!DOCTYPE html>
<html lang="es" class="h-full bg-white">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }} — Iniciar sesión</title>
    <link rel="apple-touch-icon" sizes="180x180" href="/images/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/images/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/images/favicon-16x16.png">
    <link rel="shortcut icon" href="/favicon.ico">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full">

<div class="flex min-h-full items-center justify-center px-4 py-12 sm:px-6 lg:px-8">
    <div class="w-full max-w-sm space-y-10">

        <div>
            <img src="/images/LOGO.png" alt="Radar de Licitaciones" class="mx-auto -mb-4 w-72 object-contain">
            <p class="text-center text-sm text-gray-500">Inicia sesión para continuar</p>
        </div>

        @if(session('status'))
        <div class="rounded-md bg-green-50 p-3 text-sm text-green-700">
            {{ session('status') }}
        </div>
        @endif

        <form method="POST" action="{{ route('auth.login') }}" class="space-y-6">
            @csrf

            @if($errors->any())
            <div class="rounded-md bg-red-50 p-3 text-sm text-red-700">
                {{ $errors->first() }}
            </div>
            @endif

            <div>
                <div class="col-span-2">
                    <input id="email" type="email" name="email" required
                           value="{{ old('email') }}"
                           placeholder="Correo electrónico"
                           autocomplete="email"
                           class="block w-full rounded-t-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:relative focus:outline-2 focus:-outline-offset-2 focus:outline-blue-600 sm:text-sm/6"/>
                </div>
                <div class="-mt-px">
                    <input id="password" type="password" name="password" required
                           placeholder="Contraseña"
                           autocomplete="current-password"
                           class="block w-full rounded-b-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:relative focus:outline-2 focus:-outline-offset-2 focus:outline-blue-600 sm:text-sm/6"/>
                </div>
            </div>

            <div class="flex items-center justify-between">
                <div class="flex gap-3">
                    <div class="flex h-6 shrink-0 items-center">
                        <div class="group grid size-4 grid-cols-1">
                            <input id="remember" type="checkbox" name="remember"
                                   class="col-start-1 row-start-1 appearance-none rounded-sm border border-gray-300 bg-white checked:border-blue-600 checked:bg-blue-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600"/>
                            <svg viewBox="0 0 14 14" fill="none" class="pointer-events-none col-start-1 row-start-1 size-3.5 self-center justify-self-center stroke-white">
                                <path d="M3 8L6 11L11 3.5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="opacity-0 group-has-checked:opacity-100"/>
                            </svg>
                        </div>
                    </div>
                    <label for="remember" class="block text-sm/6 text-gray-900">Recordarme</label>
                </div>
                <a href="{{ route('password.request') }}" class="text-sm font-medium text-blue-600 hover:text-blue-500">
                    ¿Olvidaste tu contraseña?
                </a>
            </div>

            <button type="submit"
                    class="flex w-full justify-center rounded-md bg-blue-600 px-3 py-1.5 text-sm/6 font-semibold text-white shadow-xs hover:bg-blue-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                Iniciar sesión
            </button>
        </form>

        <p class="text-center text-sm text-gray-500">
            ¿No tienes cuenta?
            <a href="{{ route('register.show') }}" class="font-medium text-blue-600 hover:text-blue-500">Registrate</a>
        </p>

    </div>
</div>

</body>
</html>
