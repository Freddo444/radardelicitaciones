@extends('layouts.app')
@section('title', 'Rubros')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">

    {{-- Page header --}}
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-base font-semibold text-gray-900">Rubros vigilados</h1>
            <p class="mt-1 text-sm text-gray-500">
                {{ $rubros->total() }} rubro{{ $rubros->total() !== 1 ? 's' : '' }} registrado{{ $rubros->total() !== 1 ? 's' : '' }}.
            </p>
        </div>
        <div class="mt-4 sm:mt-0">
            <button command="show-modal" commandfor="rubros-drawer"
                    class="inline-flex items-center gap-x-2 rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-xs hover:bg-blue-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="-ml-0.5 size-5">
                    <path d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z"/>
                </svg>
                Agregar rubro
            </button>
        </div>
    </div>

    {{-- Rubros list --}}
    <div class="mt-8">
        @if($rubros->isEmpty())
            {{-- Empty state --}}
            <div class="text-center py-16">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" class="mx-auto size-12 text-gray-400">
                    <path d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <h3 class="mt-2 text-sm font-semibold text-gray-900">Sin rubros configurados</h3>
                <p class="mt-1 text-sm text-gray-500">Agrega los códigos UNSPSC que deseas monitorear.</p>
                <div class="mt-6">
                    <button command="show-modal" commandfor="rubros-drawer"
                            class="inline-flex items-center gap-x-2 rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-xs hover:bg-blue-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                        <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="-ml-0.5 size-5">
                            <path d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z"/>
                        </svg>
                        Agregar primer rubro
                    </button>
                </div>
            </div>
        @else
            <ul role="list" class="divide-y divide-gray-100 rounded-xl border border-gray-200 bg-white">
                @foreach($rubros as $rubro)
                <li class="flex items-center justify-between gap-x-6 px-6 py-4">
                    {{-- Left: code + name --}}
                    <div class="flex min-w-0 items-center gap-x-4">
                        <span class="inline-flex shrink-0 items-center rounded-md bg-blue-50 px-2 py-1 font-mono text-xs font-semibold text-blue-700 ring-1 ring-inset ring-blue-700/10">
                            {{ $rubro->code }}
                        </span>
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-gray-900">{{ $rubro->name }}</p>
                        </div>
                    </div>

                    {{-- Right: active toggle + delete --}}
                    <div class="flex shrink-0 items-center gap-x-4">
                        {{-- Active toggle --}}
                        <form method="POST" action="{{ route('rubros.toggle', $rubro) }}">
                            @csrf @method('PATCH')
                            <button type="submit"
                                    title="{{ $rubro->active ? 'Desactivar' : 'Activar' }}"
                                    class="group relative inline-flex w-8 shrink-0 cursor-pointer rounded-full p-px transition-colors duration-200 ease-in-out focus:outline-none {{ $rubro->active ? 'bg-blue-600' : 'bg-gray-200' }}">
                                <span class="size-4 rounded-full bg-white shadow-xs ring-1 ring-gray-900/5 transition-transform duration-200 ease-in-out {{ $rubro->active ? 'translate-x-3.5' : 'translate-x-0' }}"></span>
                            </button>
                        </form>

                        {{-- Delete --}}
                        <form method="POST" action="{{ route('rubros.destroy', $rubro) }}"
                              onsubmit="return confirm('¿Eliminar el rubro {{ $rubro->code }}? Esta acción no se puede deshacer.')">
                            @csrf @method('DELETE')
                            <button type="submit"
                                    class="rounded-md p-1 text-gray-400 hover:text-red-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-500">
                                <span class="sr-only">Eliminar</span>
                                <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="size-4">
                                    <path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 0 0 6 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 1 0 .23 1.482l.149-.022.841 10.518A2.75 2.75 0 0 0 7.596 19h4.807a2.75 2.75 0 0 0 2.742-2.53l.841-10.52.149.023a.75.75 0 0 0 .23-1.482A41.03 41.03 0 0 0 14 4.193V3.75A2.75 2.75 0 0 0 11.25 1h-2.5ZM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4ZM8.58 7.72a.75.75 0 0 0-1.5.06l.3 7.5a.75.75 0 1 0 1.5-.06l-.3-7.5Zm4.34.06a.75.75 0 1 0-1.5-.06l-.3 7.5a.75.75 0 1 0 1.5.06l.3-7.5Z" clip-rule="evenodd"/>
                                </svg>
                            </button>
                        </form>
                    </div>
                </li>
                @endforeach
            </ul>
        @endif

        {{-- Pagination --}}
        <div class="mt-6">
            {{ $rubros->links('components.pagination') }}
        </div>
    </div>

