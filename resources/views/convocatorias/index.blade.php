@extends('layouts.app')
@section('title', 'Convocatorias')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">

    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-base font-semibold text-gray-900">Convocatorias</h1>
            <p class="mt-1 text-sm text-gray-500">{{ number_format($bids->total()) }} convocatoria{{ $bids->total() !== 1 ? 's' : '' }} registrada{{ $bids->total() !== 1 ? 's' : '' }}.</p>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" action="{{ route('convocatorias.index') }}" class="mt-6 flex flex-wrap items-end gap-4">
        <div class="flex-1 min-w-[200px] max-w-sm">
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
            <a href="{{ route('convocatorias.index') }}" class="text-sm text-blue-600 hover:text-blue-500">Limpiar</a>
        @endif
    </form>

    @if(session('success'))
        <div class="mt-4 rounded-md bg-green-50 p-4 text-sm text-green-800 ring-1 ring-inset ring-green-200">
            {{ session('success') }}
        </div>
    @endif

    <div class="mt-8 flow-root">
        @if($bids->isEmpty())
            <div class="text-center py-16">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" class="mx-auto size-12 text-gray-400">
                    <path d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m6.75 12H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <h3 class="mt-2 text-sm font-semibold text-gray-900">Sin convocatorias</h3>
                <p class="mt-1 text-sm text-gray-500">Ejecuta el sondeo desde el dashboard para comenzar.</p>
            </div>
        @else
            <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                    <table class="min-w-full divide-y divide-gray-300">
                        <thead>
                            <tr>
                                <th class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-0">Proceso</th>
                                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Entidad</th>
                                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Estado</th>
                                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Monto</th>
                                <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Cierre</th>
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
                            @endphp
                            <tr>
                                <td class="py-4 pl-4 pr-3 sm:pl-0">
                                    <div class="text-sm font-medium text-gray-900 line-clamp-1">{{ $bid->title }}</div>
                                    <div class="mt-0.5 text-xs text-gray-500 font-mono">{{ $bid->process_code }}</div>
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
                                <td class="px-3 py-4 text-sm whitespace-nowrap {{ $deadlinePast ? 'text-red-600' : 'text-gray-700' }}">
                                    @if($bid->tender_deadline)
                                        {{ $bid->tender_deadline->format('d/m/Y') }}
                                        @if($deadlinePast)
                                            <span class="text-xs">(vencida)</span>
                                        @endif
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
                                <td class="py-4 pl-3 pr-4 text-right text-sm sm:pr-0">
                                    <div class="flex justify-end gap-x-3">
                                        <a href="{{ $bid->secp_url }}" target="_blank" rel="noopener"
                                           class="text-blue-600 hover:text-blue-500 text-xs font-medium">SECP</a>
                                        <a href="{{ route('ofertas.create', ['bid' => $bid->id]) }}"
                                           class="text-green-600 hover:text-green-500 text-xs font-medium">Preparar oferta</a>
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

</div>
@endsection
