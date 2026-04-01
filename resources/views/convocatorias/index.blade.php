@extends('layouts.app')
@section('title', 'Convocatorias')

@section('content')
<div x-data="convocatorias()" class="mx-auto max-w-7xl px-4 py-6 sm:px-6 sm:py-10 lg:px-8">

    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-base font-semibold text-gray-900">Convocatorias</h1>
            <p class="mt-1 text-sm text-gray-500">{{ number_format($bids->total()) }} convocatoria{{ $bids->total() !== 1 ? 's' : '' }} registrada{{ $bids->total() !== 1 ? 's' : '' }}.</p>
        </div>
    </div>

    {{-- Tabs: Todas | Recomendadas | Guardadas --}}
    <div class="mt-6 border-b border-gray-200">
        <nav class="-mb-px flex space-x-4 sm:space-x-8" aria-label="Tabs">
            <a href="{{ route('convocatorias.index', array_merge(request()->except('tab', 'page'), [])) }}"
               class="border-b-2 px-1 py-4 text-sm font-medium whitespace-nowrap {{ !request('tab') ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}">
                Todas
            </a>
            <a href="{{ route('convocatorias.index', array_merge(request()->except('page'), ['tab' => 'recomendadas'])) }}"
               class="border-b-2 px-1 py-4 text-sm font-medium whitespace-nowrap {{ request('tab') === 'recomendadas' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}">
                Recomendadas @if($relevantCount > 0)<span class="ml-1 rounded-full bg-green-100 px-2 py-0.5 text-xs text-green-600">{{ $relevantCount }}</span>@endif
            </a>
            <a href="{{ route('convocatorias.index', array_merge(request()->except('page'), ['tab' => 'guardadas'])) }}"
               class="border-b-2 px-1 py-4 text-sm font-medium whitespace-nowrap {{ request('tab') === 'guardadas' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}">
                Guardadas @if($bookmarkCount > 0)<span class="ml-1 rounded-full bg-blue-100 px-2 py-0.5 text-xs text-blue-600">{{ $bookmarkCount }}</span>@endif
            </a>
        </nav>
    </div>

    {{-- Filters --}}
    <form method="GET" action="{{ route('convocatorias.index') }}" class="mt-4 flex flex-wrap items-end gap-3 sm:gap-4">
        @if(request('tab'))
            <input type="hidden" name="tab" value="{{ request('tab') }}">
        @endif
        <div class="w-full sm:flex-1 sm:min-w-[200px] sm:max-w-sm">
            <label for="q" class="block text-xs font-medium text-gray-700">Buscar</label>
            <input type="text" name="q" id="q" value="{{ request('q') }}" placeholder="Título, entidad o código..."
                   class="mt-1 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-blue-600 sm:text-sm">
        </div>
        <div>
            <label for="estado" class="block text-xs font-medium text-gray-700">Estado</label>
            <select name="estado" id="estado" onchange="this.form.submit()"
                    class="mt-1 block rounded-md border-0 py-1.5 pl-3 pr-8 text-gray-900 ring-1 ring-inset ring-gray-300 text-sm focus:ring-2 focus:ring-blue-600">
                <option value="">Todos</option>
                @foreach($statuses as $s)
                    <option value="{{ $s }}" @selected(request('estado') === $s)>{{ $s }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="modalidad" class="block text-xs font-medium text-gray-700">Modalidad</label>
            <select name="modalidad" id="modalidad" onchange="this.form.submit()"
                    class="mt-1 block rounded-md border-0 py-1.5 pl-3 pr-8 text-gray-900 ring-1 ring-inset ring-gray-300 text-sm focus:ring-2 focus:ring-blue-600">
                <option value="">Todas</option>
                @foreach($methods as $m)
                    <option value="{{ $m }}" @selected(request('modalidad') === $m)>{{ $m }}</option>
                @endforeach
            </select>
        </div>
        <label class="flex items-center gap-x-2 text-sm text-gray-700">
            <input type="checkbox" name="vigentes" value="1" onchange="this.form.submit()"
                   @checked(request()->boolean('vigentes'))
                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-600">
            Solo vigentes
        </label>
        <button type="submit"
                class="rounded-md bg-blue-600 px-3 py-1.5 text-sm font-semibold text-white shadow-xs hover:bg-blue-500">
            Buscar
        </button>
        @if(request()->hasAny(['q', 'estado', 'modalidad', 'vigentes']))
            <a href="{{ route('convocatorias.index', request('tab') ? ['tab' => request('tab')] : []) }}" class="text-sm text-blue-600 hover:text-blue-500">Limpiar</a>
        @endif
    </form>

    @if(session('success'))
        <div class="mt-4 rounded-md bg-green-50 p-4 text-sm text-green-800 ring-1 ring-inset ring-green-200">
            {{ session('success') }}
        </div>
    @endif

    <div class="mt-6 flow-root">
        @if($bids->isEmpty())
            <div class="text-center py-16">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" class="mx-auto size-12 text-gray-400">
                    <path d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m6.75 12H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <h3 class="mt-2 text-sm font-semibold text-gray-900">Sin convocatorias</h3>
                <p class="mt-1 text-sm text-gray-500">
                    @if(request('tab') === 'recomendadas')
                        No se encontraron convocatorias que coincidan con tus palabras clave o rubros activos.
                    @elseif(request('tab') === 'guardadas')
                        No tienes convocatorias guardadas. Marca alguna con la estrella para verla aquí.
                    @else
                        Ejecuta el sondeo desde el dashboard para comenzar.
                    @endif
                </p>
            </div>
        @else

            {{-- ── Mobile card list (below md) ──────────────────────────── --}}
            <div class="md:hidden space-y-3">
                @foreach($bids as $bid)
                @php
                    $status      = strtoupper($bid->status ?? '');
                    $statusStyle = match(true) {
                        str_contains($status, 'PUBLICAD')  => 'bg-green-50 text-green-700 ring-green-600/20',
                        str_contains($status, 'ADJUDIC')   => 'bg-gray-100 text-gray-600 ring-gray-500/10',
                        str_contains($status, 'CANCEL')    => 'bg-red-50 text-red-700 ring-red-600/20',
                        str_contains($status, 'DESIERTO')  => 'bg-yellow-50 text-yellow-800 ring-yellow-600/20',
                        str_contains($status, 'CERRADA')   => 'bg-yellow-50 text-yellow-800 ring-yellow-600/20',
                        str_contains($status, 'ABIERTO')   => 'bg-blue-50 text-blue-700 ring-blue-600/20',
                        str_contains($status, 'APERTURAD') => 'bg-blue-50 text-blue-700 ring-blue-600/20',
                        str_contains($status, 'EVALUAC')   => 'bg-blue-50 text-blue-700 ring-blue-600/20',
                        default                            => 'bg-gray-100 text-gray-600 ring-gray-500/10',
                    };
                    $deadlinePast = $bid->tender_deadline && $bid->tender_deadline->isPast();
                    $deadlineSoon = $bid->tender_deadline && !$deadlinePast && $bid->tender_deadline->diffInDays(now()) < 3;
                @endphp
                <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-xs active:bg-gray-50" @click="openDrawer({{ $bid->id }})">
                    <div class="flex items-start justify-between gap-2">
                        <h3 class="text-sm font-semibold text-gray-900 line-clamp-2 flex-1">{{ $bid->title }}</h3>
                        <button @click.stop="toggleBookmark({{ $bid->id }}, $event)"
                                class="shrink-0 transition-colors"
                                :class="bookmarks[{{ $bid->id }}] ? 'text-yellow-500' : 'text-gray-300'">
                            <svg class="size-5" :fill="bookmarks[{{ $bid->id }}] ? 'currentColor' : 'none'" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0Z"/>
                            </svg>
                        </button>
                    </div>
                    <div class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-gray-500">
                        <span class="truncate max-w-[200px]">{{ $bid->buyer_name ?? '—' }}</span>
                        <span class="text-gray-400">·</span>
                        <span class="font-mono">{{ $bid->process_code }}</span>
                    </div>
                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        <span class="inline-flex rounded-md px-1.5 py-0.5 text-xs font-medium ring-1 ring-inset {{ $statusStyle }}">
                            {{ $bid->status ?? 'N/D' }}
                        </span>
                        @if($bid->is_relevant)
                            <span class="rounded bg-green-50 px-1.5 py-0.5 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">Relevante</span>
                        @endif
                        @if($bid->mipymes)
                            <span class="rounded bg-purple-50 px-1.5 py-0.5 text-xs font-medium text-purple-700 ring-1 ring-inset ring-purple-600/20">MIPYMES{{ $bid->mipymes_mujeres ? ' Mujeres' : '' }}</span>
                        @endif
                        @if(!empty($bid->matched_rubros))
                            @foreach(array_slice($bid->matched_rubros, 0, 2) as $rubro)
                                @php $code = is_array($rubro) ? ($rubro['code'] ?? $rubro) : $rubro; @endphp
                                <span class="rounded bg-blue-50 px-1.5 py-0.5 text-xs font-mono text-blue-700">{{ $code }}</span>
                            @endforeach
                            @if(count($bid->matched_rubros) > 2)
                                <span class="text-xs text-gray-400">+{{ count($bid->matched_rubros) - 2 }}</span>
                            @endif
                        @endif
                    </div>
                    <div class="mt-2 flex items-center justify-between text-xs">
                        <div class="text-gray-500">
                            @if($bid->amount_estimated && $bid->amount_estimated > 0)
                                <span class="font-semibold text-gray-900">{{ $bid->currency === 'USD' ? 'US$' : 'RD$' }}{{ number_format($bid->amount_estimated, 0, '.', ',') }}</span>
                            @endif
                        </div>
                        @if($bid->tender_deadline)
                        <span class="{{ $deadlinePast ? 'text-red-600' : ($deadlineSoon ? 'text-amber-600' : 'text-gray-500') }}">
                            Cierre {{ $bid->tender_deadline->format('d/m/Y') }}
                            @if($deadlinePast)(vencida)@elseif($deadlineSoon)({{ $bid->tender_deadline->diffForHumans(['parts' => 1, 'short' => true]) }})@endif
                        </span>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>

            {{-- ── Desktop table (md and up) ────────────────────────────── --}}
            <div class="hidden md:block -mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                    <table class="min-w-full divide-y divide-gray-300">
                        <thead>
                            <tr>
                                <th class="w-8 py-3.5 pl-3 pr-1 sm:pl-0"></th>
                                @php
                                    $currentSort = request('sort', 'published_at');
                                    $currentDir  = request('dir', 'desc');
                                @endphp
                                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                                    <a href="{{ route('convocatorias.index', array_merge(request()->all(), ['sort' => 'title', 'dir' => $currentSort === 'title' && $currentDir === 'asc' ? 'desc' : 'asc'])) }}"
                                       class="group inline-flex items-center gap-1">
                                        Proceso
                                        @if($currentSort === 'title')
                                            <svg class="size-3.5 text-gray-500" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="{{ $currentDir === 'asc' ? 'M14.77 12.79a.75.75 0 0 1-1.06-.02L10 8.832 6.29 12.77a.75.75 0 1 1-1.08-1.04l4.25-4.5a.75.75 0 0 1 1.08 0l4.25 4.5a.75.75 0 0 1-.02 1.06Z' : 'M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z' }}" clip-rule="evenodd"/></svg>
                                        @endif
                                    </a>
                                </th>
                                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                                    <a href="{{ route('convocatorias.index', array_merge(request()->all(), ['sort' => 'buyer_name', 'dir' => $currentSort === 'buyer_name' && $currentDir === 'asc' ? 'desc' : 'asc'])) }}"
                                       class="group inline-flex items-center gap-1">
                                        Entidad
                                        @if($currentSort === 'buyer_name')
                                            <svg class="size-3.5 text-gray-500" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="{{ $currentDir === 'asc' ? 'M14.77 12.79a.75.75 0 0 1-1.06-.02L10 8.832 6.29 12.77a.75.75 0 1 1-1.08-1.04l4.25-4.5a.75.75 0 0 1 1.08 0l4.25 4.5a.75.75 0 0 1-.02 1.06Z' : 'M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z' }}" clip-rule="evenodd"/></svg>
                                        @endif
                                    </a>
                                </th>
                                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Estado</th>
                                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                                    <a href="{{ route('convocatorias.index', array_merge(request()->all(), ['sort' => 'amount_estimated', 'dir' => $currentSort === 'amount_estimated' && $currentDir === 'desc' ? 'asc' : 'desc'])) }}"
                                       class="group inline-flex items-center gap-1">
                                        Monto
                                        @if($currentSort === 'amount_estimated')
                                            <svg class="size-3.5 text-gray-500" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="{{ $currentDir === 'asc' ? 'M14.77 12.79a.75.75 0 0 1-1.06-.02L10 8.832 6.29 12.77a.75.75 0 1 1-1.08-1.04l4.25-4.5a.75.75 0 0 1 1.08 0l4.25 4.5a.75.75 0 0 1-.02 1.06Z' : 'M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z' }}" clip-rule="evenodd"/></svg>
                                        @endif
                                    </a>
                                </th>
                                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                                    <a href="{{ route('convocatorias.index', array_merge(request()->all(), ['sort' => 'published_at', 'dir' => $currentSort === 'published_at' && $currentDir === 'desc' ? 'asc' : 'desc'])) }}"
                                       class="group inline-flex items-center gap-1">
                                        Publicado
                                        @if($currentSort === 'published_at')
                                            <svg class="size-3.5 text-gray-500" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="{{ $currentDir === 'asc' ? 'M14.77 12.79a.75.75 0 0 1-1.06-.02L10 8.832 6.29 12.77a.75.75 0 1 1-1.08-1.04l4.25-4.5a.75.75 0 0 1 1.08 0l4.25 4.5a.75.75 0 0 1-.02 1.06Z' : 'M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z' }}" clip-rule="evenodd"/></svg>
                                        @endif
                                    </a>
                                </th>
                                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                                    <a href="{{ route('convocatorias.index', array_merge(request()->all(), ['sort' => 'tender_deadline', 'dir' => $currentSort === 'tender_deadline' && $currentDir === 'asc' ? 'desc' : 'asc'])) }}"
                                       class="group inline-flex items-center gap-1">
                                        Cierre
                                        @if($currentSort === 'tender_deadline')
                                            <svg class="size-3.5 text-gray-500" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="{{ $currentDir === 'asc' ? 'M14.77 12.79a.75.75 0 0 1-1.06-.02L10 8.832 6.29 12.77a.75.75 0 1 1-1.08-1.04l4.25-4.5a.75.75 0 0 1 1.08 0l4.25 4.5a.75.75 0 0 1-.02 1.06Z' : 'M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z' }}" clip-rule="evenodd"/></svg>
                                        @endif
                                    </a>
                                </th>
                                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Rubros</th>
                                <th class="relative py-3.5 pl-3 pr-4 sm:pr-0"><span class="sr-only">Acciones</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($bids as $bid)
                            @php
                                $status      = strtoupper($bid->status ?? '');
                                $statusStyle = match(true) {
                                    str_contains($status, 'PUBLICAD')  => 'bg-green-50 text-green-700 ring-green-600/20',
                                    str_contains($status, 'ADJUDIC')   => 'bg-gray-100 text-gray-600 ring-gray-500/10',
                                    str_contains($status, 'CANCEL')    => 'bg-red-50 text-red-700 ring-red-600/20',
                                    str_contains($status, 'DESIERTO')  => 'bg-yellow-50 text-yellow-800 ring-yellow-600/20',
                                    str_contains($status, 'CERRADA')   => 'bg-yellow-50 text-yellow-800 ring-yellow-600/20',
                                    str_contains($status, 'ABIERTO')   => 'bg-blue-50 text-blue-700 ring-blue-600/20',
                                    str_contains($status, 'APERTURAD') => 'bg-blue-50 text-blue-700 ring-blue-600/20',
                                    str_contains($status, 'EVALUAC')   => 'bg-blue-50 text-blue-700 ring-blue-600/20',
                                    default                            => 'bg-gray-100 text-gray-600 ring-gray-500/10',
                                };

                                $deadlinePast = $bid->tender_deadline && $bid->tender_deadline->isPast();
                                $deadlineSoon = $bid->tender_deadline && !$deadlinePast && $bid->tender_deadline->diffInDays(now()) < 3;
                            @endphp
                            <tr class="cursor-pointer hover:bg-gray-50" @click="openDrawer({{ $bid->id }})">
                                {{-- Bookmark --}}
                                <td class="py-4 pl-3 pr-1 sm:pl-0" @click.stop>
                                    <button @click="toggleBookmark({{ $bid->id }}, $event)"
                                            class="text-gray-300 hover:text-yellow-500 transition-colors"
                                            :class="bookmarks[{{ $bid->id }}] && 'text-yellow-500'">
                                        <svg class="size-5" :fill="bookmarks[{{ $bid->id }}] ? 'currentColor' : 'none'" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0Z"/>
                                        </svg>
                                    </button>
                                </td>
                                {{-- Title --}}
                                <td class="px-3 py-4">
                                    <div class="text-sm font-medium text-gray-900">{{ $bid->title }}</div>
                                    <div class="mt-0.5 flex items-center gap-2">
                                        <span class="text-xs text-gray-500 font-mono">{{ $bid->process_code }}</span>
                                        @if($bid->is_relevant)
                                            <span class="inline-flex rounded bg-green-50 px-1.5 py-0.5 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">Relevante</span>
                                        @endif
                                        @if($bid->mipymes)
                                            <span class="inline-flex rounded bg-purple-50 px-1.5 py-0.5 text-xs font-medium text-purple-700 ring-1 ring-inset ring-purple-600/20">MIPYMES{{ $bid->mipymes_mujeres ? ' Mujeres' : '' }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-3 py-4 text-sm text-gray-700">
                                    <span class="line-clamp-1 max-w-[180px]">{{ $bid->buyer_name ?? '—' }}</span>
                                </td>
                                <td class="px-3 py-4">
                                    <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset {{ $statusStyle }}">
                                        {{ $bid->status ?? 'N/D' }}
                                    </span>
                                </td>
                                <td class="px-3 py-4 text-sm text-gray-700 whitespace-nowrap">
                                    @if($bid->amount_estimated && $bid->amount_estimated > 0)
                                        {{ $bid->currency === 'USD' ? 'US$' : 'RD$' }}{{ number_format($bid->amount_estimated, 0, '.', ',') }}
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-3 py-4 text-sm text-gray-500 whitespace-nowrap">
                                    @if($bid->published_at)
                                        {{ $bid->published_at->format('d/m/Y') }}
                                        <div class="text-xs text-gray-400">{{ $bid->published_at->format('h:i A') }}</div>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-3 py-4 text-sm whitespace-nowrap {{ $deadlinePast ? 'text-red-600' : ($deadlineSoon ? 'text-amber-600' : 'text-gray-700') }}">
                                    @if($bid->tender_deadline)
                                        {{ $bid->tender_deadline->format('d/m/Y') }}
                                        <div class="text-xs {{ $deadlinePast ? 'text-red-500' : ($deadlineSoon ? 'text-amber-500' : 'text-gray-400') }}">
                                            {{ $bid->tender_deadline->format('h:i A') }}
                                            @if($deadlinePast)(vencida)@elseif($deadlineSoon)({{ $bid->tender_deadline->diffForHumans(['parts' => 1, 'short' => true]) }})@endif
                                        </div>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-3 py-4">
                                    @if(!empty($bid->matched_rubros))
                                        <div class="flex flex-wrap gap-1">
                                            @foreach(array_slice($bid->matched_rubros, 0, 2) as $rubro)
                                                @php $code = is_array($rubro) ? ($rubro['code'] ?? $rubro) : $rubro; @endphp
                                                <span class="inline-flex rounded bg-blue-50 px-1.5 py-0.5 text-xs font-mono text-blue-700">{{ $code }}</span>
                                            @endforeach
                                            @if(count($bid->matched_rubros) > 2)
                                                <span class="text-xs text-gray-400">+{{ count($bid->matched_rubros) - 2 }}</span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-gray-400 text-xs">—</span>
                                    @endif
                                </td>
                                <td class="py-4 pl-3 pr-4 text-right text-sm sm:pr-0" @click.stop>
                                    <div class="flex justify-end gap-x-2">
                                        {{-- DGCP external link --}}
                                        <a href="{{ $bid->secp_url }}" target="_blank" rel="noopener"
                                           class="rounded p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 transition-colors" title="Ver en DGCP">
                                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/>
                                            </svg>
                                        </a>
                                        {{-- Prellenar --}}
                                        <a href="{{ route('prellenado.show', $bid) }}"
                                           class="rounded p-1.5 text-gray-400 hover:text-purple-600 hover:bg-purple-50 transition-colors" title="Prellenar documentos">
                                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
                                            </svg>
                                        </a>
                                        {{-- Preparar oferta --}}
                                        <a href="{{ route('ofertas.create', ['bid' => $bid->id]) }}"
                                           class="rounded p-1.5 text-gray-400 hover:text-green-600 hover:bg-green-50 transition-colors" title="Preparar oferta">
                                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m3.75 9v6m3-3H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
                                            </svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            @if($bids->hasPages())
                <div class="mt-6">{{ $bids->links('components.pagination') }}</div>
            @endif
        @endif
    </div>

    @push('styles')
    <style>
        @media (max-width: 767px) {
            [x-data="convocatorias()"] .pagination-wrapper { font-size: 0.75rem; }
        }
    </style>
    @endpush

    {{-- ── Slide-over Drawer ──────────────────────────────────────────── --}}
    <div x-show="drawerOpen" x-cloak class="relative z-50" role="dialog" aria-modal="true">
        {{-- Backdrop --}}
        <div x-show="drawerOpen"
             x-transition:enter="ease-in-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in-out duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-gray-900/80" @click="closeDrawer()"></div>

        <div class="fixed inset-0 overflow-hidden">
            <div class="absolute inset-0 overflow-hidden">
                <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-6 sm:pl-16">
                    <div x-show="drawerOpen"
                         x-transition:enter="transform transition ease-in-out duration-300" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
                         x-transition:leave="transform transition ease-in-out duration-300" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
                         class="pointer-events-auto w-screen max-w-2xl">
                        <div class="flex h-full flex-col overflow-y-auto bg-white shadow-xl">

                            {{-- Drawer header --}}
                            <div class="sticky top-0 z-10 bg-white border-b border-gray-200 px-4 py-4 sm:px-6">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1 min-w-0 pr-4">
                                        {{-- Status badge --}}
                                        <template x-if="bid">
                                            <div class="flex items-center gap-2 mb-2">
                                                <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset"
                                                      :class="statusStyle(bid.status)">
                                                    <span x-text="bid.status || 'N/D'"></span>
                                                </span>
                                                <template x-if="bid.mipymes">
                                                    <span class="inline-flex rounded bg-purple-50 px-1.5 py-0.5 text-xs font-medium text-purple-700 ring-1 ring-inset ring-purple-600/20"
                                                          x-text="bid.mipymes_mujeres ? 'MIPYMES Mujeres' : 'MIPYMES'"></span>
                                                </template>
                                            </div>
                                        </template>
                                        {{-- Title --}}
                                        <h2 class="text-base font-semibold text-gray-900 leading-6" x-text="bid?.title || 'Cargando...'"></h2>
                                        {{-- Process code with copy --}}
                                        <template x-if="bid">
                                            <div class="mt-1 flex items-center gap-2">
                                                <span class="text-xs text-gray-500 font-mono" x-text="bid.process_code"></span>
                                                <button @click="copyCode()" class="transition-colors" :class="copied ? 'text-green-500' : 'text-gray-400 hover:text-gray-600'" :title="copied ? 'Copiado!' : 'Copiar código'">
                                                    <svg x-show="!copied" class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 0 1-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 0 1 1.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 0 0-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 0 1-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H9.75"/>
                                                    </svg>
                                                    <svg x-show="copied" x-cloak class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/>
                                                    </svg>
                                                </button>
                                                <a :href="bid.secp_url" target="_blank" rel="noopener" class="text-blue-600 hover:text-blue-500" title="Ver en DGCP">
                                                    <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/>
                                                    </svg>
                                                </a>
                                            </div>
                                        </template>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button @click="refreshDetail()" title="Actualizar datos desde la API"
                                                class="rounded-md bg-white p-1 text-gray-400 hover:text-blue-600 transition-colors">
                                            <svg class="size-5" :class="refreshing && 'animate-spin'" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182"/>
                                            </svg>
                                        </button>
                                        <button @click="closeDrawer()" class="rounded-md bg-white text-gray-400 hover:text-gray-500">
                                            <span class="sr-only">Cerrar</span>
                                            <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                {{-- Action row --}}
                                <template x-if="bid">
                                    <div class="mt-3 flex flex-wrap items-center gap-2 sm:gap-3">
                                        {{-- Guardar --}}
                                        <button @click="toggleBookmark(bid.id, $event)" class="inline-flex items-center gap-1.5 rounded-md px-2.5 py-1.5 text-xs font-medium transition-colors"
                                                :class="bid.is_bookmarked ? 'bg-yellow-50 text-yellow-700 ring-1 ring-inset ring-yellow-600/20' : 'bg-gray-50 text-gray-600 ring-1 ring-inset ring-gray-300 hover:bg-gray-100'">
                                            <svg class="size-4" :fill="bid.is_bookmarked ? 'currentColor' : 'none'" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0Z"/>
                                            </svg>
                                            Guardar
                                        </button>
                                        {{-- Notificar --}}
                                        <button @click="toggleWatch(bid.id)" class="inline-flex items-center gap-1.5 rounded-md px-2.5 py-1.5 text-xs font-medium transition-colors"
                                                :class="bid.is_watched ? 'bg-blue-50 text-blue-700 ring-1 ring-inset ring-blue-600/20' : 'bg-gray-50 text-gray-600 ring-1 ring-inset ring-gray-300 hover:bg-gray-100'">
                                            <svg class="size-4" :fill="bid.is_watched ? 'currentColor' : 'none'" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/>
                                            </svg>
                                            Notificar
                                        </button>
                                        {{-- Prellenar --}}
                                        <a :href="'/convocatorias/' + bid.id + '/prellenar'"
                                           class="inline-flex items-center gap-1.5 rounded-md bg-purple-50 px-2.5 py-1.5 text-xs font-medium text-purple-700 ring-1 ring-inset ring-purple-600/20 hover:bg-purple-100 transition-colors">
                                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
                                            </svg>
                                            Prellenar
                                        </a>
                                        {{-- Preparar oferta --}}
                                        <a :href="bid.has_offer ? '/ofertas/' + bid.offer_id : '/ofertas/create?bid=' + bid.id"
                                           class="inline-flex items-center gap-1.5 rounded-md bg-blue-50 px-2.5 py-1.5 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-600/20 hover:bg-blue-100 transition-colors">
                                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m3.75 9v6m3-3H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
                                            </svg>
                                            <span x-text="bid.has_offer ? 'Ver oferta' : 'Preparar oferta'"></span>
                                        </a>
                                    </div>
                                </template>

                                {{-- Agregar al tablero CTA --}}
                                <template x-if="bid">
                                    <div class="mt-3">
                                        <template x-if="bid.on_tablero">
                                            <a :href="'/ofertas/' + bid.offer_id"
                                               class="flex items-center justify-between rounded-lg border border-gray-200 px-4 py-3 text-sm hover:bg-gray-50 transition-colors">
                                                <div>
                                                    <p class="font-medium text-gray-900">En el tablero</p>
                                                    <p class="text-xs text-gray-500">Esta convocatoria ya tiene una oferta asociada.</p>
                                                </div>
                                                <span class="text-blue-600 text-xs font-medium">Ver en tablero &rarr;</span>
                                            </a>
                                        </template>
                                        <template x-if="!bid.on_tablero">
                                            <button @click="addToTablero(bid.id)"
                                                    class="flex w-full items-center justify-between rounded-lg border border-dashed border-gray-300 px-4 py-3 text-sm hover:border-blue-400 hover:bg-blue-50/50 transition-colors">
                                                <div class="text-left">
                                                    <p class="font-medium text-gray-900">Agregar al tablero</p>
                                                    <p class="text-xs text-gray-500">Cree una tarea asociada y gest&iacute;onela desde su pipeline.</p>
                                                </div>
                                                <span class="flex size-8 items-center justify-center rounded-full bg-blue-600 text-white shadow-sm">
                                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                                                    </svg>
                                                </span>
                                            </button>
                                        </template>
                                    </div>
                                </template>
                            </div>

                            {{-- Loading state --}}
                            <template x-if="loading">
                                <div class="flex-1 flex items-center justify-center py-20">
                                    <svg class="animate-spin size-8 text-blue-600" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                </div>
                            </template>

                            {{-- Drawer body --}}
                            <template x-if="!loading && bid">
                                <div class="flex-1 px-4 py-6 sm:px-6 space-y-6">

                                    {{-- Key dates & value --}}
                                    <div>
                                        <h3 class="text-sm font-medium text-gray-900">Datos clave</h3>
                                        <dl class="mt-2 divide-y divide-gray-100">
                                            <div class="flex justify-between py-2">
                                                <dt class="text-sm text-gray-500">Fecha de publicación</dt>
                                                <dd class="text-sm text-gray-900" x-text="bid.published_at || '—'"></dd>
                                            </div>
                                            <div class="flex justify-between py-2">
                                                <dt class="text-sm text-gray-500">Fecha de cierre</dt>
                                                <dd class="flex items-center gap-x-2 text-sm" :class="bid.tender_deadline_past ? 'text-red-600 font-medium' : 'text-gray-900'">
                                                    <span x-text="bid.tender_deadline || '—'"></span>
                                                    <a x-show="bid.tender_deadline" :href="'/calendar/bid/' + bid.id + '.ics'"
                                                       class="inline-flex items-center rounded bg-gray-100 px-1.5 py-0.5 text-xs font-medium text-gray-600 hover:bg-blue-100 hover:text-blue-700 transition-colors"
                                                       title="Agregar al calendario">
                                                        <svg class="size-3.5 mr-0.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/>
                                                        </svg>
                                                        .ics
                                                    </a>
                                                </dd>
                                            </div>
                                            <div class="flex justify-between py-2">
                                                <dt class="text-sm text-gray-500">Valor</dt>
                                                <dd class="text-sm font-medium text-gray-900" x-text="formatAmount(bid.amount_estimated, bid.currency)"></dd>
                                            </div>
                                        </dl>
                                    </div>

                                    {{-- Institution info --}}
                                    <div>
                                        <h3 class="text-sm font-medium text-gray-900">Institución</h3>
                                        <dl class="mt-2 divide-y divide-gray-100">
                                            <template x-for="[label, value] in institutionFields()" :key="label">
                                                <div class="flex justify-between py-2" x-show="value">
                                                    <dt class="text-sm text-gray-500" x-text="label"></dt>
                                                    <dd class="text-sm text-gray-900 text-right max-w-[60%]" x-text="value"></dd>
                                                </div>
                                            </template>
                                        </dl>
                                    </div>

                                    {{-- Cronograma --}}
                                    <div x-show="cronograma.length > 0">
                                        <div class="flex items-center justify-between">
                                            <h3 class="text-sm font-medium text-gray-900">Cronograma</h3>
                                            <button x-show="cronograma.length > 3" @click="showAllCronograma = !showAllCronograma"
                                                    class="text-xs text-blue-600 hover:text-blue-500"
                                                    x-text="showAllCronograma ? 'Ver menos' : '+' + (cronograma.length - 3) + ' eventos más'"></button>
                                        </div>
                                        <div class="mt-3 flow-root">
                                            <ul class="-mb-4">
                                                <template x-for="(event, idx) in (showAllCronograma ? cronograma : cronograma.slice(0, 3))" :key="idx">
                                                    <li class="relative pb-4">
                                                        <template x-if="idx < (showAllCronograma ? cronograma.length : Math.min(cronograma.length, 3)) - 1">
                                                            <span class="absolute top-3 left-3 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
                                                        </template>
                                                        <div class="relative flex items-start space-x-3">
                                                            <div>
                                                                <span class="flex size-6 items-center justify-center rounded-full ring-4 ring-white"
                                                                      :class="event.is_past ? 'bg-green-500' : 'bg-gray-300'">
                                                                    <template x-if="event.is_past">
                                                                        <svg class="size-3.5 text-white" viewBox="0 0 20 20" fill="currentColor">
                                                                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd"/>
                                                                        </svg>
                                                                    </template>
                                                                    <template x-if="!event.is_past">
                                                                        <span class="size-2 rounded-full bg-white"></span>
                                                                    </template>
                                                                </span>
                                                            </div>
                                                            <div class="flex min-w-0 flex-1 justify-between">
                                                                <p class="text-sm text-gray-700" x-text="event.label"></p>
                                                                <div class="text-right whitespace-nowrap pl-3">
                                                                    <p class="text-xs text-gray-500" x-text="event.date"></p>
                                                                    <template x-if="event.countdown">
                                                                        <span class="inline-flex rounded bg-amber-50 px-1.5 py-0.5 text-xs text-amber-700 ring-1 ring-inset ring-amber-600/20 mt-0.5"
                                                                              x-text="event.countdown"></span>
                                                                    </template>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </li>
                                                </template>
                                            </ul>
                                        </div>
                                    </div>

                                    {{-- Tabs: Artículos | Documentos | Adjudicación --}}
                                    <div>
                                        <div class="border-b border-gray-200">
                                            <nav class="-mb-px flex space-x-3 sm:space-x-6">
                                                <button @click="activeTab = 'articulos'; loadTab('articulos')"
                                                        class="border-b-2 py-3 text-sm font-medium whitespace-nowrap"
                                                        :class="activeTab === 'articulos' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'">
                                                    Artículos y Lotes
                                                </button>
                                                <button @click="activeTab = 'documentos'; loadTab('documentos')"
                                                        class="border-b-2 py-3 text-sm font-medium whitespace-nowrap"
                                                        :class="activeTab === 'documentos' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'">
                                                    Documentos
                                                </button>
                                                <button @click="activeTab = 'adjudicacion'; loadTab('adjudicacion')"
                                                        class="border-b-2 py-3 text-sm font-medium whitespace-nowrap"
                                                        :class="activeTab === 'adjudicacion' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'">
                                                    Adjudicación
                                                </button>
                                            </nav>
                                        </div>

                                        <div class="mt-4">
                                            {{-- Tab loading --}}
                                            <template x-if="tabLoading">
                                                <div class="flex justify-center py-8">
                                                    <svg class="animate-spin size-6 text-blue-600" fill="none" viewBox="0 0 24 24">
                                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                                    </svg>
                                                </div>
                                            </template>

                                            {{-- Artículos tab --}}
                                            <template x-if="!tabLoading && activeTab === 'articulos'">
                                                <div>
                                                    <template x-if="tabData.length === 0">
                                                        <p class="text-sm text-gray-500 py-4">Sin artículos disponibles.</p>
                                                    </template>
                                                    <template x-if="tabData.length > 0">
                                                        <div class="overflow-x-auto">
                                                            <table class="min-w-full text-sm">
                                                                <thead>
                                                                    <tr class="border-b border-gray-200">
                                                                        <th class="py-2 pr-3 text-left font-medium text-gray-900">UNSPSC</th>
                                                                        <th class="py-2 px-3 text-left font-medium text-gray-900">Descripción</th>
                                                                        <th class="py-2 px-3 text-right font-medium text-gray-900">Cant.</th>
                                                                        <th class="py-2 px-3 text-right font-medium text-gray-900">P. Unit.</th>
                                                                        <th class="py-2 pl-3 text-right font-medium text-gray-900">Total</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody class="divide-y divide-gray-100">
                                                                    <template x-for="(item, i) in tabData" :key="i">
                                                                        <tr>
                                                                            <td class="py-2 pr-3 font-mono text-xs text-gray-500" x-text="[item.familia, item.clase, item.subclase].filter(Boolean).join('-') || '—'"></td>
                                                                            <td class="py-2 px-3 text-gray-700" x-text="item.descripcion_usuario || item.descripcion_articulo || '—'"></td>
                                                                            <td class="py-2 px-3 text-right text-gray-700" x-text="item.cantidad ?? '—'"></td>
                                                                            <td class="py-2 px-3 text-right text-gray-700" x-text="item.precio_unitario_estimado ? formatNum(item.precio_unitario_estimado) : '—'"></td>
                                                                            <td class="py-2 pl-3 text-right font-medium text-gray-900" x-text="item.precio_total_estimado ? formatNum(item.precio_total_estimado) : '—'"></td>
                                                                        </tr>
                                                                    </template>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </template>
                                                </div>
                                            </template>

                                            {{-- Documentos tab --}}
                                            <template x-if="!tabLoading && activeTab === 'documentos'">
                                                <div>
                                                    <template x-if="tabData.length === 0">
                                                        <p class="text-sm text-gray-500 py-4">Sin documentos disponibles.</p>
                                                    </template>
                                                    <template x-if="tabData.length > 0">
                                                        <ul class="divide-y divide-gray-100">
                                                            <template x-for="(doc, i) in tabData" :key="i">
                                                                <li class="flex items-center justify-between py-3">
                                                                    <div class="min-w-0 flex-1">
                                                                        <p class="text-sm font-medium text-gray-900 truncate" x-text="doc.nombre_documento || doc.tipo_documento || 'Documento'"></p>
                                                                        <p class="text-xs text-gray-500" x-text="(doc.tipo_documento || '') + (doc.fecha_carga_archivo ? ' — ' + doc.fecha_carga_archivo : '')"></p>
                                                                    </div>
                                                                    <template x-if="doc.url_documento">
                                                                        <a :href="'/convocatorias/' + bid.id + '/download-doc?url=' + encodeURIComponent(doc.url_documento) + '&filename=' + encodeURIComponent(doc.nombre_documento || 'documento.pdf')"
                                                                           class="ml-4 shrink-0 rounded bg-blue-50 px-2.5 py-1.5 text-xs font-medium text-blue-700 hover:bg-blue-100">
                                                                            Descargar
                                                                        </a>
                                                                    </template>
                                                                </li>
                                                            </template>
                                                        </ul>
                                                    </template>
                                                </div>
                                            </template>

                                            {{-- Adjudicación tab --}}
                                            <template x-if="!tabLoading && activeTab === 'adjudicacion'">
                                                <div>
                                                    <template x-if="!tabData.contracts || tabData.contracts.length === 0">
                                                        <p class="text-sm text-gray-500 py-4">Sin datos de adjudicación disponibles.</p>
                                                    </template>
                                                    <template x-if="tabData.contracts && tabData.contracts.length > 0">
                                                        <div class="space-y-4">
                                                            <template x-for="(contract, i) in tabData.contracts" :key="i">
                                                                <div class="rounded-lg border border-gray-200 p-4">
                                                                    <div class="flex items-center justify-between">
                                                                        <p class="text-sm font-medium text-gray-900" x-text="contract.razon_social || contract.proveedor || 'Proveedor'"></p>
                                                                        <span class="text-sm font-semibold text-gray-900" x-text="contract.monto_total ? formatNum(contract.monto_total) : '—'"></span>
                                                                    </div>
                                                                    <div class="mt-1 flex flex-wrap gap-x-4 text-xs text-gray-500">
                                                                        <span x-show="contract.rpe" x-text="'RPE: ' + contract.rpe"></span>
                                                                        <span x-show="contract.estado_contrato" x-text="contract.estado_contrato"></span>
                                                                        <span x-show="contract.forma_pago" x-text="contract.forma_pago"></span>
                                                                    </div>
                                                                </div>
                                                            </template>

                                                            {{-- Awarded articles --}}
                                                            <template x-if="tabData.articles && tabData.articles.length > 0">
                                                                <div>
                                                                    <h4 class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-2">Artículos adjudicados</h4>
                                                                    <table class="min-w-full text-sm">
                                                                        <thead>
                                                                            <tr class="border-b border-gray-200">
                                                                                <th class="py-2 pr-3 text-left font-medium text-gray-900">Descripción</th>
                                                                                <th class="py-2 px-3 text-right font-medium text-gray-900">Cant.</th>
                                                                                <th class="py-2 px-3 text-right font-medium text-gray-900">P. Unit.</th>
                                                                                <th class="py-2 pl-3 text-right font-medium text-gray-900">Total</th>
                                                                            </tr>
                                                                        </thead>
                                                                        <tbody class="divide-y divide-gray-100">
                                                                            <template x-for="(art, j) in tabData.articles" :key="j">
                                                                                <tr>
                                                                                    <td class="py-2 pr-3 text-gray-700" x-text="art.descripcion_articulo || art.descripcion_usuario || '—'"></td>
                                                                                    <td class="py-2 px-3 text-right text-gray-700" x-text="art.cantidad ?? '—'"></td>
                                                                                    <td class="py-2 px-3 text-right text-gray-700" x-text="art.precio_unitario ? formatNum(art.precio_unitario) : '—'"></td>
                                                                                    <td class="py-2 pl-3 text-right font-medium text-gray-900" x-text="art.precio_total ? formatNum(art.precio_total) : '—'"></td>
                                                                                </tr>
                                                                            </template>
                                                                        </tbody>
                                                                    </table>
                                                                </div>
                                                            </template>
                                                        </div>
                                                    </template>
                                                </div>
                                            </template>
                                        </div>
                                    </div>

                                    {{-- Matched rubros --}}
                                    <div x-show="bid.matched_rubros && bid.matched_rubros.length > 0">
                                        <h3 class="text-sm font-medium text-gray-900">Rubros coincidentes</h3>
                                        <div class="mt-2 flex flex-wrap gap-1.5">
                                            <template x-for="rubro in bid.matched_rubros" :key="rubro">
                                                <span class="inline-flex rounded bg-blue-50 px-2 py-1 text-xs font-mono text-blue-700 ring-1 ring-inset ring-blue-600/20"
                                                      x-text="typeof rubro === 'object' ? (rubro.code || rubro) : rubro"></span>
                                            </template>
                                        </div>
                                    </div>

                                </div>
                            </template>

                            {{-- Drawer footer --}}
                            <template x-if="bid">
                                <div class="sticky bottom-0 border-t border-gray-200 bg-gray-50 px-4 py-4 sm:px-6">
                                    <div class="flex items-center justify-between gap-3">
                                        <a :href="bid.secp_url" target="_blank" rel="noopener"
                                           class="inline-flex items-center gap-1.5 text-sm font-medium text-gray-600 hover:text-gray-900">
                                            Ver en DGCP
                                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/>
                                            </svg>
                                        </a>
                                        <a :href="bid.has_offer ? '/ofertas/' + bid.offer_id : '/ofertas/create?bid=' + bid.id"
                                           class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-xs hover:bg-blue-500">
                                            <span x-text="bid.has_offer ? 'Ver oferta' : 'Preparar oferta'"></span> &rarr;
                                        </a>
                                    </div>
                                </div>
                            </template>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
function convocatorias() {
    return {
        drawerOpen: false,
        loading: false,
        refreshing: false,
        bid: null,
        cronograma: [],
        institution: {},
        activeTab: 'articulos',
        tabData: [],
        tabLoading: false,
        tabCache: {},
        showAllCronograma: false,
        copied: false,
        bookmarks: {!! json_encode($bids->pluck('is_bookmarked', 'id')) !!},

        async openDrawer(bidId) {
            this.drawerOpen = true;
            this.loading = true;
            this.bid = null;
            this.cronograma = [];
            this.institution = {};
            this.activeTab = 'articulos';
            this.tabData = [];
            this.tabCache = {};
            this.showAllCronograma = false;

            try {
                const res = await fetch(`/convocatorias/${bidId}/detail`);
                const json = await res.json();
                this.bid = json.bid;
                this.cronograma = json.cronograma || [];
                this.institution = json.institution || {};
            } catch (e) {
                console.error('Error loading bid detail:', e);
            } finally {
                this.loading = false;
            }

            this.loadTab('articulos');
        },

        closeDrawer() {
            this.drawerOpen = false;
        },

        async refreshDetail() {
            if (!this.bid) return;
            this.refreshing = true;
            this.tabCache = {};
            try {
                const res = await fetch(`/convocatorias/${this.bid.id}/detail`);
                const json = await res.json();
                this.bid = json.bid;
                this.cronograma = json.cronograma || [];
                this.institution = json.institution || {};
                await this.loadTab(this.activeTab, true);
            } catch (e) {
                console.error('Error refreshing:', e);
            }
            this.refreshing = false;
        },

        async loadTab(tab, forceRefresh = false) {
            if (!this.bid) return;
            if (!forceRefresh && this.tabCache[tab]) {
                this.tabData = this.tabCache[tab];
                return;
            }

            this.tabLoading = true;
            try {
                const refreshParam = forceRefresh ? '&refresh=1' : '';
                const res = await fetch(`/convocatorias/${this.bid.id}/tab?tab=${tab}${refreshParam}`);
                const json = await res.json();
                this.tabData = json.data || [];
                this.tabCache[tab] = this.tabData;
            } catch (e) {
                this.tabData = [];
                console.error('Error loading tab:', e);
            } finally {
                this.tabLoading = false;
            }
        },

        async toggleBookmark(bidId, event) {
            event?.stopPropagation();
            try {
                const res = await fetch(`/convocatorias/${bidId}/bookmark`, {
                    method: 'PATCH',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
                });
                const json = await res.json();
                this.bookmarks[bidId] = json.bookmarked;
                if (this.bid && this.bid.id === bidId) {
                    this.bid.is_bookmarked = json.bookmarked;
                }
            } catch (e) {
                console.error('Bookmark error:', e);
            }
        },

        async addToTablero(bidId) {
            try {
                const res = await fetch('/tablero/add-bid', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ bid_id: bidId }),
                });
                const json = await res.json();
                if (this.bid && this.bid.id === bidId) {
                    this.bid.on_tablero = true;
                    this.bid.has_offer = true;
                    this.bid.offer_id = json.offer_id;
                }
            } catch (e) {
                console.error('Add to tablero error:', e);
            }
        },

        async toggleWatch(bidId) {
            try {
                const res = await fetch(`/convocatorias/${bidId}/watch`, {
                    method: 'PATCH',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
                });
                const json = await res.json();
                if (this.bid && this.bid.id === bidId) {
                    this.bid.is_watched = json.watched;
                    // Watching auto-activates bookmark
                    if (json.bookmarked !== undefined) {
                        this.bid.is_bookmarked = json.bookmarked;
                        this.bookmarks[bidId] = json.bookmarked;
                    }
                }
            } catch (e) {
                console.error('Watch toggle error:', e);
            }
        },

        copyCode() {
            const text = this.bid?.process_code;
            if (!text) return;
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text);
            } else {
                const ta = document.createElement('textarea');
                ta.value = text;
                ta.style.position = 'fixed';
                ta.style.opacity = '0';
                document.body.appendChild(ta);
                ta.select();
                document.execCommand('copy');
                document.body.removeChild(ta);
            }
            this.copied = true;
            setTimeout(() => this.copied = false, 1500);
        },

        statusStyle(status) {
            const s = (status || '').toUpperCase();
            if (s.includes('PUBLICAD')) return 'bg-green-50 text-green-700 ring-green-600/20';
            if (s.includes('ADJUDIC')) return 'bg-gray-100 text-gray-600 ring-gray-500/10';
            if (s.includes('CANCEL')) return 'bg-red-50 text-red-700 ring-red-600/20';
            if (s.includes('DESIERTO') || s.includes('CERRADA')) return 'bg-yellow-50 text-yellow-800 ring-yellow-600/20';
            if (s.includes('ABIERTO') || s.includes('APERTURAD') || s.includes('EVALUAC')) return 'bg-blue-50 text-blue-700 ring-blue-600/20';
            return 'bg-gray-100 text-gray-600 ring-gray-500/10';
        },

        institutionFields() {
            const i = this.institution;
            return [
                ['Institución', i.institucion],
                ['Encargado/a', i.encargado],
                ['Correo electrónico', i.email],
                ['Teléfono', i.telefono],
                ['Modalidad', i.modalidad],
                ['Objeto', i.objeto],
                ['Duración contrato', i.duracion_contrato],
                ['Proveedores notificados', i.proveedores_notificados],
            ];
        },

        formatAmount(amount, currency) {
            if (!amount || amount == 0) return '—';
            const prefix = currency === 'USD' ? 'US$' : 'RD$';
            return prefix + Number(amount).toLocaleString('en', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },

        formatNum(n) {
            if (n == null) return '—';
            return Number(n).toLocaleString('en', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },
    };
}
</script>
@endsection
