<!DOCTYPE html>
<html lang="es" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }} — Configurar empresa</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full">

<div class="min-h-full">
    <div class="mx-auto max-w-2xl px-4 py-12 sm:px-6 lg:px-8">

        <h1 class="text-2xl font-bold text-gray-900">Configurar empresa</h1>
        <p class="mt-1 text-sm text-gray-500">Ingresa los datos de tu empresa para comenzar a usar la plataforma.</p>

        @if($errors->any())
        <div class="mt-4 rounded-md bg-red-50 p-3 text-sm text-red-700">
            <ul class="list-disc pl-4 space-y-1">
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form method="POST" action="{{ route('company-setup.store') }}" class="mt-8 space-y-6">
            @csrf

            <div class="rounded-lg bg-white p-6 shadow ring-1 ring-gray-900/5 space-y-5">
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="razon_social" class="block text-sm font-medium text-gray-700">Razon social *</label>
                        <input id="razon_social" type="text" name="razon_social" required value="{{ old('razon_social') }}"
                               class="mt-1 block w-full rounded-md bg-white px-3 py-1.5 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus:outline-2 focus:-outline-offset-2 focus:outline-blue-600"/>
                    </div>

                    <div>
                        <label for="rnc" class="block text-sm font-medium text-gray-700">RNC *</label>
                        <input id="rnc" type="text" name="rnc" required value="{{ old('rnc') }}" maxlength="20"
                               class="mt-1 block w-full rounded-md bg-white px-3 py-1.5 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus:outline-2 focus:-outline-offset-2 focus:outline-blue-600"/>
                    </div>

                    <div>
                        <label for="nombre_comercial" class="block text-sm font-medium text-gray-700">Nombre comercial</label>
                        <input id="nombre_comercial" type="text" name="nombre_comercial" value="{{ old('nombre_comercial') }}"
                               class="mt-1 block w-full rounded-md bg-white px-3 py-1.5 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus:outline-2 focus:-outline-offset-2 focus:outline-blue-600"/>
                    </div>

                    <div>
                        <label for="telefono" class="block text-sm font-medium text-gray-700">Telefono</label>
                        <input id="telefono" type="text" name="telefono" value="{{ old('telefono') }}"
                               class="mt-1 block w-full rounded-md bg-white px-3 py-1.5 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus:outline-2 focus:-outline-offset-2 focus:outline-blue-600"/>
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}"
                               class="mt-1 block w-full rounded-md bg-white px-3 py-1.5 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus:outline-2 focus:-outline-offset-2 focus:outline-blue-600"/>
                    </div>

                    <div class="sm:col-span-2">
                        <label for="direccion" class="block text-sm font-medium text-gray-700">Direccion</label>
                        <input id="direccion" type="text" name="direccion" value="{{ old('direccion') }}"
                               class="mt-1 block w-full rounded-md bg-white px-3 py-1.5 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus:outline-2 focus:-outline-offset-2 focus:outline-blue-600"/>
                    </div>

                    <div>
                        <label for="municipio" class="block text-sm font-medium text-gray-700">Municipio</label>
                        <input id="municipio" type="text" name="municipio" value="{{ old('municipio') }}"
                               class="mt-1 block w-full rounded-md bg-white px-3 py-1.5 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus:outline-2 focus:-outline-offset-2 focus:outline-blue-600"/>
                    </div>

                    <div>
                        <label for="provincia" class="block text-sm font-medium text-gray-700">Provincia</label>
                        <input id="provincia" type="text" name="provincia" value="{{ old('provincia') }}"
                               class="mt-1 block w-full rounded-md bg-white px-3 py-1.5 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus:outline-2 focus:-outline-offset-2 focus:outline-blue-600"/>
                    </div>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit"
                        class="inline-flex items-center rounded-md bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-500">
                    Crear empresa y continuar
                </button>
            </div>
        </form>

    </div>
</div>

</body>
</html>
