<!DOCTYPE html>
<html lang="es" class="h-full bg-white">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }} — Registro</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full">

<div class="flex min-h-full items-center justify-center px-4 py-12 sm:px-6 lg:px-8">
    <div class="w-full max-w-sm space-y-8">

        <div>
            <h2 class="text-center text-2xl/9 font-bold tracking-tight text-gray-900">Radar de Licitaciones</h2>
            <p class="mt-1 text-center text-sm text-gray-500">Crea tu cuenta para comenzar</p>
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
                <label for="email" class="block text-sm font-medium text-gray-700">Correo electronico</label>
                <input id="email" type="email" name="email" required value="{{ old('email') }}" autocomplete="email"
                       class="mt-1 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-blue-600 sm:text-sm/6"/>
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">Contrasena</label>
                <input id="password" type="password" name="password" required autocomplete="new-password"
                       class="mt-1 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-blue-600 sm:text-sm/6"/>
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirmar contrasena</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
                       class="mt-1 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-blue-600 sm:text-sm/6"/>
            </div>

            <div class="rounded-md bg-blue-50 p-3 text-sm text-blue-800">
                <strong>Plan basico:</strong> US$45/mes — incluye 1 empresa y 2 usuarios.
            </div>

            <button type="submit"
                    class="flex w-full justify-center rounded-md bg-blue-600 px-3 py-1.5 text-sm/6 font-semibold text-white shadow-xs hover:bg-blue-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                Crear cuenta
            </button>
        </form>

        <p class="text-center text-sm text-gray-500">
            ¿Ya tienes cuenta?
            <a href="{{ route('login') }}" class="font-medium text-blue-600 hover:text-blue-500">Inicia sesion</a>
        </p>

    </div>
</div>

</body>
</html>
