@extends('layouts.app')
@section('title', 'Instituciones')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">

    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-base font-semibold text-gray-900">Departamentos de Compras</h1>
            <p class="mt-1 text-sm text-gray-500">
                {{ number_format($institutions->total()) }} instituci{{ $institutions->total() !== 1 ? 'ones' : 'ón' }} encontrada{{ $institutions->total() !== 1 ? 's' : '' }}
                de {{ number_format($totalCount) }} total{{ $totalCount !== 1 ? 'es' : '' }}.
            </p>
        </div>
        <div class="mt-3 sm:mt-0">
            <span class="inline-flex items-center gap-1.5 rounded-md bg-sky-50 px-2.5 py-1.5 text-xs font-medium text-sky-700">
                <svg class="size-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0 0 12 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75Z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Unidades de compra del Estado
            </span>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" action="{{ route('inteligencia.instituciones') }}" class="mt-6 rounded-lg border border-gray-200 bg-gray-50 p-4">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div class="sm:col-span-2">
                <label for="q" class="block text-xs font-medium text-gray-700">Buscar</label>
                <input type="text" name="q" id="q" value="{{ request('q') }}" placeholder="Nombre, siglas, código, correo..."
                       class="mt-1 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-blue-600 sm:text-sm">
            </div>
            <div>
                <label for="estado" class="block text-xs font-medium text-gray-700">Estado</label>
                <select name="estado" id="estado"
                        class="mt-1 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-blue-600 sm:text-sm">
                    <option value="">Todos</option>
                    @foreach($statuses as $st)
                        <option value="{{ $st }}" {{ request('estado') == $st ? 'selected' : '' }}>{{ $st }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="mt-4 flex items-center gap-3">
            <button type="submit" class="rounded-md bg-blue-600 px-3 py-1.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-500">Filtrar</button>
            <a href="{{ route('inteligencia.instituciones') }}" class="text-sm text-gray-600 hover:text-gray-900">Limpiar filtros</a>
        </div>
    </form>

    {{-- Table --}}
    <div class="mt-6 rounded-lg border border-gray-200 shadow-sm">
        <div class="table-scroll-x rounded-lg">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        @php
                            $sortDir = request('dir') === 'asc' ? 'desc' : 'asc';
                            $currentSort = request('sort', 'name');
                            $currentDir = request('dir', 'asc');
                        @endphp
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'name', 'dir' => $currentSort === 'name' ? $sortDir : 'asc']) }}"
                               class="group inline-flex items-center gap-1 hover:text-gray-700">
                                Nombre
                                @if($currentSort === 'name')
                                    <svg class="size-3" fill="currentColor" viewBox="0 0 20 20">
                                        @if($currentDir === 'asc')
                                            <path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd"/>
                                        @else
                                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                        @endif
                                    </svg>
                                @endif
                            </a>
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'acronym', 'dir' => $currentSort === 'acronym' ? $sortDir : 'asc']) }}"
                               class="group inline-flex items-center gap-1 hover:text-gray-700">
                                Siglas
                                @if($currentSort === 'acronym')
                                    <svg class="size-3" fill="currentColor" viewBox="0 0 20 20">
                                        @if($currentDir === 'asc')
                                            <path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd"/>
                                        @else
                                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                        @endif
                                    </svg>
                                @endif
                            </a>
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'code', 'dir' => $currentSort === 'code' ? $sortDir : 'asc']) }}"
                               class="group inline-flex items-center gap-1 hover:text-gray-700">
                                Código
                                @if($currentSort === 'code')
                                    <svg class="size-3" fill="currentColor" viewBox="0 0 20 20">
                                        @if($currentDir === 'asc')
                                            <path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd"/>
                                        @else
                                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                        @endif
                                    </svg>
                                @endif
                            </a>
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Contacto</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Dirección</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'status', 'dir' => $currentSort === 'status' ? $sortDir : 'asc']) }}"
                               class="group inline-flex items-center gap-1 hover:text-gray-700">
                                Estado
                                @if($currentSort === 'status')
                                    <svg class="size-3" fill="currentColor" viewBox="0 0 20 20">
                                        @if($currentDir === 'asc')
                                            <path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd"/>
                                        @else
                                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                        @endif
                                    </svg>
                                @endif
                            </a>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @forelse($institutions as $inst)
                        <tr class="hover:bg-gray-50">
                            <td class="max-w-xs px-4 py-3 text-sm text-gray-900">
                                <div class="font-medium" title="{{ $inst->name }}">{{ \Illuminate\Support\Str::limit($inst->name, 50) }}</div>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-gray-700">
                                {{ $inst->acronym ?: '—' }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm">
                                <span class="font-mono text-xs text-sky-600">{{ $inst->code }}</span>
                            </td>
                            @php
                                $validEmail = $inst->email && !str_contains(strtoupper($inst->email), 'CORREOINVALIDO');
                                $validNotifEmail = $inst->notification_email && $inst->notification_email !== $inst->email && !str_contains(strtoupper($inst->notification_email), 'CORREOINVALIDO');
                                $validPhone = $inst->phone && !preg_match('/^\(0{3}\)0{3}-0{4}$/', $inst->phone);
                            @endphp
                            <td class="max-w-[200px] px-4 py-3 text-sm text-gray-500">
                                @if($validEmail)
                                    <div class="truncate text-xs" title="{{ $inst->email }}">{{ $inst->email }}</div>
                                @endif
                                @if($validNotifEmail)
                                    <div class="truncate text-xs text-gray-400" title="{{ $inst->notification_email }}">{{ $inst->notification_email }}</div>
                                @endif
                                @if($validPhone)
                                    <div class="text-xs text-gray-400">{{ $inst->phone }}</div>
                                @endif
                                @if(!$validEmail && !$validPhone)
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="max-w-[200px] px-4 py-3 text-sm text-gray-500">
                                @if($inst->address)
                                    <div class="truncate text-xs" title="{{ $inst->address }}">{{ \Illuminate\Support\Str::limit($inst->address, 50) }}</div>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm">
                                @if($inst->status)
                                    @php
                                        $statusColor = match(strtolower($inst->status)) {
                                            'activo', 'activa' => 'bg-green-100 text-green-700',
                                            'inactivo', 'inactiva' => 'bg-gray-100 text-gray-700',
                                            default => 'bg-blue-100 text-blue-700',
                                        };
                                    @endphp
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $statusColor }}">{{ $inst->status }}</span>
                                @else
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-sm text-gray-500">
                                @if($totalCount === 0)
                                    <div class="mx-auto max-w-sm">
                                        <svg class="mx-auto size-12 text-gray-300" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24">
                                            <path d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0 0 12 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75Z" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                        <p class="mt-2 font-medium text-gray-900">Sin datos de instituciones</p>
                                        <p class="mt-1 text-gray-500">Ejecuta <code class="rounded bg-gray-100 px-1 py-0.5 text-xs">php artisan secp:sync-institutions</code> para sincronizar unidades de compra desde la API.</p>
                                    </div>
                                @else
                                    No se encontraron instituciones con los filtros seleccionados.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($institutions->hasPages())
        <div class="mt-6">
            {{ $institutions->links('components.pagination') }}
        </div>
    @endif

</div>
@endsection
