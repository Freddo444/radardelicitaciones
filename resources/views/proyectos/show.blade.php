@extends('layouts.app')
@section('title', $proyecto->nombre)

@section('content')
<div class="mx-auto max-w-4xl px-4 py-10 sm:px-6 lg:px-8">

    {{-- Header --}}
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <nav class="flex items-center gap-x-2 text-sm text-gray-500">
                <a href="{{ route('proyectos.index') }}" class="hover:text-gray-700">Proyectos</a>
                <span>/</span>
                <span class="text-gray-900 font-medium">{{ $proyecto->nombre }}</span>
            </nav>
        </div>
        <form method="POST" action="{{ route('proyectos.destroy', $proyecto) }}"
              onsubmit="return confirm('¿Eliminar este proyecto y todos sus documentos? Esta acción no se puede deshacer.')"
              class="mt-4 sm:mt-0">
            @csrf @method('DELETE')
            <button type="submit"
                    class="inline-flex items-center gap-x-1.5 rounded-md bg-red-50 px-2.5 py-1.5 text-sm font-semibold text-red-700 ring-1 ring-inset ring-red-200 hover:bg-red-100">
                Eliminar proyecto
            </button>
        </form>
    </div>

    @if(session('success'))
        <div class="mt-4 rounded-md bg-green-50 p-4 text-sm text-green-800 ring-1 ring-inset ring-green-200">
            {{ session('success') }}
        </div>
    @endif

    {{-- ── Project details form ───────────────────────────────── --}}
    <form method="POST" action="{{ route('proyectos.update', $proyecto) }}" class="mt-8 space-y-8">
        @csrf

        {{-- Section: Identificación --}}
        <div class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-xl">
            <div class="px-4 py-5 sm:p-6">
                <h2 class="text-sm font-semibold text-gray-900 mb-5">Identificación del proyecto</h2>
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-900">Nombre del proyecto <span class="text-red-500">*</span></label>
                        <input type="text" name="nombre" required value="{{ old('nombre', $proyecto->nombre) }}"
                               class="mt-1 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-blue-600 sm:text-sm">
                        @error('nombre')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-900">Entidad contratante <span class="text-red-500">*</span></label>
                        <input type="text" name="cliente" required value="{{ old('cliente', $proyecto->cliente) }}"
                               class="mt-1 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-blue-600 sm:text-sm">
                        @error('cliente')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-900">Número de contrato</label>
                        <input type="text" name="numero_contrato" value="{{ old('numero_contrato', $proyecto->numero_contrato) }}"
                               class="mt-1 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-blue-600 sm:text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-900">Código UNSPSC</label>
                        <input type="text" name="unspsc_codigo" value="{{ old('unspsc_codigo', $proyecto->unspsc_codigo) }}"
                               placeholder="ej. 72101500"
                               class="mt-1 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-blue-600 sm:text-sm font-mono">
                    </div>
                </div>
            </div>
        </div>

        {{-- Section: Económico --}}
        <div class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-xl">
            <div class="px-4 py-5 sm:p-6">
                <h2 class="text-sm font-semibold text-gray-900 mb-5">Datos económicos y temporales</h2>
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-900">Monto del contrato</label>
                        <input type="number" name="monto" step="0.01" min="0" value="{{ old('monto', $proyecto->monto) }}"
                               class="mt-1 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-blue-600 sm:text-sm">
                        @error('monto')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-900">Moneda <span class="text-red-500">*</span></label>
                        <select name="currency"
                                class="mt-1 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-blue-600 sm:text-sm">
                            <option value="DOP" @selected(old('currency', $proyecto->currency) === 'DOP')>DOP (Peso dominicano)</option>
                            <option value="USD" @selected(old('currency', $proyecto->currency) === 'USD')>USD (Dólar)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-900">Fecha de inicio</label>
                        <input type="date" name="fecha_inicio" value="{{ old('fecha_inicio', $proyecto->fecha_inicio?->format('Y-m-d')) }}"
                               class="mt-1 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-blue-600 sm:text-sm">
                        @error('fecha_inicio')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-900">Fecha de fin</label>
                        <input type="date" name="fecha_fin" value="{{ old('fecha_fin', $proyecto->fecha_fin?->format('Y-m-d')) }}"
                               class="mt-1 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-blue-600 sm:text-sm">
                        @error('fecha_fin')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Section: Descripción --}}
        <div class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-xl">
            <div class="px-4 py-5 sm:p-6">
                <h2 class="text-sm font-semibold text-gray-900 mb-5">Descripción y contacto</h2>
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-900">Descripción del alcance</label>
                        <textarea name="descripcion" rows="5"
                                  class="mt-1 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-blue-600 sm:text-sm">{{ old('descripcion', $proyecto->descripcion) }}</textarea>
                        @error('descripcion')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-900">Nombre de contacto en cliente</label>
                        <input type="text" name="contacto_cliente" value="{{ old('contacto_cliente', $proyecto->contacto_cliente) }}"
                               class="mt-1 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-blue-600 sm:text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-900">Teléfono de contacto</label>
                        <input type="text" name="contacto_telefono" value="{{ old('contacto_telefono', $proyecto->contacto_telefono) }}"
                               class="mt-1 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-blue-600 sm:text-sm">
                    </div>
                </div>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit"
                    class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                Guardar cambios
            </button>
        </div>
    </form>

    {{-- ── Supporting documents ───────────────────────────────── --}}
    <div class="mt-12">
        <div class="sm:flex sm:items-center sm:justify-between">
            <h2 class="text-sm font-semibold text-gray-900">Documentos de soporte</h2>
            <button command="show-modal" commandfor="add-doc-drawer"
                    class="mt-3 sm:mt-0 inline-flex items-center gap-x-1.5 rounded-md bg-white px-2.5 py-1.5 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="-ml-0.5 size-4">
                    <path d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z"/>
                </svg>
                Agregar documento
            </button>
        </div>

        @if($proyecto->documents->isEmpty())
            <p class="mt-4 text-sm text-gray-500">Sin documentos adjuntos. Puedes agregar contratos, actas de recepción, etc.</p>
        @else
            <ul class="mt-4 divide-y divide-gray-200">
                @foreach($proyecto->documents as $doc)
                <li class="flex items-center justify-between py-3">
                    <div>
                        <p class="text-sm font-medium text-gray-900">{{ $doc->nombre }}</p>
                        <p class="text-xs text-gray-500">{{ $doc->filename }}</p>
                    </div>
                    <div class="flex items-center gap-x-3">
                        <a href="{{ route('proyectos.documents.download', [$proyecto, $doc]) }}"
                           class="text-sm text-blue-600 hover:text-blue-500">Descargar</a>
                        <form method="POST" action="{{ route('proyectos.documents.destroy', [$proyecto, $doc]) }}"
                              onsubmit="return confirm('¿Eliminar este documento?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-sm text-red-600 hover:text-red-500">Eliminar</button>
                        </form>
                    </div>
                </li>
                @endforeach
            </ul>
        @endif
    </div>

