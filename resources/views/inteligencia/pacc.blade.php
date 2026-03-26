@extends('layouts.app')
@section('title', 'PACC — Plan Anual de Compras')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">

    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-base font-semibold text-gray-900">Plan Anual de Compras (PACC)</h1>
            <p class="mt-1 text-sm text-gray-500">
                {{ number_format($acquisitions->total()) }} adquisici{{ $acquisitions->total() !== 1 ? 'ones' : 'ón' }} encontrada{{ $acquisitions->total() !== 1 ? 's' : '' }}
                de {{ number_format($totalCount) }} total{{ $totalCount !== 1 ? 'es' : '' }}.
            </p>
        </div>
        <div class="mt-3 sm:mt-0">
            <span class="inline-flex items-center gap-1.5 rounded-md bg-amber-50 px-2.5 py-1.5 text-xs font-medium text-amber-700">
                <svg class="size-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Compras planificadas por instituciones
            </span>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" action="{{ route('inteligencia.pacc') }}" class="mt-6 rounded-lg border border-gray-200 bg-gray-50 p-4">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            {{-- Search --}}
            <div class="sm:col-span-2">
                <label for="q" class="block text-xs font-medium text-gray-700">Buscar</label>
                <input type="text" name="q" id="q" value="{{ request('q') }}" placeholder="Descripción, institución, propósito..."
                       class="mt-1 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-blue-600 sm:text-sm">
            </div>

            {{-- UNSPSC Familia --}}
            <div>
                <label for="familia" class="block text-xs font-medium text-gray-700">Familia UNSPSC</label>
                <select name="familia" id="familia"
                        class="mt-1 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-blue-600 sm:text-sm">
                    <option value="">Todas</option>
                    @foreach($familias as $f)
                        <option value="{{ $f->unspsc_familia }}" {{ request('familia') == $f->unspsc_familia ? 'selected' : '' }}>
                            {{ $f->unspsc_familia }} — {{ \Illuminate\Support\Str::limit($f->unspsc_description, 40) }}
                        </option>
                    @endforeach
                </select>
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

            {{-- Modality --}}
            <div>
                <label for="modalidad" class="block text-xs font-medium text-gray-700">Modalidad</label>
                <select name="modalidad" id="modalidad"
                        class="mt-1 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-blue-600 sm:text-sm">
                    <option value="">Todas</option>
                    @foreach($modalities as $mod)
                        <option value="{{ $mod }}" {{ request('modalidad') == $mod ? 'selected' : '' }}>
                            {{ $mod }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Object type --}}
            <div>
                <label for="tipo_objeto" class="block text-xs font-medium text-gray-700">Tipo objeto</label>
                <select name="tipo_objeto" id="tipo_objeto"
                        class="mt-1 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-blue-600 sm:text-sm">
                    <option value="">Todos</option>
                    @foreach($objectTypes as $ot)
                        <option value="{{ $ot }}" {{ request('tipo_objeto') == $ot ? 'selected' : '' }}>
                            {{ $ot }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- MIPYMES checkboxes --}}
            <div class="flex items-end gap-4">
                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" name="mipymes" value="1" {{ request('mipymes') === '1' ? 'checked' : '' }}
                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-600">
                    MIPYMES
                </label>
                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" name="mipymes_mujeres" value="1" {{ request('mipymes_mujeres') === '1' ? 'checked' : '' }}
                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-600">
                    Mujeres
                </label>
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
                <label for="fecha_desde" class="block text-xs font-medium text-gray-700">Fecha inicio desde</label>
                <input type="date" name="fecha_desde" id="fecha_desde" value="{{ request('fecha_desde') }}"
                       class="mt-1 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-blue-600 sm:text-sm">
            </div>
            <div>
                <label for="fecha_hasta" class="block text-xs font-medium text-gray-700">Fecha inicio hasta</label>
                <input type="date" name="fecha_hasta" id="fecha_hasta" value="{{ request('fecha_hasta') }}"
                       class="mt-1 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-blue-600 sm:text-sm">
            </div>
        </div>

        <div class="mt-4 flex items-center gap-3">
            <button type="submit" class="rounded-md bg-blue-600 px-3 py-1.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-500">
                Filtrar
            </button>
            <a href="{{ route('inteligencia.pacc') }}" class="text-sm text-gray-600 hover:text-gray-900">Limpiar filtros</a>
        </div>
    </form>

    {{-- Aggregate Panel (shown when filters are active) --}}
    @if($aggregates && $aggregates['summary']->total_acquisitions > 0)
        @php $s = $aggregates['summary']; @endphp
        <div class="mt-6 space-y-4">
            {{-- Summary stats --}}
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
                <div class="rounded-lg border border-gray-200 bg-white px-4 py-3">
                    <dt class="text-xs font-medium text-gray-500">Adquisiciones</dt>
                    <dd class="mt-1 text-lg font-semibold text-gray-900">{{ number_format($s->total_acquisitions) }}</dd>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white px-4 py-3">
                    <dt class="text-xs font-medium text-gray-500">Monto total est.</dt>
                    <dd class="mt-1 text-lg font-semibold text-gray-900">{{ $s->sum_estimated ? 'RD$'.number_format($s->sum_estimated, 2) : '—' }}</dd>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white px-4 py-3">
                    <dt class="text-xs font-medium text-gray-500">Monto prom.</dt>
                    <dd class="mt-1 text-lg font-semibold text-gray-900">{{ $s->avg_estimated ? 'RD$'.number_format($s->avg_estimated, 2) : '—' }}</dd>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white px-4 py-3">
                    <dt class="text-xs font-medium text-gray-500">Monto mín.</dt>
                    <dd class="mt-1 text-lg font-semibold text-green-700">{{ $s->min_estimated ? 'RD$'.number_format($s->min_estimated, 2) : '—' }}</dd>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white px-4 py-3">
                    <dt class="text-xs font-medium text-gray-500">Instituciones</dt>
                    <dd class="mt-1 text-lg font-semibold text-gray-900">{{ number_format($s->unique_institutions) }}</dd>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white px-4 py-3">
                    <dt class="text-xs font-medium text-gray-500">MIPYMES</dt>
                    <dd class="mt-1 text-lg font-semibold text-purple-700">{{ number_format($s->mipymes_count) }}</dd>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                {{-- By modality --}}
                @if($aggregates['by_modality']->isNotEmpty())
                <div class="rounded-lg border border-gray-200 bg-white p-4">
                    <h3 class="text-sm font-medium text-gray-900">Por modalidad</h3>
                    <div class="mt-3 space-y-1.5">
                        @php $maxMod = $aggregates['by_modality']->max('total') ?: 1; @endphp
                        @foreach($aggregates['by_modality'] as $mod)
                            <div class="flex items-center gap-3 text-xs">
                                <span class="w-32 shrink-0 truncate text-gray-700" title="{{ $mod->modality }}">{{ \Illuminate\Support\Str::limit($mod->modality, 25) }}</span>
                                <div class="flex-1 h-4 rounded bg-gray-100 overflow-hidden">
                                    <div class="h-full rounded bg-amber-500" style="width: {{ ($mod->total / $maxMod) * 100 }}%"></div>
                                </div>
                                <span class="w-28 shrink-0 text-right tabular-nums text-gray-700">RD${{ number_format($mod->total, 0) }}</span>
                                <span class="w-8 shrink-0 text-right text-gray-400">({{ $mod->count }})</span>
                            </div>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Top institutions --}}
                @if($aggregates['by_institution']->isNotEmpty())
                <div class="rounded-lg border border-gray-200 bg-white p-4">
                    <h3 class="text-sm font-medium text-gray-900">Principales instituciones</h3>
                    <ul class="mt-3 space-y-2">
                        @foreach($aggregates['by_institution'] as $inst)
                            <li class="flex items-center justify-between text-xs">
                                <div class="min-w-0 flex-1">
                                    <p class="truncate font-medium text-gray-900" title="{{ $inst->institution_name }}">{{ \Illuminate\Support\Str::limit($inst->institution_name, 40) }}</p>
                                    <p class="text-gray-400">{{ $inst->count }} adquisici{{ $inst->count !== 1 ? 'ones' : 'ón' }}</p>
                                </div>
                                <span class="ml-2 shrink-0 tabular-nums font-medium text-gray-700">RD${{ number_format($inst->total, 2) }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
                @endif
            </div>
        </div>
    @endif

    {{-- Table --}}
    <div class="mt-6 rounded-lg border border-gray-200 shadow-sm">
        <div class="table-scroll-x rounded-lg">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        @php
                            $sortDir = request('dir') === 'asc' ? 'desc' : 'asc';
                            $currentSort = request('sort', 'start_date');
                            $currentDir = request('dir', 'desc');
                        @endphp
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'description', 'dir' => $currentSort === 'description' ? $sortDir : 'asc']) }}"
                               class="group inline-flex items-center gap-1 hover:text-gray-700">
                                Descripción
                                @if($currentSort === 'description')
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
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'modality', 'dir' => $currentSort === 'modality' ? $sortDir : 'asc']) }}"
                               class="group inline-flex items-center gap-1 hover:text-gray-700">
                                Modalidad
                                @if($currentSort === 'modality')
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
                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'object_type', 'dir' => $currentSort === 'object_type' ? $sortDir : 'asc']) }}"
                               class="group inline-flex items-center gap-1 hover:text-gray-700">
                                Objeto
                                @if($currentSort === 'object_type')
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
                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'estimated_amount', 'dir' => $currentSort === 'estimated_amount' ? $sortDir : 'desc']) }}"
                               class="group inline-flex items-center gap-1 hover:text-gray-700">
                                Monto est.
                                @if($currentSort === 'estimated_amount')
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
                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'start_date', 'dir' => $currentSort === 'start_date' ? $sortDir : 'desc']) }}"
                               class="group inline-flex items-center gap-1 hover:text-gray-700">
                                Inicio est.
                                @if($currentSort === 'start_date')
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
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">UNSPSC</th>
                        <th class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500">Badges</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @forelse($acquisitions as $acq)
                        @php
                            $matchesRubro = false;
                            if (!empty($matchedRubroCodes)) {
                                foreach ($matchedRubroCodes as $level => $codes) {
                                    $field = 'unspsc_' . $level;
                                    if ($acq->$field && in_array($acq->$field, $codes)) {
                                        $matchesRubro = true;
                                        break;
                                    }
                                }
                            }
                        @endphp
                        <tr class="{{ $matchesRubro ? 'bg-green-50/50' : '' }} hover:bg-gray-50">
                            <td class="max-w-xs px-4 py-3 text-sm text-gray-900">
                                <div class="font-medium" title="{{ $acq->description }}">
                                    {{ \Illuminate\Support\Str::limit($acq->description, 80) }}
                                </div>
                                @if($acq->purpose)
                                    <div class="mt-0.5 text-xs text-gray-500 truncate" title="{{ $acq->purpose }}">{{ \Illuminate\Support\Str::limit($acq->purpose, 60) }}</div>
                                @endif
                            </td>
                            <td class="max-w-[180px] px-4 py-3 text-sm text-gray-500">
                                <div class="truncate" title="{{ $acq->institution_name }}">{{ \Illuminate\Support\Str::limit($acq->institution_name, 35) }}</div>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">
                                {{ \Illuminate\Support\Str::limit($acq->modality, 20) }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">
                                {{ $acq->object_type ?? '—' }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-medium tabular-nums text-gray-900">
                                @if($acq->estimated_amount > 0)
                                    {{ $acq->currency }} {{ number_format($acq->estimated_amount, 2) }}
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">
                                @if($acq->start_date)
                                    {{ $acq->start_date->format('d/m/Y') }}
                                    @if($acq->start_date->year > now()->year + 5 || $acq->start_date->year < 2015)
                                        <span class="ml-1 text-xs text-red-400">Fecha inválida</span>
                                    @elseif($acq->start_date->isFuture())
                                        <span class="ml-1 text-xs text-amber-600">{{ $acq->start_date->diffForHumans() }}</span>
                                    @endif
                                @else
                                    —
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">
                                @if($acq->unspsc_subclase)
                                    <span class="inline-flex rounded bg-gray-100 px-1.5 py-0.5 text-xs font-mono text-gray-600">{{ $acq->unspsc_subclase }}</span>
                                @elseif($acq->unspsc_clase)
                                    <span class="inline-flex rounded bg-gray-100 px-1.5 py-0.5 text-xs font-mono text-gray-600">{{ $acq->unspsc_clase }}</span>
                                @elseif($acq->unspsc_familia)
                                    <span class="inline-flex rounded bg-gray-100 px-1.5 py-0.5 text-xs font-mono text-gray-600">{{ $acq->unspsc_familia }}</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-center text-xs">
                                <div class="flex items-center justify-center gap-1">
                                    @if($matchesRubro)
                                        <span class="inline-flex rounded-full bg-green-100 px-2 py-0.5 text-[10px] font-medium text-green-700">Tu rubro</span>
                                    @endif
                                    @if($acq->mipymes)
                                        <span class="inline-flex rounded-full bg-purple-100 px-2 py-0.5 text-[10px] font-medium text-purple-700">MIPYMES</span>
                                    @endif
                                    @if($acq->mipymes_mujeres)
                                        <span class="inline-flex rounded-full bg-pink-100 px-2 py-0.5 text-[10px] font-medium text-pink-700">Mujeres</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-12 text-center text-sm text-gray-500">
                                @if($totalCount === 0)
                                    <div class="mx-auto max-w-sm">
                                        <svg class="mx-auto size-12 text-gray-300" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24">
                                            <path d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                        <p class="mt-2 font-medium text-gray-900">Sin datos PACC</p>
                                        <p class="mt-1 text-gray-500">Ejecuta <code class="rounded bg-gray-100 px-1 py-0.5 text-xs">php artisan secp:sync-pacc</code> para sincronizar planes anuales de compras desde la API.</p>
                                    </div>
                                @else
                                    No se encontraron adquisiciones con los filtros seleccionados.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    @if($acquisitions->hasPages())
        <div class="mt-6">
            {{ $acquisitions->links('components.pagination') }}
        </div>
    @endif

</div>
@endsection
