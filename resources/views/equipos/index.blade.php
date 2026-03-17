@extends('layouts.app')
@section('title', 'Equipos')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">

    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-base font-semibold text-gray-900">Inventario de equipos</h1>
            <p class="mt-1 text-sm text-gray-500">{{ $items->total() }} equipo{{ $items->total() !== 1 ? 's' : '' }} registrado{{ $items->total() !== 1 ? 's' : '' }}.</p>
        </div>
        <div class="mt-4 sm:mt-0">
            <button command="show-modal" commandfor="add-equipo-drawer"
                    class="inline-flex items-center gap-x-2 rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-xs hover:bg-blue-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="-ml-0.5 size-5">
                    <path d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z"/>
                </svg>
                Agregar equipo
            </button>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" action="{{ route('equipos.index') }}" class="mt-6 flex flex-wrap items-end gap-4">
        <div>
            <label for="tenencia" class="block text-xs font-medium text-gray-700">Tenencia</label>
            <select name="tenencia" id="tenencia" onchange="this.form.submit()"
                    class="mt-1 block rounded-md border-0 py-1.5 pl-3 pr-8 text-gray-900 ring-1 ring-inset ring-gray-300 text-sm focus:ring-2 focus:ring-blue-600">
                <option value="">Todas</option>
                @foreach($tenencias as $val => $label)
                    <option value="{{ $val }}" @selected(request('tenencia') === $val)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <label class="flex items-center gap-x-2 text-sm text-gray-700">
            <input type="checkbox" name="inactivos" value="1" onchange="this.form.submit()"
                   @checked(request()->boolean('inactivos'))
                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-600">
            Mostrar inactivos
        </label>
        @if(request()->hasAny(['tenencia', 'inactivos']))
            <a href="{{ route('equipos.index') }}" class="text-sm text-blue-600 hover:text-blue-500">Limpiar</a>
        @endif
    </form>

    @if(session('success'))
        <div class="mt-4 rounded-md bg-green-50 p-4 text-sm text-green-800 ring-1 ring-inset ring-green-200">
            {{ session('success') }}
        </div>
    @endif

    <div class="mt-8 flow-root">
        @if($items->isEmpty())
            <div class="text-center py-16">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" class="mx-auto size-12 text-gray-400">
                    <path d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l5.654-4.654m5.5-4.236.084-.003a1.125 1.125 0 0 1 .82 1.916l-.253.253c-.34.34-.508.81-.508 1.28v.252c0 .408-.16.8-.444 1.09l-.39.39a1.125 1.125 0 0 1-1.59 0l-1.5-1.5a1.125 1.125 0 0 1 0-1.59l.39-.39c.29-.284.68-.444 1.09-.444h.252c.47 0 .94-.168 1.28-.508l.253-.253c.348-.348.94-.348 1.288 0Z" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <h3 class="mt-2 text-sm font-semibold text-gray-900">Sin equipos registrados</h3>
                <p class="mt-1 text-sm text-gray-500">Agrega el parque de equipos de la empresa para incluirlos en las ofertas.</p>
            </div>
        @else
            <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                    <table class="min-w-full divide-y divide-gray-300">
                        <thead>
                            <tr>
                                <th class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-0">Descripción</th>
                                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Marca / Modelo / Año</th>
                                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Tenencia</th>
                                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Condición</th>
                                <th class="px-3 py-3.5 text-center text-sm font-semibold text-gray-900">Cant.</th>
                                <th class="relative py-3.5 pl-3 pr-4 sm:pr-0"><span class="sr-only">Acciones</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200" id="equipment-table">
                            @foreach($items as $item)
                            <tr class="{{ $item->active ? '' : 'opacity-50' }}" id="row-{{ $item->id }}">
                                <td class="py-4 pl-4 pr-3 sm:pl-0">
                                    <div class="text-sm font-medium text-gray-900">{{ $item->descripcion }}</div>
                                    @if($item->capacidad)
                                        <div class="text-xs text-gray-500">{{ $item->capacidad }}</div>
                                    @endif
                                    @if(!$item->active)
                                        <span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600">Inactivo</span>
                                    @endif
                                </td>
                                <td class="px-3 py-4 text-sm text-gray-700">
                                    {{ $item->fichaLabel() ?: '—' }}
                                </td>
                                <td class="px-3 py-4 text-sm">
                                    @php
                                        $tenenciaColors = ['propio' => 'bg-blue-50 text-blue-700', 'arrendado' => 'bg-yellow-50 text-yellow-700', 'leasing' => 'bg-purple-50 text-purple-700'];
                                    @endphp
                                    <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium {{ $tenenciaColors[$item->tenencia] ?? '' }}">
                                        {{ $tenencias[$item->tenencia] ?? $item->tenencia }}
                                    </span>
                                </td>
                                <td class="px-3 py-4 text-sm">
                                    @php
                                        $condColors = ['bueno' => 'bg-green-50 text-green-700', 'regular' => 'bg-yellow-50 text-yellow-700', 'malo' => 'bg-red-50 text-red-700'];
                                    @endphp
                                    <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium {{ $condColors[$item->condicion] ?? '' }}">
                                        {{ $condiciones[$item->condicion] ?? $item->condicion }}
                                    </span>
                                </td>
                                <td class="px-3 py-4 text-sm text-gray-700 text-center">{{ $item->cantidad }}</td>
                                <td class="py-4 pl-3 pr-4 text-right text-sm sm:pr-0">
                                    <div class="flex justify-end gap-x-3">
                                        <button
                                            onclick="openEditDrawer({{ $item->id }}, {{ json_encode($item->descripcion) }}, {{ json_encode($item->marca) }}, {{ json_encode($item->modelo) }}, {{ json_encode($item->anio) }}, {{ json_encode($item->tenencia) }}, {{ json_encode($item->capacidad) }}, {{ json_encode($item->condicion) }}, {{ $item->cantidad }}, {{ json_encode($item->notas) }})"
                                            class="text-blue-600 hover:text-blue-500 font-medium text-sm">Editar</button>
                                        <form method="POST" action="{{ route('equipos.toggle', $item) }}">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="text-sm {{ $item->active ? 'text-gray-500 hover:text-gray-700' : 'text-green-600 hover:text-green-500' }}">
                                                {{ $item->active ? 'Desactivar' : 'Activar' }}
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('equipos.destroy', $item) }}"
                                              onsubmit="return confirm('¿Eliminar este equipo?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-sm text-red-600 hover:text-red-500">Eliminar</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            @if($items->hasPages())
                <div class="mt-6">{{ $items->links() }}</div>
            @endif
        @endif
    </div>

