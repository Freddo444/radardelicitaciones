@extends('layouts.app')
@section('title', 'Contratos')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">

    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-base font-semibold text-gray-900">Contratos</h1>
            <p class="mt-1 text-sm text-gray-500">
                {{ number_format($contracts->total()) }} contrato{{ $contracts->total() !== 1 ? 's' : '' }} encontrado{{ $contracts->total() !== 1 ? 's' : '' }}
                de {{ number_format($totalCount) }} total{{ $totalCount !== 1 ? 'es' : '' }}.
            </p>
        </div>
        <div class="mt-3 sm:mt-0">
            <span class="inline-flex items-center gap-1.5 rounded-md bg-indigo-50 px-2.5 py-1.5 text-xs font-medium text-indigo-700">
                <svg class="size-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Contratos gubernamentales firmados
            </span>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" action="{{ route('inteligencia.contratos') }}" class="mt-6 rounded-lg border border-gray-200 bg-gray-50 p-4">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            {{-- Search --}}
            <div class="sm:col-span-2">
                <label for="q" class="block text-xs font-medium text-gray-700">Buscar</label>
                <input type="text" name="q" id="q" value="{{ request('q') }}" placeholder="Proveedor, institución, código de proceso o contrato..."
                       class="mt-1 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-blue-600 sm:text-sm">
            </div>

            {{-- Institution --}}
            <div>
                <label for="institucion" class="block text-xs font-medium text-gray-700">Institución</label>
                <select name="institucion" id="institucion"
                        class="mt-1 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-blue-600 sm:text-sm">
                    <option value="">Todas</option>
                    @foreach($institutions as $inst)
                        <option value="{{ $inst->institution_code }}" {{ request('institucion') == $inst->institution_code ? 'selected' : '' }}>
                            {{ \Illuminate\Support\Str::limit($inst->institution_name, 50) }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Provider --}}
            <div>
                <label for="proveedor" class="block text-xs font-medium text-gray-700">Proveedor</label>
                <select name="proveedor" id="proveedor"
                        class="mt-1 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-blue-600 sm:text-sm">
                    <option value="">Todos</option>
                    @foreach($providers as $prov)
                        <option value="{{ $prov->provider_rpe }}" {{ request('proveedor') == $prov->provider_rpe ? 'selected' : '' }}>
                            {{ \Illuminate\Support\Str::limit($prov->provider_name, 50) }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Status --}}
            <div>
                <label for="estado" class="block text-xs font-medium text-gray-700">Estado</label>
                <select name="estado" id="estado"
                        class="mt-1 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-blue-600 sm:text-sm">
                    <option value="">Todos</option>
                    @foreach($statuses as $st)
                        <option value="{{ $st }}" {{ request('estado') == $st ? 'selected' : '' }}>
                            {{ $st }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Amount range --}}
            <div>
                <label for="monto_min" class="block text-xs font-medium text-gray-700">Monto mínimo</label>
                <input type="number" name="monto_min" id="monto_min" value="{{ request('monto_min') }}" step="0.01" placeholder="0.00"
                       class="mt-1 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-blue-600 sm:text-sm">
            </div>
            <div>
                <label for="monto_max" class="block text-xs font-medium text-gray-700">Monto máximo</label>
                <input type="number" name="monto_max" id="monto_max" value="{{ request('monto_max') }}" step="0.01" placeholder="0.00"
                       class="mt-1 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-blue-600 sm:text-sm">
            </div>

            {{-- Date range --}}
            <div>
                <label for="fecha_desde" class="block text-xs font-medium text-gray-700">Fecha contrato desde</label>
                <input type="date" name="fecha_desde" id="fecha_desde" value="{{ request('fecha_desde') }}"
                       class="mt-1 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-blue-600 sm:text-sm">
            </div>
            <div>
                <label for="fecha_hasta" class="block text-xs font-medium text-gray-700">Fecha contrato hasta</label>
                <input type="date" name="fecha_hasta" id="fecha_hasta" value="{{ request('fecha_hasta') }}"
                       class="mt-1 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-blue-600 sm:text-sm">
            </div>
        </div>

        <div class="mt-4 flex items-center gap-3">
            <button type="submit" class="rounded-md bg-blue-600 px-3 py-1.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-500">
                Filtrar
            </button>
            <a href="{{ route('inteligencia.contratos') }}" class="text-sm text-gray-600 hover:text-gray-900">Limpiar filtros</a>
        </div>
    </form>

    {{-- Aggregate Panel --}}
    @if($aggregates && $aggregates['summary']->total_contracts > 0)
        @php $s = $aggregates['summary']; @endphp
        <div class="mt-6 space-y-4">
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
                <div class="rounded-lg border border-gray-200 bg-white px-4 py-3">
                    <dt class="text-xs font-medium text-gray-500">Contratos</dt>
                    <dd class="mt-1 text-lg font-semibold text-gray-900">{{ number_format($s->total_contracts) }}</dd>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white px-4 py-3">
                    <dt class="text-xs font-medium text-gray-500">Monto total</dt>
                    <dd class="mt-1 text-lg font-semibold text-gray-900">{{ $s->sum_amount ? 'RD$'.number_format($s->sum_amount, 2) : '—' }}</dd>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white px-4 py-3">
                    <dt class="text-xs font-medium text-gray-500">Monto prom.</dt>
                    <dd class="mt-1 text-lg font-semibold text-gray-900">{{ $s->avg_amount ? 'RD$'.number_format($s->avg_amount, 2) : '—' }}</dd>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white px-4 py-3">
                    <dt class="text-xs font-medium text-gray-500">Monto mín.</dt>
                    <dd class="mt-1 text-lg font-semibold text-green-700">{{ $s->min_amount ? 'RD$'.number_format($s->min_amount, 2) : '—' }}</dd>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white px-4 py-3">
                    <dt class="text-xs font-medium text-gray-500">Proveedores</dt>
                    <dd class="mt-1 text-lg font-semibold text-gray-900">{{ number_format($s->unique_providers) }}</dd>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white px-4 py-3">
                    <dt class="text-xs font-medium text-gray-500">Instituciones</dt>
                    <dd class="mt-1 text-lg font-semibold text-gray-900">{{ number_format($s->unique_institutions) }}</dd>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                {{-- By status --}}
                @if($aggregates['by_status']->isNotEmpty())
                <div class="rounded-lg border border-gray-200 bg-white p-4">
                    <h3 class="text-sm font-medium text-gray-900">Por estado</h3>
                    <div class="mt-3 space-y-1.5">
                        @php $maxSt = $aggregates['by_status']->max('total') ?: 1; @endphp
                        @foreach($aggregates['by_status'] as $st)
                            <div class="flex items-center gap-3 text-xs">
                                <span class="w-20 shrink-0 truncate text-gray-700">{{ $st->status }}</span>
                                <div class="flex-1 h-4 rounded bg-gray-100 overflow-hidden">
                                    <div class="h-full rounded bg-indigo-500" style="width: {{ ($st->total / $maxSt) * 100 }}%"></div>
                                </div>
                                <span class="w-28 shrink-0 text-right tabular-nums text-gray-700">RD${{ number_format($st->total, 0) }}</span>
                                <span class="w-8 shrink-0 text-right text-gray-400">({{ $st->count }})</span>
                            </div>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Top providers --}}
                @if($aggregates['top_providers']->isNotEmpty())
                <div class="rounded-lg border border-gray-200 bg-white p-4">
                    <h3 class="text-sm font-medium text-gray-900">Principales proveedores</h3>
                    <ul class="mt-3 space-y-2">
                        @foreach($aggregates['top_providers'] as $prov)
                            <li class="flex items-center justify-between text-xs">
                                <div class="min-w-0 flex-1">
                                    <p class="truncate font-medium text-gray-900" title="{{ $prov->provider_name }}">{{ \Illuminate\Support\Str::limit($prov->provider_name, 35) }}</p>
                                    <p class="text-gray-400">{{ $prov->contract_count }} contrato{{ $prov->contract_count !== 1 ? 's' : '' }}</p>
                                </div>
                                <span class="ml-2 shrink-0 tabular-nums font-medium text-gray-700">RD${{ number_format($prov->total_amount, 2) }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
                @endif

                {{-- Top institutions --}}
                @if($aggregates['top_institutions']->isNotEmpty())
                <div class="rounded-lg border border-gray-200 bg-white p-4">
                    <h3 class="text-sm font-medium text-gray-900">Principales instituciones</h3>
                    <ul class="mt-3 space-y-2">
                        @foreach($aggregates['top_institutions'] as $inst)
                            <li class="flex items-center justify-between text-xs">
                                <div class="min-w-0 flex-1">
                                    <p class="truncate font-medium text-gray-900" title="{{ $inst->institution_name }}">{{ \Illuminate\Support\Str::limit($inst->institution_name, 35) }}</p>
                                    <p class="text-gray-400">{{ $inst->contract_count }} contrato{{ $inst->contract_count !== 1 ? 's' : '' }}</p>
                                </div>
                                <span class="ml-2 shrink-0 tabular-nums font-medium text-gray-700">RD${{ number_format($inst->total_amount, 2) }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
                @endif
            </div>
        </div>
    @endif

    {{-- Table --}}
    <div class="mt-6 overflow-hidden rounded-lg border border-gray-200 shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        @php
                            $sortDir = request('dir') === 'asc' ? 'desc' : 'asc';
                            $currentSort = request('sort', 'contract_date');
                            $currentDir = request('dir', 'desc');
                        @endphp
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'contract_code', 'dir' => $currentSort === 'contract_code' ? $sortDir : 'asc']) }}"
                               class="group inline-flex items-center gap-1 hover:text-gray-700">
                                Código
                                @if($currentSort === 'contract_code')
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
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Descripción</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'provider_name', 'dir' => $currentSort === 'provider_name' ? $sortDir : 'asc']) }}"
                               class="group inline-flex items-center gap-1 hover:text-gray-700">
                                Proveedor
                                @if($currentSort === 'provider_name')
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
                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'institution_name', 'dir' => $currentSort === 'institution_name' ? $sortDir : 'asc']) }}"
                               class="group inline-flex items-center gap-1 hover:text-gray-700">
                                Institución
                                @if($currentSort === 'institution_name')
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
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">
                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'amount', 'dir' => $currentSort === 'amount' ? $sortDir : 'desc']) }}"
                               class="group inline-flex items-center gap-1 hover:text-gray-700">
                                Monto
                                @if($currentSort === 'amount')
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
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'contract_date', 'dir' => $currentSort === 'contract_date' ? $sortDir : 'desc']) }}"
                               class="group inline-flex items-center gap-1 hover:text-gray-700">
                                Fecha
                                @if($currentSort === 'contract_date')
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
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Proceso</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @forelse($contracts as $contract)
                        <tr class="hover:bg-gray-50">
                            <td class="whitespace-nowrap px-4 py-3 text-sm">
                                <span class="font-mono text-xs text-indigo-600">{{ \Illuminate\Support\Str::limit($contract->contract_code, 30) }}</span>
                            </td>
                            <td class="max-w-xs px-4 py-3 text-sm text-gray-900">
                                <div class="truncate" title="{{ $contract->description }}">
                                    {{ \Illuminate\Support\Str::limit($contract->description, 60) ?: '—' }}
                                </div>
                                @if($contract->payment_method)
                                    <div class="text-xs text-gray-400">{{ $contract->payment_method }}</div>
                                @endif
                            </td>
                            <td class="max-w-[160px] px-4 py-3 text-sm text-gray-900">
                                <div class="truncate" title="{{ $contract->provider_name }}">{{ \Illuminate\Support\Str::limit($contract->provider_name, 30) }}</div>
                                @if($contract->provider_rpe)
                                    <div class="text-xs text-gray-400">RPE: {{ $contract->provider_rpe }}</div>
                                @endif
                            </td>
                            <td class="max-w-[160px] px-4 py-3 text-sm text-gray-500">
                                <div class="truncate" title="{{ $contract->institution_name }}">{{ \Illuminate\Support\Str::limit($contract->institution_name, 30) }}</div>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-medium tabular-nums text-gray-900">
                                @if($contract->amount > 0)
                                    {{ $contract->currency }} {{ number_format($contract->amount, 2) }}
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm">
                                @if($contract->status)
                                    @php
                                        $statusColor = match(strtolower($contract->status)) {
                                            'activo', 'vigente' => 'bg-green-100 text-green-700',
                                            'cerrado', 'finalizado', 'completado' => 'bg-gray-100 text-gray-700',
                                            'cancelado', 'rescindido' => 'bg-red-100 text-red-700',
                                            default => 'bg-blue-100 text-blue-700',
                                        };
                                    @endphp
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $statusColor }}">{{ $contract->status }}</span>
                                @else
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">
                                {{ $contract->contract_date?->format('d/m/Y') ?? $contract->award_date?->format('d/m/Y') ?? '—' }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm">
                                @if($contract->process_code)
                                    <span class="font-mono text-xs text-blue-600">{{ \Illuminate\Support\Str::limit($contract->process_code, 25) }}</span>
                                @else
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-12 text-center text-sm text-gray-500">
                                @if($totalCount === 0)
                                    <div class="mx-auto max-w-sm">
                                        <svg class="mx-auto size-12 text-gray-300" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24">
                                            <path d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                        <p class="mt-2 font-medium text-gray-900">Sin datos de contratos</p>
                                        <p class="mt-1 text-gray-500">Ejecuta <code class="rounded bg-gray-100 px-1 py-0.5 text-xs">php artisan secp:sync-contracts</code> para sincronizar contratos desde la API.</p>
                                    </div>
                                @else
                                    No se encontraron contratos con los filtros seleccionados.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    @if($contracts->hasPages())
        <div class="mt-6">
            {{ $contracts->links() }}
        </div>
    @endif

</div>
@endsection
