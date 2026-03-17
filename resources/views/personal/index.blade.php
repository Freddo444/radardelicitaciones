@extends('layouts.app')
@section('title', 'Personal')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">

    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-base font-semibold text-gray-900">Registro de personal</h1>
            <p class="mt-1 text-sm text-gray-500">{{ $people->total() }} persona{{ $people->total() !== 1 ? 's' : '' }} registrada{{ $people->total() !== 1 ? 's' : '' }}.</p>
        </div>
        <div class="mt-4 sm:mt-0">
            <button command="show-modal" commandfor="add-person-drawer"
                    class="inline-flex items-center gap-x-2 rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-xs hover:bg-blue-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="-ml-0.5 size-5">
                    <path d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z"/>
                </svg>
                Agregar persona
            </button>
        </div>
    </div>

    <div class="mt-8 flow-root">
        @if($people->isEmpty())
            <div class="text-center py-16">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" class="mx-auto size-12 text-gray-400">
                    <path d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <h3 class="mt-2 text-sm font-semibold text-gray-900">Sin personal registrado</h3>
                <p class="mt-1 text-sm text-gray-500">Agrega el equipo de tu empresa para auto-completar CVs y formularios de oferta.</p>
                <div class="mt-6">
                    <button command="show-modal" commandfor="add-person-drawer"
                            class="inline-flex items-center gap-x-2 rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-xs hover:bg-blue-500">
                        <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="-ml-0.5 size-5">
                            <path d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z"/>
                        </svg>
                        Agregar primera persona
                    </button>
                </div>
            </div>
        @else
            <ul role="list" class="divide-y divide-gray-100">
                @foreach($people as $person)
                <li class="flex items-center justify-between gap-x-6 py-4">
                    <div class="flex min-w-0 items-center gap-x-4">
                        {{-- Avatar / photo --}}
                        <div class="size-10 shrink-0 rounded-full bg-blue-100 flex items-center justify-center text-sm font-semibold text-blue-700">
                            {{ strtoupper(substr($person->nombre, 0, 1)) }}
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-x-2">
                                <p class="text-sm font-semibold text-gray-900 truncate">{{ $person->nombre }}</p>
                                @if(!$person->active)
                                    <span class="rounded-md bg-gray-100 px-1.5 py-0.5 text-xs font-medium text-gray-500 ring-1 ring-inset ring-gray-500/10">Inactivo</span>
                                @endif
                            </div>
                            <p class="mt-0.5 text-xs text-gray-500 truncate">
                                {{ $person->cargo ?? '—' }}
                                @if($person->cedula)
                                    <span class="ml-2 font-mono">{{ $person->cedula }}</span>
                                @endif
                            </p>
                        </div>
                    </div>
                    <div class="flex shrink-0 items-center gap-x-2">
                        <a href="{{ route('personal.show', $person) }}"
                           class="rounded-md bg-white px-2.5 py-1.5 text-xs font-semibold text-gray-900 shadow-xs inset-ring inset-ring-gray-300 hover:bg-gray-50">
                            Editar
                        </a>
                        <form method="POST" action="{{ route('personal.toggle', $person) }}">
                            @csrf @method('PATCH')
                            <button type="submit" title="{{ $person->active ? 'Desactivar' : 'Activar' }}"
                                    class="group relative inline-flex w-8 shrink-0 cursor-pointer rounded-full p-px transition-colors duration-200 focus:outline-none {{ $person->active ? 'bg-blue-600' : 'bg-gray-200' }}">
                                <span class="size-4 rounded-full bg-white shadow-xs ring-1 ring-gray-900/5 transition-transform duration-200 {{ $person->active ? 'translate-x-3.5' : 'translate-x-0' }}"></span>
                            </button>
                        </form>
                    </div>
                </li>
                @endforeach
            </ul>
            <div class="mt-6">
                {{ $people->links('components.pagination') }}
            </div>
        @endif
    </div>
</div>

{{-- Add person drawer --}}
<el-dialog>
    <dialog id="add-person-drawer" aria-labelledby="add-person-title"
            class="fixed inset-0 size-auto max-h-none max-w-none overflow-hidden bg-transparent backdrop:bg-transparent">
        <div tabindex="0" class="absolute inset-0 pl-10 focus:outline-none sm:pl-16">
            <el-dialog-panel class="ml-auto block size-full max-w-md transform transition duration-500 ease-in-out data-closed:translate-x-full sm:duration-700">
                <div class="relative flex h-full flex-col divide-y divide-gray-200 bg-white shadow-xl">

                    <div class="flex items-start justify-between px-4 py-6 sm:px-6">
                        <h2 id="add-person-title" class="text-base font-semibold text-gray-900">Agregar persona</h2>
                        <button type="button" command="close" commandfor="add-person-drawer"
                                class="relative ml-3 rounded-md text-gray-400 hover:text-gray-500">
                            <span class="sr-only">Cerrar</span>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="size-6">
                                <path d="M6 18 18 6M6 6l12 12" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                    </div>

                    <form method="POST" action="{{ route('personal.store') }}" class="flex min-h-0 flex-1 flex-col">
                        @csrf
                        <div class="flex-1 space-y-5 overflow-y-auto px-4 py-6 sm:px-6">
                            <p class="text-sm text-gray-500">Ingresa los datos básicos. Podrás completar el perfil completo (educación, idiomas, experiencia) desde la ficha individual.</p>
                            <div>
                                <label for="p-nombre" class="block text-sm font-medium text-gray-900">Nombre completo <span class="text-red-500">*</span></label>
                                <input type="text" id="p-nombre" name="nombre" required
                                       placeholder="Juan Pérez"
                                       class="mt-1.5 w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600"/>
                            </div>
                            <div>
                                <label for="p-cedula" class="block text-sm font-medium text-gray-900">Cédula</label>
                                <input type="text" id="p-cedula" name="cedula" maxlength="20"
                                       placeholder="000-0000000-0"
                                       class="mt-1.5 w-full rounded-md bg-white px-3 py-2 text-sm font-mono text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600"/>
                            </div>
                            <div>
                                <label for="p-cargo" class="block text-sm font-medium text-gray-900">Cargo / Especialidad</label>
                                <input type="text" id="p-cargo" name="cargo"
                                       placeholder="Ingeniero Civil"
                                       class="mt-1.5 w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600"/>
                            </div>
                        </div>
                        <div class="flex shrink-0 justify-end gap-x-3 px-4 py-4">
                            <button type="button" command="close" commandfor="add-person-drawer"
                                    class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-xs inset-ring inset-ring-gray-300 hover:inset-ring-gray-400">
                                Cancelar
                            </button>
                            <button type="submit"
                                    class="inline-flex justify-center rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-xs hover:bg-blue-500">
                                Agregar
                            </button>
                        </div>
                    </form>
                </div>
            </el-dialog-panel>
        </div>
    </dialog>
</el-dialog>

@endsection