</div>

{{-- ── Add equipo drawer ───────────────────────────────────────── --}}
<el-dialog>
    <dialog id="add-equipo-drawer"
            class="fixed inset-0 size-auto max-h-none max-w-none overflow-hidden bg-transparent backdrop:bg-transparent">
        <el-dialog-backdrop class="fixed inset-0 bg-gray-500/75 transition-opacity duration-300 ease-linear data-closed:opacity-0"></el-dialog-backdrop>
        <div class="fixed inset-0 overflow-hidden">
            <div class="absolute inset-0 overflow-hidden">
                <el-dialog-panel class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10 transform transition duration-500 ease-in-out data-closed:translate-x-full sm:duration-700">
                    <div class="pointer-events-auto flex h-full flex-col overflow-y-scroll bg-white shadow-xl w-full max-w-md">
                        <div class="bg-blue-800 px-4 py-6 sm:px-6">
                            <div class="flex items-center justify-between">
                                <h2 class="text-base font-semibold text-white">Nuevo equipo</h2>
                                <button command="close" commandfor="add-equipo-drawer" class="text-blue-200 hover:text-white">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="size-6">
                                        <path d="M6 18 18 6M6 6l12 12" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </button>
                            </div>
                            <p class="mt-1 text-sm text-blue-300">Datos básicos. Podrás editar el detalle completo después.</p>
                        </div>
                        <div class="flex flex-1 flex-col gap-y-5 px-4 py-6 sm:px-6">
                            <form method="POST" action="{{ route('equipos.store') }}">
                                @csrf
                                <div class="space-y-5">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-900">Descripción <span class="text-red-500">*</span></label>
                                        <input type="text" name="descripcion" required placeholder="ej. Excavadora hidráulica"
                                               class="mt-1 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-blue-600 sm:text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-900">Tenencia <span class="text-red-500">*</span></label>
                                        <select name="tenencia" class="mt-1 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-blue-600 sm:text-sm">
                                            @foreach($tenencias as $val => $label)
                                                <option value="{{ $val }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-900">Cantidad <span class="text-red-500">*</span></label>
                                        <input type="number" name="cantidad" min="1" value="1"
                                               class="mt-1 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-blue-600 sm:text-sm">
                                    </div>
                                </div>
                                <div class="mt-6 flex justify-end gap-x-3">
                                    <button type="button" command="close" commandfor="add-equipo-drawer"
                                            class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                                        Cancelar
                                    </button>
                                    <button type="submit"
                                            class="rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500">
                                        Agregar
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </el-dialog-panel>
            </div>
        </div>
    </dialog>
</el-dialog>