</div>

{{-- Add rubro drawer --}}
<el-dialog>
    <dialog id="rubros-drawer" aria-labelledby="drawer-title"
            class="fixed inset-0 size-auto max-h-none max-w-none overflow-hidden bg-transparent backdrop:bg-transparent">
        <div tabindex="0" class="absolute inset-0 pl-10 focus:outline-none sm:pl-16">
            <el-dialog-panel class="ml-auto block size-full max-w-md transform transition duration-500 ease-in-out data-closed:translate-x-full sm:duration-700">
                <div class="relative flex h-full flex-col divide-y divide-gray-200 bg-white shadow-xl">

                    {{-- Drawer header --}}
                    <div class="flex items-start justify-between px-4 py-6 sm:px-6">
                        <h2 id="drawer-title" class="text-base font-semibold text-gray-900">Agregar rubro</h2>
                        <button type="button" command="close" commandfor="rubros-drawer"
                                class="relative ml-3 rounded-md text-gray-400 hover:text-gray-500 focus-visible:outline-2 focus-visible:outline-blue-600">
                            <span class="sr-only">Cerrar</span>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" class="size-6">
                                <path d="M6 18 18 6M6 6l12 12" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                    </div>

                    {{-- Drawer body --}}
                    <div class="flex min-h-0 flex-1 flex-col overflow-y-auto px-4 py-6 sm:px-6">

                        <label for="rubro-search" class="block text-sm font-medium text-gray-900">Código UNSPSC</label>
                        <p class="mt-1 text-xs text-gray-500">Ingresa el código de 8 dígitos (ej. <span class="font-mono">72101500</span>).</p>
                        <div class="relative mt-2">
                            <input type="text" id="rubro-search" autocomplete="off" maxlength="8"
                                   placeholder="ej. 72101500"
                                   class="w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600"/>
                            <div id="search-spinner" class="absolute inset-y-0 right-3 hidden items-center">
                                <svg class="size-4 animate-spin text-blue-500" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                                </svg>
                            </div>
                        </div>
                        <ul id="rubro-results" role="list" class="mt-2 divide-y divide-gray-100 rounded-md border border-gray-100 empty:hidden"></ul>

                        {{-- Selected rubro display --}}
                        <div id="selected-rubro" class="mt-4 hidden rounded-lg border border-blue-200 bg-blue-50 px-4 py-3">
                            <p class="text-xs font-medium text-blue-600">Rubro seleccionado</p>
                            <p id="selected-label" class="mt-0.5 text-sm font-semibold text-blue-900"></p>
                            <button type="button" id="clear-selection"
                                    class="mt-1 text-xs text-blue-500 underline hover:text-blue-700">
                                Cambiar selección
                            </button>
                        </div>

                    </div>

                    {{-- Drawer footer --}}
                    <div class="flex shrink-0 justify-end gap-x-3 px-4 py-4">
                        <button type="button" command="close" commandfor="rubros-drawer"
                                class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-xs inset-ring inset-ring-gray-300 hover:inset-ring-gray-400">
                            Cancelar
                        </button>
                        <button type="button" id="rubro-save"
                                class="inline-flex justify-center rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-xs hover:bg-blue-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 disabled:opacity-40 disabled:cursor-not-allowed"
                                disabled>
                            Guardar
                        </button>
                    </div>

                    {{-- Hidden submit form --}}
                    <form id="rubro-form" method="POST" action="{{ route('rubros.store') }}" class="hidden">
                        @csrf
                        <input type="hidden" name="code"  id="form-code"/>
                        <input type="hidden" name="name"  id="form-name"/>
                        <input type="hidden" name="level" id="form-level"/>
                    </form>

                </div>
            </el-dialog-panel>
        </div>
    </dialog>
