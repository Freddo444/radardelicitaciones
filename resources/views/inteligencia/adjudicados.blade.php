@extends('layouts.app')
@section('title', 'Artículos Adjudicados')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">

    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-base font-semibold text-gray-900">Artículos Adjudicados</h1>
            <p class="mt-1 text-sm text-gray-500">
                {{ number_format($totalCount) }} artículo{{ $totalCount !== 1 ? 's' : '' }} en total.
            </p>
        </div>
        <div class="mt-3 sm:mt-0">
            <span class="inline-flex items-center gap-1.5 rounded-md bg-blue-50 px-2.5 py-1.5 text-xs font-medium text-blue-700">
                <svg class="size-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Precios históricos de contratos adjudicados
            </span>
        </div>
    </div>

    @php
        $adjFamiliaOptions = [['value' => '', 'label' => 'Todas']];
        foreach ($familias as $f) {
            $adjFamiliaOptions[] = [
                'value' => $f->unspsc_familia,
                'label' => $f->unspsc_familia.' — '.\Illuminate\Support\Str::limit($f->unspsc_description, 40),
            ];
        }
        $adjInstitucionOptions = [['value' => '', 'label' => 'Todas']];
        foreach ($institutions as $inst) {
            $adjInstitucionOptions[] = [
                'value' => $inst->institution_code,
                'label' => \Illuminate\Support\Str::limit($inst->institution_name, 50),
            ];
        }
    @endphp
    {{-- Filters --}}
    <form id="adjudicados-filters" method="GET" action="{{ route('inteligencia.adjudicados') }}" class="mt-6 rounded-lg border border-gray-200 bg-gray-50 p-4">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            {{-- Search --}}
            <div class="sm:col-span-2">
                <label for="q" class="block text-xs font-medium text-gray-700">Buscar</label>
                <input type="text" name="q" id="q" value="{{ request('q') }}" placeholder="Descripción, proveedor, institución, código..."
                       class="mt-1 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-blue-600 sm:text-sm">
            </div>

            {{-- UNSPSC Familia --}}
            <div>
                <x-filter-dropdown form="adjudicados-filters" name="familia" label="Familia UNSPSC" :options="$adjFamiliaOptions" :button-label-max="48" />
            </div>

            {{-- Institution --}}
            <div>
                <x-filter-dropdown form="adjudicados-filters" name="institucion" label="Institución" :options="$adjInstitucionOptions" :button-label-max="48" />
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
                <label for="fecha_desde" class="block text-xs font-medium text-gray-700">Fecha desde</label>
                <input type="date" name="fecha_desde" id="fecha_desde" value="{{ request('fecha_desde') }}"
                       class="mt-1 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-blue-600 sm:text-sm">
            </div>
            <div>
                <label for="fecha_hasta" class="block text-xs font-medium text-gray-700">Fecha hasta</label>
                <input type="date" name="fecha_hasta" id="fecha_hasta" value="{{ request('fecha_hasta') }}"
                       class="mt-1 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-blue-600 sm:text-sm">
            </div>
        </div>

        <div class="mt-4 flex items-center gap-3">
            <button type="submit" class="rounded-md bg-blue-600 px-3 py-1.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-500">
                Filtrar
            </button>
            <a href="{{ route('inteligencia.adjudicados') }}" class="text-sm text-gray-600 hover:text-gray-900">Limpiar filtros</a>
        </div>
    </form>

    {{-- Aggregate Panel (shown when filters are active) --}}
    @if($aggregates && $aggregates['summary']->total_articles > 0)
        @php $s = $aggregates['summary']; @endphp
        <div class="mt-6 space-y-4">
            {{-- Summary stats --}}
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4 lg:grid-cols-6">
                <div class="rounded-lg border border-gray-200 bg-white px-4 py-3">
                    <dt class="text-xs font-medium text-gray-500">Artículos</dt>
                    <dd class="mt-1 text-lg font-semibold text-gray-900">{{ number_format($s->total_articles) }}</dd>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white px-4 py-3">
                    <dt class="text-xs font-medium text-gray-500">Precio prom.</dt>
                    <dd class="mt-1 text-lg font-semibold text-gray-900">{{ $s->avg_unit_price ? 'RD$'.number_format($s->avg_unit_price, 2) : '—' }}</dd>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white px-4 py-3">
                    <dt class="text-xs font-medium text-gray-500">Precio mín.</dt>
                    <dd class="mt-1 text-lg font-semibold text-green-700">{{ $s->min_unit_price ? 'RD$'.number_format($s->min_unit_price, 2) : '—' }}</dd>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white px-4 py-3">
                    <dt class="text-xs font-medium text-gray-500">Precio máx.</dt>
                    <dd class="mt-1 text-lg font-semibold text-red-700">{{ $s->max_unit_price ? 'RD$'.number_format($s->max_unit_price, 2) : '—' }}</dd>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white px-4 py-3">
                    <dt class="text-xs font-medium text-gray-500">Proveedores</dt>
                    <dd class="mt-1 text-lg font-semibold text-gray-900">{{ number_format($s->unique_providers) }}</dd>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white px-4 py-3">
                    <dt class="text-xs font-medium text-gray-500">Monto total</dt>
                    <dd class="mt-1 text-lg font-semibold text-gray-900">{{ $s->sum_total ? 'RD$'.number_format($s->sum_total, 2) : '—' }}</dd>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                {{-- Price trend by month --}}
                @if($aggregates['price_trend']->isNotEmpty())
                <div class="rounded-lg border border-gray-200 bg-white p-4">
                    <h3 class="text-sm font-medium text-gray-900">Tendencia de precio unitario</h3>
                    <div class="mt-3 space-y-1.5">
                        @php
                            $maxAvg = $aggregates['price_trend']->max('avg_price') ?: 1;
                        @endphp
                        @foreach($aggregates['price_trend'] as $point)
                            <div class="flex items-center gap-3 text-xs">
                                <span class="w-14 shrink-0 font-mono text-gray-500">{{ $point->month }}</span>
                                <div class="flex-1 h-4 rounded bg-gray-100 overflow-hidden">
                                    <div class="h-full rounded bg-blue-500" style="width: {{ ($point->avg_price / $maxAvg) * 100 }}%"></div>
                                </div>
                                <span class="w-24 shrink-0 text-right tabular-nums text-gray-700">RD${{ number_format($point->avg_price, 2) }}</span>
                                <span class="w-8 shrink-0 text-right text-gray-400">({{ $point->article_count }})</span>
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
                                    <p class="text-gray-400">{{ $prov->article_count }} artículo{{ $prov->article_count !== 1 ? 's' : '' }}</p>
                                </div>
                                <span class="ml-2 shrink-0 tabular-nums font-medium text-gray-700">RD${{ number_format($prov->awarded_total, 2) }}</span>
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
                                    <p class="text-gray-400">{{ $inst->article_count }} artículo{{ $inst->article_count !== 1 ? 's' : '' }}</p>
                                </div>
                                <span class="ml-2 shrink-0 tabular-nums font-medium text-gray-700">RD${{ number_format($inst->awarded_total, 2) }}</span>
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
                            $currentSort = request('sort', 'award_date');
                            $currentDir = request('dir', 'desc');
                        @endphp
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'description', 'dir' => $currentSort === 'description' ? $sortDir : 'asc']) }}"
                               class="group inline-flex items-center gap-1 hover:text-gray-700">
                                Artículo
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
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">UNSPSC</th>
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
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Cant.</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">
                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'unit_price', 'dir' => $currentSort === 'unit_price' ? $sortDir : 'desc']) }}"
                               class="group inline-flex items-center gap-1 hover:text-gray-700">
                                P. Unit.
                                @if($currentSort === 'unit_price')
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
                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'total', 'dir' => $currentSort === 'total' ? $sortDir : 'desc']) }}"
                               class="group inline-flex items-center gap-1 hover:text-gray-700">
                                Total
                                @if($currentSort === 'total')
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
                            <a href="{{ request()->fullUrlWithQuery(['sort' => 'award_date', 'dir' => $currentSort === 'award_date' ? $sortDir : 'desc']) }}"
                               class="group inline-flex items-center gap-1 hover:text-gray-700">
                                Fecha
                                @if($currentSort === 'award_date')
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
                    @forelse($articles as $article)
                        <tr class="hover:bg-gray-50">
                            <td class="max-w-xs px-4 py-3 text-sm text-gray-900">
                                <div class="truncate font-medium" title="{{ $article->description }}">
                                    {{ \Illuminate\Support\Str::limit($article->description, 60) }}
                                </div>
                                @if($article->unit_measure)
                                    <div class="text-xs text-gray-500">{{ $article->unit_measure }}</div>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">
                                @if($article->unspsc_subclase)
                                    <span class="inline-flex rounded bg-gray-100 px-1.5 py-0.5 text-xs font-mono text-gray-600">{{ $article->unspsc_subclase }}</span>
                                @elseif($article->unspsc_clase)
                                    <span class="inline-flex rounded bg-gray-100 px-1.5 py-0.5 text-xs font-mono text-gray-600">{{ $article->unspsc_clase }}</span>
                                @elseif($article->unspsc_familia)
                                    <span class="inline-flex rounded bg-gray-100 px-1.5 py-0.5 text-xs font-mono text-gray-600">{{ $article->unspsc_familia }}</span>
                                @endif
                                @if($article->unspsc_description)
                                    <div class="mt-0.5 max-w-[120px] truncate text-xs text-gray-400" title="{{ $article->unspsc_description }}">{{ $article->unspsc_description }}</div>
                                @endif
                            </td>
                            <td class="max-w-[160px] px-4 py-3 text-sm text-gray-900">
                                <div class="truncate" title="{{ $article->provider_name }}">{{ \Illuminate\Support\Str::limit($article->provider_name, 30) }}</div>
                                @if($article->provider_rpe)
                                    <div class="text-xs text-gray-400">RPE: {{ $article->provider_rpe }}</div>
                                @endif
                            </td>
                            <td class="max-w-[160px] px-4 py-3 text-sm text-gray-500">
                                <div class="truncate" title="{{ $article->institution_name }}">{{ \Illuminate\Support\Str::limit($article->institution_name, 30) }}</div>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm tabular-nums text-gray-900">
                                {{ number_format($article->quantity, $article->quantity == intval($article->quantity) ? 0 : 2) }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm tabular-nums text-gray-900">
                                {{ $article->currency }} {{ number_format($article->unit_price, 2) }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-medium tabular-nums text-gray-900">
                                {{ $article->currency }} {{ number_format($article->total, 2) }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">
                                {{ $article->award_date?->format('d/m/Y') ?? '—' }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm">
                                @if($article->process_code)
                                    <span class="font-mono text-xs text-blue-600" title="{{ $article->contract_code }}">{{ \Illuminate\Support\Str::limit($article->process_code, 25) }}</span>
                                @else
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-12 text-center text-sm text-gray-500">
                                @if($totalCount === 0)
                                    <div class="mx-auto max-w-sm">
                                        <svg class="mx-auto size-12 text-gray-300" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24">
                                            <path d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                        <p class="mt-2 font-medium text-gray-900">Sin datos de adjudicaciones</p>
                                        <p class="mt-1 text-gray-500">Ejecuta <code class="rounded bg-gray-100 px-1 py-0.5 text-xs">php artisan secp:sync-contracts</code> para sincronizar artículos adjudicados desde la API.</p>
                                    </div>
                                @else
                                    No se encontraron artículos con los filtros seleccionados.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    @if($articles->hasPages())
        <div class="mt-6">
            {{ $articles->links('components.pagination') }}
        </div>
    @endif

</div>
@endsection
