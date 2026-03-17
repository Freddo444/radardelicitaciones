@extends('layouts.app')
@section('title', 'Proyectos')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">

    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-base font-semibold text-gray-900">Portafolio de proyectos</h1>
            <p class="mt-1 text-sm text-gray-500">{{ $projects->total() }} proyecto{{ $projects->total() !== 1 ? 's' : '' }} registrado{{ $projects->total() !== 1 ? 's' : '' }}.</p>
        </div>
        <div class="mt-4 sm:mt-0">
            <button command="show-modal" commandfor="add-project-drawer"
                    class="inline-flex items-center gap-x-2 rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-xs hover:bg-blue-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="-ml-0.5 size-5">
                    <path d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z"/>
                </svg>
                Agregar proyecto
            </button>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" action="{{ route('proyectos.index') }}" class="mt-6 flex flex-wrap items-end gap-4">
        <div>
            <label for="year" class="block text-xs font-medium text-gray-700">Año</label>
            <select name="year" id="year" onchange="this.form.submit()"
                    class="mt-1 block rounded-md border-0 py-1.5 pl-3 pr-8 text-gray-900 ring-1 ring-inset ring-gray-300 text-sm focus:ring-2 focus:ring-blue-600">
                <option value="">Todos los años</option>
                @foreach($years as $y)
                    <option value="{{ $y }}" @selected(request('year') == $y)>{{ $y }}</option>
                @endforeach
            </select>
        </div>
        @if(request()->hasAny(['year', 'rubro']))
            <a href="{{ route('proyectos.index') }}" class="text-sm text-blue-600 hover:text-blue-500">Limpiar filtros</a>
        @endif
    </form>

    <div class="mt-8 flow-root">
        @if($projects->isEmpty())
            <div class="text-center py-16">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" class="mx-auto size-12 text-gray-400">
                    <path d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 0 0 .75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 0 0-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0 1 12 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 0 1-.673-.38m0 0A2.18 2.18 0 0 1 3 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 0 1 3.413-.387m7.5 0V5.25A2.25 2.25 0 0 0 13.5 3h-3a2.25 2.25 0 0 0-2.25 2.25v.894m7.5 0a48.667 48.667 0 0 0-7.5 0M12 12.75h.008v.008H12v-.008Z" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <h3 class="mt-2 text-sm font-semibold text-gray-900">Sin proyectos registrados</h3>
                <p class="mt-1 text-sm text-gray-500">Registra los proyectos ejecutados para incluirlos en las ofertas.</p>
            </div>
        @else
            <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                    <table class="min-w-full divide-y divide-gray-300">
                        <thead>
                            <tr>
                                <th class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-0">Proyecto</th>
                                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Cliente</th>
                                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Período</th>
                                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Monto</th>
                                <th class="relative py-3.5 pl-3 pr-4 sm:pr-0"><span class="sr-only">Ver</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($projects as $proyecto)
                            <tr>
                                <td class="py-4 pl-4 pr-3 sm:pl-0">
                                    <div class="font-medium text-sm text-gray-900">{{ $proyecto->nombre }}</div>
                                    @if($proyecto->numero_contrato)
                                        <div class="text-xs text-gray-500">Contrato: {{ $proyecto->numero_contrato }}</div>
                                    @endif
                                    @if($proyecto->unspsc_codigo)
                                        <div class="text-xs text-gray-400 font-mono">{{ $proyecto->unspsc_codigo }}</div>
                                    @endif
                                </td>
                                <td class="px-3 py-4 text-sm text-gray-700">{{ $proyecto->cliente }}</td>
                                <td class="px-3 py-4 text-sm text-gray-500">{{ $proyecto->periodoLabel() }}</td>
                                <td class="px-3 py-4 text-sm text-gray-700">{{ $proyecto->montoFormatted() }}</td>
                                <td class="py-4 pl-3 pr-4 text-right text-sm sm:pr-0">
                                    <a href="{{ route('proyectos.show', $proyecto) }}"
                                       class="text-blue-600 hover:text-blue-500 font-medium">Ver<span class="sr-only">, {{ $proyecto->nombre }}</span></a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            @if($projects->hasPages())
                <div class="mt-6">{{ $projects->links() }}</div>
            @endif
        @endif
    </div>

</div>

{{-- ── Add project drawer ─────────────────────────────────────── --}}
<el-dialog>
    <dialog id="add-project-drawer"
            class="fixed inset-0 size-auto max-h-none max-w-none overflow-hidden bg-transparent backdrop:bg-transparent">
        <el-dialog-backdrop class="fixed inset-0 bg-gray-500/75 transition-opacity duration-300 ease-linear data-closed:opacity-0"></el-dialog-backdrop>
        <div class="fixed inset-0 overflow-hidden">
            <div class="absolute inset-0 overflow-hidden">
                <el-dialog-panel class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10 transform transition duration-500 ease-in-out data-closed:translate-x-full sm:duration-700">
                    <div class="pointer-events-auto flex h-full flex-col overflow-y-scroll bg-white shadow-xl w-full max-w-md">
                        <div class="bg-blue-800 px-4 py-6 sm:px-6">
                            <div class="flex items-center justify-between">
                                <h2 class="text-base font-semibold text-white">Nuevo proyecto</h2>
                                <button command="close" commandfor="add-project-drawer" class="text-blue-200 hover:text-white">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="size-6">
                                        <path d="M6 18 18 6M6 6l12 12" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </button>
                            </div>
                            <p class="mt-1 text-sm text-blue-300">Datos básicos — completa el resto en la ficha del proyecto.</p>
                        </div>
                        <div class="flex flex-1 flex-col gap-y-6 px-4 py-6 sm:px-6">
                            <form method="POST" action="{{ route('proyectos.store') }}">
                                @csrf
                                <div class="space-y-5">
                                    <div>
                                        <label for="nombre" class="block text-sm font-medium text-gray-900">Nombre del proyecto <span class="text-red-500">*</span></label>
                                        <input type="text" name="nombre" id="nombre" required
                                               class="mt-1 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-blue-600 sm:text-sm">
                                    </div>
                                    <div>
                                        <label for="cliente" class="block text-sm font-medium text-gray-900">Entidad contratante <span class="text-red-500">*</span></label>
                                        <input type="text" name="cliente" id="cliente" required
                                               class="mt-1 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-blue-600 sm:text-sm">
                                    </div>
                                </div>
                                <div class="mt-6 flex justify-end gap-x-3">
                                    <button type="button" command="close" commandfor="add-project-drawer"
                                            class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                                        Cancelar
                                    </button>
                                    <button type="submit"
                                            class="rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500">
                                        Crear proyecto
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
@endsection