{{-- ── Edit equipo drawer ───────────────────────────────────────── --}}
<el-dialog>
    <dialog id="edit-equipo-drawer"
            class="fixed inset-0 size-auto max-h-none max-w-none overflow-hidden bg-transparent backdrop:bg-transparent">
        <el-dialog-backdrop class="fixed inset-0 bg-gray-500/75 transition-opacity duration-300 ease-linear data-closed:opacity-0"></el-dialog-backdrop>
        <div class="fixed inset-0 overflow-hidden">
            <div class="absolute inset-0 overflow-hidden">
                <el-dialog-panel class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10 transform transition duration-500 ease-in-out data-closed:translate-x-full sm:duration-700">
                    <div class="pointer-events-auto flex h-full flex-col overflow-y-scroll bg-white shadow-xl w-full max-w-md">
                        <div class="bg-blue-800 px-4 py-6 sm:px-6">
                            <div class="flex items-center justify-between">
                                <h2 class="text-base font-semibold text-white">Editar equipo</h2>
                                <button command="close" commandfor="edit-equipo-drawer" class="text-blue-200 hover:text-white">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="size-6">
                                        <path d="M6 18 18 6M6 6l12 12" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <div class="flex flex-1 flex-col gap-y-5 px-4 py-6 sm:px-6">
                            <form id="edit-equipo-form" method="POST" action="">
                                @csrf
                                <div class="space-y-5">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-900">Descripción <span class="text-red-500">*</span></label>
                                        <input type="text" id="edit-descripcion" name="descripcion" required
                                               class="mt-1 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-blue-600 sm:text-sm">
                                    </div>
                                    <div class="grid grid-cols-3 gap-3">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-900">Marca</label>
                                            <input type="text" id="edit-marca" name="marca"
                                                   class="mt-1 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-blue-600 sm:text-sm">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-900">Modelo</label>
                                            <input type="text" id="edit-modelo" name="modelo"
                                                   class="mt-1 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-blue-600 sm:text-sm">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-900">Año</label>
                                            <input type="number" id="edit-anio" name="anio" min="1900" max="{{ date('Y') + 1 }}"
                                                   class="mt-1 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-blue-600 sm:text-sm">
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-900">Tenencia <span class="text-red-500">*</span></label>
                                            <select id="edit-tenencia" name="tenencia" class="mt-1 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-blue-600 sm:text-sm">
                                                @foreach($tenencias as $val => $label)
                                                    <option value="{{ $val }}">{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-900">Condición <span class="text-red-500">*</span></label>
                                            <select id="edit-condicion" name="condicion" class="mt-1 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-blue-600 sm:text-sm">
                                                @foreach($condiciones as $val => $label)
                                                    <option value="{{ $val }}">{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-900">Capacidad / Especificaciones técnicas</label>
                                        <input type="text" id="edit-capacidad" name="capacidad"
                                               placeholder="ej. 20 toneladas, motor CAT C7"
                                               class="mt-1 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-blue-600 sm:text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-900">Cantidad <span class="text-red-500">*</span></label>
                                        <input type="number" id="edit-cantidad" name="cantidad" min="1"
                                               class="mt-1 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-blue-600 sm:text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-900">Notas</label>
                                        <textarea id="edit-notas" name="notas" rows="3"
                                                  class="mt-1 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-blue-600 sm:text-sm"></textarea>
                                    </div>
                                </div>
                                <div class="mt-6 flex justify-end gap-x-3">
                                    <button type="button" command="close" commandfor="edit-equipo-drawer"
                                            class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                                        Cancelar
                                    </button>
                                    <button type="submit"
                                            class="rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500">
                                        Guardar cambios
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </el-dialog-panel>
            </div>
        </div>
    </dialog>
</el-dialog>

<script>
function openEditDrawer(id, descripcion, marca, modelo, anio, tenencia, capacidad, condicion, cantidad, notas) {
    document.getElementById('edit-equipo-form').action = '/equipos/' + id;
    document.getElementById('edit-descripcion').value = descripcion || '';
    document.getElementById('edit-marca').value = marca || '';
    document.getElementById('edit-modelo').value = modelo || '';
    document.getElementById('edit-anio').value = anio || '';
    document.getElementById('edit-tenencia').value = tenencia || 'propio';
    document.getElementById('edit-capacidad').value = capacidad || '';
    document.getElementById('edit-condicion').value = condicion || 'bueno';
    document.getElementById('edit-cantidad').value = cantidad || 1;
    document.getElementById('edit-notas').value = notas || '';

    const drawer = document.getElementById('edit-equipo-drawer');
    drawer.showModal ? drawer.showModal() : drawer.setAttribute('open', '');
}
</script>
@endsection
