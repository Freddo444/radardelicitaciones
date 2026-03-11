@extends('layouts.app')
@section('title', 'Convocatorias')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">

    {{-- Stat cards --}}
    <dl class="grid grid-cols-1 gap-5 sm:grid-cols-3">

        <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow-sm sm:p-6">
            <dt class="flex items-center gap-x-3">
                <div class="rounded-md bg-indigo-500 p-2">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" class="size-5 text-white">
                        <path d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <span class="truncate text-sm font-medium text-gray-500">Total convocatorias</span>
            </dt>
            <dd class="mt-3 text-3xl font-semibold text-gray-900">{{ number_format($stats['total']) }}</dd>
        </div>

        <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow-sm sm:p-6">
            <dt class="flex items-center gap-x-3">
                <div class="rounded-md bg-indigo-500 p-2">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" class="size-5 text-white">
                        <path d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <span class="truncate text-sm font-medium text-gray-500">Últimos 7 días</span>
            </dt>
            <dd class="mt-3 text-3xl font-semibold text-gray-900">{{ number_format($stats['this_week']) }}</dd>
        </div>

        <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow-sm sm:p-6">
            <dt class="flex items-center gap-x-3">
                <div class="rounded-md bg-indigo-500 p-2">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" class="size-5 text-white">
                        <path d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <span class="truncate text-sm font-medium text-gray-500">Sin notificar</span>
            </dt>
            <dd class="mt-3 text-3xl font-semibold text-gray-900">{{ number_format($stats['unnotified']) }}</dd>
        </div>

    </dl>

    {{-- Bid list --}}
    <div class="mt-10">

        @if($bids->isEmpty())
            {{-- Empty state --}}
            <div class="text-center py-20">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" class="mx-auto size-12 text-gray-400">
                    <path d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m6.75 12H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <h3 class="mt-2 text-sm font-semibold text-gray-900">Sin convocatorias aún</h3>
                <p class="mt-1 text-sm text-gray-500">Las convocatorias aparecerán aquí una vez que se ejecute el sondeo y se encuentren coincidencias.</p>
                <div class="mt-6">
                    <form method="POST" action="{{ route('poll.manual') }}" class="inline">
                        @csrf
                        <button type="submit"
                                class="inline-flex items-center gap-x-2 rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                            <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="-ml-0.5 size-5">
                                <path fill-rule="evenodd" d="M15.312 11.424a5.5 5.5 0 0 1-9.201 2.466l-.312-.311h2.433a.75.75 0 0 0 0-1.5H3.989a.75.75 0 0 0-.75.75v4.242a.75.75 0 0 0 1.5 0v-2.43l.31.31a7 7 0 0 0 11.712-3.138.75.75 0 0 0-1.449-.39Zm1.23-3.723a.75.75 0 0 0 .219-.53V2.929a.75.75 0 0 0-1.5 0V5.36l-.31-.31A7 7 0 0 0 3.239 8.188a.75.75 0 1 0 1.448.389A5.5 5.5 0 0 1 13.89 6.11l.311.31h-2.432a.75.75 0 0 0 0 1.5h4.243a.75.75 0 0 0 .53-.219Z" clip-rule="evenodd"/>
                            </svg>
                            Ejecutar sondeo ahora
                        </button>
                    </form>
                </div>
            </div>
        @else
            <ul role="list" class="divide-y divide-gray-100">
                @foreach($bids as $bid)
                @php
                    $status = strtoupper($bid->status ?? '');
                    $statusStyle = match(true) {
                        str_contains($status, 'PUBLICAD')  => 'bg-green-50 text-green-700 inset-ring-green-600/20',
                        str_contains($status, 'ADJUDIC')   => 'bg-gray-100 text-gray-600 inset-ring-gray-500/10',
                        str_contains($status, 'CELEBRAD')  => 'bg-gray-100 text-gray-600 inset-ring-gray-500/10',
                        str_contains($status, 'CANCEL')    => 'bg-red-50 text-red-700 inset-ring-red-600/20',
                        str_contains($status, 'DESIERTO')  => 'bg-yellow-50 text-yellow-800 inset-ring-yellow-600/20',
                        str_contains($status, 'CERRADA')   => 'bg-yellow-50 text-yellow-800 inset-ring-yellow-600/20',
                        str_contains($status, 'ABIERTO')   => 'bg-blue-50 text-blue-700 inset-ring-blue-600/20',
                        str_contains($status, 'APERTURAD') => 'bg-blue-50 text-blue-700 inset-ring-blue-600/20',
                        str_contains($status, 'EVALUAC')   => 'bg-blue-50 text-blue-700 inset-ring-blue-600/20',
                        default                            => 'bg-gray-100 text-gray-600 inset-ring-gray-500/10',
                    };
                @endphp
                <li class="flex items-start justify-between gap-x-6 py-5">
                    {{-- Left: title, meta, rubros --}}
                    <div class="min-w-0 flex-1">
                        <div class="flex items-start gap-x-3">
                            <p class="text-sm/6 font-semibold text-gray-900">{{ $bid->title }}</p>
                            <span class="mt-0.5 shrink-0 rounded-md px-1.5 py-0.5 text-xs font-medium inset-ring {{ $statusStyle }}">
                                {{ $bid->status ?? 'N/D' }}
                            </span>
                        </div>
                        <div class="mt-1 flex flex-wrap items-center gap-x-2 text-xs/5 text-gray-500">
                            <p class="truncate">{{ $bid->buyer_name }}</p>
                            @if($bid->procurement_method)
                                <svg viewBox="0 0 2 2" class="size-0.5 fill-current"><circle r="1" cx="1" cy="1"/></svg>
                                <p>{{ $bid->procurement_method }}</p>
                            @endif
                            <svg viewBox="0 0 2 2" class="size-0.5 fill-current"><circle r="1" cx="1" cy="1"/></svg>
                            <p>Publicado {{ $bid->published_at?->diffForHumans() ?? 'N/D' }}</p>
                            @if($bid->tender_deadline)
                                <svg viewBox="0 0 2 2" class="size-0.5 fill-current"><circle r="1" cx="1" cy="1"/></svg>
                                <p>Cierre {{ $bid->tender_deadline->format('d/m/Y') }}</p>
                            @endif
                        </div>
                        @if(!empty($bid->matched_rubros))
                        <div class="mt-2 flex flex-wrap gap-1.5">
                            @foreach($bid->matched_rubros as $rubro)
                            @php $code = is_array($rubro) ? ($rubro['code'] ?? $rubro) : $rubro; $name = is_array($rubro) ? ($rubro['name'] ?? '') : ''; @endphp
                            <span title="{{ $name }}"
                                  class="inline-flex items-center rounded-md bg-indigo-50 px-1.5 py-0.5 font-mono text-xs font-medium text-indigo-700 ring-1 ring-inset ring-indigo-700/10">
                                {{ $code }}
                            </span>
                            @endforeach
                        </div>
                        @endif
                    </div>

                    {{-- Right: amount + link --}}
                    <div class="flex shrink-0 flex-col items-end gap-y-2">
                        @if($bid->amount_estimated && $bid->amount_estimated > 0)
                        <p class="text-sm font-semibold text-gray-900">
                            {{ $bid->currency === 'USD' ? 'US$' : 'RD$' }}{{ number_format($bid->amount_estimated, 0, '.', ',') }}
                        </p>
                        @endif
                        <a href="{{ $bid->secp_url }}" target="_blank" rel="noopener"
                           class="rounded-md bg-white px-2.5 py-1.5 text-xs font-semibold text-gray-900 shadow-xs inset-ring inset-ring-gray-300 hover:bg-gray-50">
                            Ver en SECP
                        </a>
                    </div>
                </li>
                @endforeach
            </ul>

            {{-- Pagination --}}
            @if($bids->hasPages())
            <div class="mt-6 border-t border-gray-100 pt-4">
                {{ $bids->links() }}
            </div>
            @endif
        @endif

    </div>
</div>
@endsection