</el-dialog>

<script>
(function () {
    const searchInput   = document.getElementById('rubro-search');
    const resultsList   = document.getElementById('rubro-results');
    const spinner       = document.getElementById('search-spinner');
    const selectedBox   = document.getElementById('selected-rubro');
    const selectedLabel = document.getElementById('selected-label');
    const clearBtn      = document.getElementById('clear-selection');
    const saveBtn       = document.getElementById('rubro-save');
    const formCode      = document.getElementById('form-code');
    const formName      = document.getElementById('form-name');
    const formLevel     = document.getElementById('form-level');

    let debounceTimer;

    searchInput.addEventListener('input', function () {
        clearTimeout(debounceTimer);
        const q = this.value.trim();

        if (q.length < 8) {
            resultsList.innerHTML = '';
            return;
        }

        debounceTimer = setTimeout(async () => {
            spinner.classList.remove('hidden');
            spinner.classList.add('flex');
            resultsList.innerHTML = '';

            try {
                const res  = await fetch('/rubros/search?q=' + encodeURIComponent(q));
                const data = await res.json();

                spinner.classList.add('hidden');
                spinner.classList.remove('flex');

                if (!data.length) {
                    resultsList.innerHTML = '<li class="px-3 py-3 text-xs text-gray-400">Código no encontrado en el catálogo UNSPSC.</li>';
                    return;
                }

                resultsList.innerHTML = data.map(item =>
                    `<li class="flex cursor-pointer items-center gap-x-3 px-3 py-2.5 text-sm hover:bg-blue-50"
                         data-code="${item.code}" data-name="${item.name}" data-level="${item.level}">
                        <span class="shrink-0 font-mono text-xs font-semibold text-blue-600">${item.code}</span>
                        <span class="min-w-0 truncate text-gray-700">${item.name}</span>
                     </li>`
                ).join('');

                resultsList.querySelectorAll('li[data-code]').forEach(li => {
                    li.addEventListener('click', () => selectRubro(li.dataset.code, li.dataset.name, li.dataset.level));
                });
            } catch {
                spinner.classList.add('hidden');
                spinner.classList.remove('flex');
                resultsList.innerHTML = '<li class="px-3 py-3 text-xs text-red-400">Error al consultar el catálogo.</li>';
            }
        }, 300);
    });

    function selectRubro(code, name, level) {
        formCode.value  = code;
        formName.value  = name;
        formLevel.value = level;

        selectedLabel.textContent = code + ' — ' + name;
        selectedBox.classList.remove('hidden');
        resultsList.innerHTML = '';
        searchInput.value = '';
        saveBtn.disabled = false;
    }

    clearBtn.addEventListener('click', () => {
        formCode.value  = '';
        formName.value  = '';
        formLevel.value = '';
        selectedBox.classList.add('hidden');
        saveBtn.disabled = true;
        searchInput.focus();
    });

    saveBtn.addEventListener('click', () => document.getElementById('rubro-form').submit());

    document.getElementById('rubros-drawer').addEventListener('close', () => {
        searchInput.value = '';
        formCode.value    = '';
        formName.value    = '';
        formLevel.value   = '';
        resultsList.innerHTML = '';
        selectedBox.classList.add('hidden');
        saveBtn.disabled = true;
    });
})();
</script>

@endsection
