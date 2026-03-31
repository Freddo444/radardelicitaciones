@extends('layouts.app')
@section('title', 'Nueva empresa')

@section('content')
<div class="mx-auto max-w-lg py-10">
    <h1 class="text-2xl font-bold text-gray-900">Registrar empresa</h1>
    <p class="mt-1 text-sm text-gray-500">Completa los datos de tu empresa para comenzar.</p>

    <form method="POST" action="{{ route('companies.store') }}" class="mt-8 space-y-5">
        @csrf

        <div>
            <label for="razon_social" class="block text-sm font-medium text-gray-700">Razón social *</label>
            <input type="text" name="razon_social" id="razon_social" required value="{{ old('razon_social') }}"
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
            @error('razon_social') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="rnc" class="block text-sm font-medium text-gray-700">RNC *</label>
            <input type="text" name="rnc" id="rnc" required value="{{ old('rnc') }}" maxlength="20"
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
            @error('rnc') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="nombre_comercial" class="block text-sm font-medium text-gray-700">Nombre comercial</label>
            <input type="text" name="nombre_comercial" id="nombre_comercial" value="{{ old('nombre_comercial') }}"
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label for="telefono" class="block text-sm font-medium text-gray-700">Teléfono</label>
                <input type="text" name="telefono" id="telefono" value="{{ old('telefono') }}"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
            </div>
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                <input type="email" name="email" id="email" value="{{ old('email') }}"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
            </div>
        </div>

        <div>
            <label for="direccion" class="block text-sm font-medium text-gray-700">Dirección</label>
            <input type="text" name="direccion" id="direccion" value="{{ old('direccion') }}"
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label for="municipio" class="block text-sm font-medium text-gray-700">Municipio</label>
                <input type="text" name="municipio" id="municipio" value="{{ old('municipio') }}"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
            </div>
            <div>
                <label for="provincia" class="block text-sm font-medium text-gray-700">Provincia</label>
                <input type="text" name="provincia" id="provincia" value="{{ old('provincia') }}"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
            </div>
        </div>

        <div class="flex justify-end pt-4">
            <button type="submit"
                    class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                Crear empresa
            </button>
        </div>
    </form>
</div>
@endsection
