@extends('layouts.app')
@section('title', 'Proveedores')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">

    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-base font-semibold text-gray-900">Directorio de Proveedores</h1>
            <p class="mt-1 text-sm text-gray-500">
                {{ number_format($totalCount) }} proveedor{{ $totalCount !== 1 ? 'es' : '' }} en total.
            </p>
        </div>
        <div class="mt-3 sm:mt-0">
            <span class="inline-flex items-center gap-1.5 rounded-md bg-green-50 px-2.5 py-1.5 text-xs font-medium text-green-700">
                <svg class="size-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Proveedores registrados del Estado
            </span>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" action="{{ route('inteligencia.proveedores') }}" class="mt-6 rounded-lg border border-gray-200 bg-gray-50 p-4">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
            {{-- Search --}}
            <div class="sm:col-span-2">
                <label for="q" class="block text-xs font-medium text-gray-700">Buscar</label>
                <input type="text" name="q" id="q" value="{{ request('q') }}" placeholder="Razón social, RNC, RPE, correo..."
                       class="mt-1 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-blue-600 sm:text-sm">
            </div>

            {{-- Status --}}
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

            {{-- Tipo persona --}}
            <div>
                <label for="tipo_persona" class="block text-xs font-medium text-gray-700">Tipo persona</label>
                <select name="tipo_persona" id="tipo_persona"
                        class="mt-1 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-blue-600 sm:text-sm">
                    <option value="">Todos</option>
                    @foreach($tiposPersona as $tp)
                        <option value="{{ $tp }}" {{ request('tipo_persona') == $tp ? 'selected' : '' }}>{{ $tp }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Province --}}
            <div>
                <label for="provincia" class="block text-xs font-medium text-gray-700">Provincia</label>
                <select name="provincia" id="provincia"
                        class="mt-1 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-blue-600 sm:text-sm">
                    <option value="">Todas</option>
                    @foreach($provinces as $prov)
                        <option value="{{ $prov }}" {{ request('provincia') == $prov ? 'selected' : '' }}>{{ $prov }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="mt-4 flex items-center gap-4">
            <label class="flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" name="mipyme" value="1" {{ request('mipyme') === '1' ? 'checked' : '' }}
                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-600">
                Solo MIPYMES
            </label>
            <button type="submit" class="rounded-md bg-blue-600 px-3 py-1.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-500">
                Filtrar
            </button>
            <a href="{{ route('inteligencia.proveedores') }}" class="text-sm text-gray-600 hover:text-gray-900">Limpiar filtros</a>
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
                            $currentSort = request('sort', 'razon_social');
                            $currentDir = request('dir', 'asc');
                        @endphp
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'razon_social', 'dir' => $currentSort === 'razon_social' ? $sortDir : 'asc']) }}"
                               class="group inline-flex items-center gap-1 hover:text-gray-700">
                                Razón social
                                @if($currentSort === 'razon_social')
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
                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'rpe', 'dir' => $currentSort === 'rpe' ? $sortDir : 'asc']) }}"
                               class="group inline-flex items-center gap-1 hover:text-gray-700">
                                RPE
                                @if($currentSort === 'rpe')
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
                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'rnc', 'dir' => $currentSort === 'rnc' ? $sortDir : 'asc']) }}"
                               class="group inline-flex items-center gap-1 hover:text-gray-700">
                                RNC
                                @if($currentSort === 'rnc')
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
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'province', 'dir' => $currentSort === 'province' ? $sortDir : 'asc']) }}"
                               class="group inline-flex items-center gap-1 hover:text-gray-700">
                                Ubicación
                                @if($currentSort === 'province')
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
                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'tipo_persona', 'dir' => $currentSort === 'tipo_persona' ? $sortDir : 'asc']) }}"
                               class="group inline-flex items-center gap-1 hover:text-gray-700">
                                Tipo
                                @if($currentSort === 'tipo_persona')
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
                        @if($providers->contains(fn($p) => $p->is_mipyme))
                        <th class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500">MIPYME</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @forelse($providers as $prov)
                        <tr class="hover:bg-gray-50">
                            <td class="max-w-[200px] px-4 py-3 text-sm text-gray-900">
                                <div class="truncate font-medium" title="{{ $prov->razon_social }}">{{ \Illuminate\Support\Str::limit($prov->razon_social, 40) }}</div>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm">
                                <span class="font-mono text-xs text-blue-600">{{ $prov->rpe }}</span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">
                                {{ $prov->rnc ?: '—' }}
                            </td>
                            <td class="max-w-[180px] px-4 py-3 text-sm text-gray-500">
                                @if($prov->email)
                                    <div class="truncate text-xs" title="{{ $prov->email }}">{{ $prov->email }}</div>
                                @endif
                                @if($prov->phone)
                                    <div class="text-xs text-gray-400">{{ $prov->phone }}</div>
                                @endif
                                @if($prov->contact_name)
                                    <div class="text-xs text-gray-400">{{ \Illuminate\Support\Str::limit($prov->contact_name, 30) }}</div>
                                @endif
                                @if(!$prov->email && !$prov->phone && !$prov->contact_name)
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">
                                @if($prov->province)
                                    {{ $prov->province }}
                                    @if($prov->municipality)
                                        <div class="text-xs text-gray-400">{{ $prov->municipality }}</div>
                                    @endif
                                @else
                                    —
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">
                                {{ $prov->tipo_persona ?: '—' }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm">
                                @if($prov->status)
                                    @php
                                        $statusColor = match(strtolower($prov->status)) {
                                            'activo', 'registrado' => 'bg-green-100 text-green-700',
                                            'suspendido' => 'bg-yellow-100 text-yellow-700',
                                            'cancelado', 'inhabilitado' => 'bg-red-100 text-red-700',
                                            default => 'bg-gray-100 text-gray-700',
                                        };
                                    @endphp
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $statusColor }}">{{ $prov->status }}</span>
                                @else
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </td>
                            @if($providers->contains(fn($p) => $p->is_mipyme))
                            <td class="whitespace-nowrap px-4 py-3 text-center text-xs">
                                @if($prov->is_mipyme)
                                    <span class="inline-flex rounded-full bg-purple-100 px-2 py-0.5 text-[10px] font-medium text-purple-700">MIPYME</span>
                                @endif
                            </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="99" class="px-4 py-12 text-center text-sm text-gray-500">
                                @if($totalCount === 0)
                                    <div class="mx-auto max-w-sm">
                                        <svg class="mx-auto size-12 text-gray-300" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24">
                                            <path d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                        <p class="mt-2 font-medium text-gray-900">Sin datos de proveedores</p>
                                        <p class="mt-1 text-gray-500">Ejecuta <code class="rounded bg-gray-100 px-1 py-0.5 text-xs">php artisan secp:sync-providers</code> para sincronizar proveedores desde la API.</p>
                                    </div>
                                @else
                                    No se encontraron proveedores con los filtros seleccionados.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    @if($providers->hasPages())
        <div class="mt-6">
            {{ $providers->links('components.pagination') }}
        </div>
    @endif

</div>
@endsection
