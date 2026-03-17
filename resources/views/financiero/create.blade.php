@extends('layouts.app')
@section('title', 'Nuevo año fiscal')

@section('content')
<div class="mx-auto max-w-lg px-4 py-10 sm:px-6 lg:px-8">

    <nav class="flex items-center gap-x-2 text-sm text-gray-500 mb-6">
        <a href="{{ route('financiero.index') }}" class="hover:text-gray-700">Financiero</a>
        <span>/</span>
        <span class="text-gray-900 font-medium">Nuevo año fiscal</span>
    </nav>

    <div class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-xl px-4 py-6 sm:p-6">
        <h2 class="text-sm font-semibold text-gray-900 mb-5">Agregar año fiscal</h2>
        <form method="POST" action="{{ route('financiero.store') }}">
            @csrf
            <div class="space-y-5">
                <div>
                    <label class="block text-sm font-medium text-gray-900">Año fiscal <span class="text-red-500">*</span></label>
                    <input type="number" name="anio_fiscal" required min="2000" max="{{ date('Y') }}"
                           value="{{ old('anio_fiscal', $suggested) }}"
                           class="mt-1 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-blue-600 sm:text-sm">
                    @error('anio_fiscal')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-900">Moneda <span class="text-red-500">*</span></label>
                    <select name="currency"
                            class="mt-1 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-blue-600 sm:text-sm">
                        <option value="DOP" @selected(old('currency') !== 'USD')>DOP (Peso dominicano)</option>
                        <option value="USD" @selected(old('currency') === 'USD')>USD (Dólar)</option>
                    </select>
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-x-3">
                <a href="{{ route('financiero.index') }}"
                   class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                    Cancelar
                </a>
                <button type="submit"
                        class="rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500">
                    Crear
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