</div>

{{-- ── Add document drawer ─────────────────────────────────────── --}}
<el-dialog id="add-doc-drawer" type="slideover" placement="right">
    <div class="flex h-full flex-col overflow-y-scroll bg-white shadow-xl">
        <div class="bg-blue-800 px-4 py-6 sm:px-6">
            <div class="flex items-center justify-between">
                <h2 class="text-base font-semibold text-white">Agregar documento</h2>
                <button command="close" commandfor="add-doc-drawer" class="text-blue-200 hover:text-white">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="size-6">
                        <path d="M6 18 18 6M6 6l12 12" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
            </div>
            <p class="mt-1 text-sm text-blue-300">Adjunta contratos, actas, informes u otros soportes del proyecto.</p>
        </div>
        <div class="flex flex-1 flex-col gap-y-5 px-4 py-6 sm:px-6">
            <form method="POST" action="{{ route('proyectos.documents.store', $proyecto) }}" enctype="multipart/form-data">
                @csrf
                <div class="space-y-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-900">Etiqueta / nombre del documento <span class="text-red-500">*</span></label>
                        <input type="text" name="nombre" required placeholder="ej. Contrato firmado, Acta de recepción"
                               class="mt-1 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-blue-600 sm:text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-900">Archivo <span class="text-red-500">*</span></label>
                        <input type="file" name="file" required
                               class="mt-1 block w-full text-sm text-gray-700 file:mr-4 file:rounded-md file:border-0 file:bg-blue-50 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-blue-700 hover:file:bg-blue-100">
                        <p class="mt-1 text-xs text-gray-500">Máx. 20 MB.</p>
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-x-3">
                    <button type="button" command="close" commandfor="add-doc-drawer"
                            class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                        Cancelar
                    </button>
                    <button type="submit"
                            class="rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500">
                        Subir documento
                    </button>
                </div>
            </form>
        </div>
    </div>
</el-dialog>
@endsection
