@extends('layouts.app')
@section('title', 'Nueva Oferta')

@section('content')
<div class="mx-auto max-w-2xl px-4 py-10 sm:px-6 lg:px-8">

    <div class="mb-6">
        <a href="{{ route('ofertas.index') }}" class="text-sm text-blue-600 hover:underline">← Ofertas</a>
    </div>

    @if($bid)
    <div class="mb-6 rounded-md bg-blue-50 p-4 text-sm text-blue-800 ring-1 ring-inset ring-blue-200">
        Vinculando a proceso del monitor:
        <strong>{{ $bid->title ?? $bid->process_code }}</strong>
        @if($bid->buyer_name) · {{ $bid->buyer_name }} @endif
    </div>
    @endif

    <div class="rounded-xl border border-gray-200 bg-white">
        <div class="border-b border-gray-200 px-6 py-4">
            <h1 class="text-sm font-semibold text-gray-900">Nueva preparación de oferta</h1>
            <p class="mt-0.5 text-xs text-gray-500">Crea el espacio de trabajo para preparar esta licitación.</p>
        </div>
        <div class="px-6 py-6">
            <form method="POST" action="{{ route('ofertas.store') }}" class="space-y-5">
                @csrf
                @if($bid)
                    <input type="hidden" name="bid_id" value="{{ $bid->id }}">
                @endif

                <div>
                    <label for="proceso_nombre" class="block text-sm font-medium text-gray-900">
                        Nombre del proceso <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="proceso_nombre" name="proceso_nombre" required
                           value="{{ old('proceso_nombre', $bid?->title) }}"
                           placeholder="ej. Construcción de escuela secundaria..."
                           class="mt-1.5 w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600"/>
                </div>

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div>
                        <label for="proceso_codigo" class="block text-sm font-medium text-gray-900">Código de proceso</label>
                        <input type="text" id="proceso_codigo" name="proceso_codigo" maxlength="100"
                               value="{{ old('proceso_codigo', $bid?->process_code) }}"
                               placeholder="ej. LICITACION-001-2024"
                               class="mt-1.5 w-full rounded-md bg-white px-3 py-2 text-sm font-mono text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600"/>
                    </div>
                    <div>
                        <label for="entidad_nombre" class="block text-sm font-medium text-gray-900">Entidad contratante</label>
                        <input type="text" id="entidad_nombre" name="entidad_nombre" maxlength="255"
                               value="{{ old('entidad_nombre', $bid?->buyer_name) }}"
                               class="mt-1.5 w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600"/>
                    </div>
                </div>

                <div>
                    <label for="fecha_limite" class="block text-sm font-medium text-gray-900">Fecha límite de entrega</label>
                    <input type="datetime-local" id="fecha_limite" name="fecha_limite"
                           value="{{ old('fecha_limite', $bid?->tender_deadline ? \Carbon\Carbon::parse($bid->tender_deadline)->format('Y-m-d\TH:i') : '') }}"
                           class="mt-1.5 w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600"/>
                </div>

                <div>
                    <label for="notas" class="block text-sm font-medium text-gray-900">Notas internas</label>
                    <textarea id="notas" name="notas" rows="3"
                              placeholder="Observaciones, estrategia, contactos clave..."
                              class="mt-1.5 w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600">{{ old('notas') }}</textarea>
                </div>

                @if($errors->any())
                <div class="rounded-md bg-red-50 p-4 text-sm text-red-800 ring-1 ring-inset ring-red-200">
                    <ul class="list-disc pl-5 space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <div class="flex justify-end gap-x-3 pt-2">
                    <a href="{{ route('ofertas.index') }}"
                       class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                        Cancelar
                    </a>
                    <button type="submit"
                            class="rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                        Crear oferta
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
