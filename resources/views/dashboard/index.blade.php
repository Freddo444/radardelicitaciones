@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">

    {{-- ── Expiry alerts banner ──────────────────────────────────────── --}}
    @if($expiryAlerts->isNotEmpty())
    <div class="mb-6 space-y-2">
        @foreach($expiryAlerts as $doc)
        @php
            $expired  = $doc->expires_at->isPast();
            $daysLeft = (int) now()->diffInDays($doc->expires_at, false);
        @endphp
        <div class="flex items-center gap-x-3 rounded-lg px-4 py-3 text-sm {{ $expired ? 'bg-red-50 ring-1 ring-inset ring-red-200' : 'bg-amber-50 ring-1 ring-inset ring-amber-200' }}">
            <svg viewBox="0 0 20 20" fill="currentColor" class="size-5 shrink-0 {{ $expired ? 'text-red-500' : 'text-amber-500' }}">
                <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495ZM10 5a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0v-3.5A.75.75 0 0 1 10 5Zm0 9a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd"/>
            </svg>
            <span class="{{ $expired ? 'text-red-800' : 'text-amber-800' }}">
                <strong>{{ $doc->name }}</strong>
                @if($expired)
                    venció hace {{ abs($daysLeft) }} {{ abs($daysLeft) === 1 ? 'día' : 'días' }}
                @else
                    vence en {{ $daysLeft }} {{ $daysLeft === 1 ? 'día' : 'días' }} ({{ $doc->expires_at->format('d/m/Y') }})
                @endif
                — {{ \App\Models\VaultDocument::$categories[$doc->category] ?? $doc->category }}
            </span>
            <a href="{{ route('documentos.index') }}" class="ml-auto shrink-0 text-xs font-medium underline {{ $expired ? 'text-red-700' : 'text-amber-700' }}">
                Ir a Documentos →
            </a>
        </div>
        @endforeach
    </div>
    @endif

    {{-- ── Active offers ─────────────────────────────────────────────── --}}
    <div class="mb-8">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-semibold text-gray-900">Ofertas activas</h2>
            <a href="{{ route('ofertas.index') }}" class="text-sm text-blue-600 hover:underline">Ver todas →</a>
        </div>

        @if($activeOffers->isEmpty())
        <div class="rounded-xl border border-dashed border-gray-300 bg-white px-6 py-8 text-center">
            <p class="text-sm text-gray-500">Sin ofertas en preparación.</p>
            <a href="{{ route('ofertas.create') }}"
               class="mt-3 inline-flex items-center gap-x-1 text-sm font-medium text-blue-600 hover:text-blue-500">
                <svg viewBox="0 0 16 16" fill="currentColor" class="size-4"><path d="M8.75 3.75a.75.75 0 0 0-1.5 0v3.5h-3.5a.75.75 0 0 0 0 1.5h3.5v3.5a.75.75 0 0 0 1.5 0v-3.5h3.5a.75.75 0 0 0 0-1.5h-3.5v-3.5Z"/></svg>
                Nueva oferta
            </a>
        </div>
        @else
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($activeOffers as $oferta)
            @php $dias = $oferta->diasRestantes(); @endphp
            <div class="relative flex flex-col rounded-xl border border-gray-200 bg-white p-5 shadow-xs hover:shadow-sm transition-shadow">
                <div class="flex items-start justify-between gap-x-2 mb-3">
                    <span class="rounded-md px-2 py-0.5 text-xs font-medium {{ \App\Models\Offer::$estadoColors[$oferta->estado] ?? 'bg-gray-100 text-gray-700' }}">
                        {{ \App\Models\Offer::$estados[$oferta->estado] ?? $oferta->estado }}
                    </span>
                    @if($oferta->fecha_limite)
                    <span class="text-xs font-medium {{ $oferta->deadlineColor() }}">
                        @if($dias === null) —
                        @elseif($dias < 0) Vencida
                        @elseif($dias === 0) Hoy
                        @else {{ $dias }}d
                        @endif
                    </span>
                    @endif
                </div>
                <h3 class="text-sm font-semibold text-gray-900 line-clamp-2 flex-1">{{ $oferta->proceso_nombre }}</h3>
                @if($oferta->entidad_nombre)
                <p class="mt-1 text-xs text-gray-500 truncate">{{ $oferta->entidad_nombre }}</p>
                @endif
                @if($oferta->fecha_limite)
                <p class="mt-1 text-xs {{ $oferta->deadlineColor() }}">
                    Vence {{ $oferta->fecha_limite->format('d/m/Y') }}
                </p>
                @endif
                <a href="{{ route('ofertas.show', $oferta) }}"
                   class="mt-4 inline-flex items-center gap-x-1 text-xs font-semibold text-blue-600 hover:text-blue-500">
                    Abrir espacio de trabajo →
                </a>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    {{-- ── Upcoming deadlines ────────────────────────────────────────── --}}
    @if($upcomingEvents->isNotEmpty())
    <div class="mb-8">
        <h2 class="text-sm font-semibold text-gray-900 mb-3">Próximas fechas clave</h2>
        <div class="rounded-xl border border-gray-200 bg-white shadow-xs overflow-hidden">
            <ul class="divide-y divide-gray-100">
                @foreach($upcomingEvents as $event)
                @php
                    $daysUntil = (int) now()->diffInDays($event->event_date, false);
                    $urgentClass = $daysUntil <= 3 ? 'text-red-600' : ($daysUntil <= 7 ? 'text-amber-600' : 'text-gray-700');
                    $typeLabels = [
                        'visita_campo' => 'Visita de campo',
                        'aclaraciones_deadline' => 'Aclaraciones',
                        'entrega_oferta' => 'Entrega oferta',
                        'apertura_sobres' => 'Apertura sobres',
                        'adjudicacion_estimada' => 'Adjudicación',
                        'custom' => 'Evento',
                    ];
                @endphp
                <li class="flex items-center gap-x-4 px-5 py-3">
                    <div class="flex flex-col items-center shrink-0 w-12">
                        <span class="text-xs font-medium {{ $urgentClass }}">{{ $event->event_date->format('d') }}</span>
                        <span class="text-xs text-gray-400 uppercase">{{ $event->event_date->translatedFormat('M') }}</span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-gray-900">{{ $typeLabels[$event->event_type] ?? $event->event_type }}</p>
                        <p class="text-xs text-gray-500 truncate">{{ $event->offer->proceso_nombre }}</p>
                    </div>
                    <span class="shrink-0 text-xs font-medium {{ $urgentClass }}">
                        @if($daysUntil === 0) Hoy
                        @elseif($daysUntil === 1) Mañana
                        @else {{ $daysUntil }} días
                        @endif
                    </span>
                    <a href="{{ route('ofertas.show', $event->offer_id) }}?tab=cronograma" class="shrink-0 text-xs text-blue-600 hover:underline">Ver →</a>
                </li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif

    {{-- ── Main grid: Convocatorias + Sondeo/Bóveda ─────────────────── --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

        {{-- Convocatorias recientes (2/3 width) --}}
        <div class="lg:col-span-2">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-sm font-semibold text-gray-900">Convocatorias recientes</h2>
                <div class="flex items-center gap-x-3 text-xs text-gray-500">
                    <span>{{ number_format($bidStats['total']) }} total · {{ number_format($bidStats['this_week']) }} esta semana</span>
                    <a href="{{ route('convocatorias.index') }}" class="font-medium text-blue-600 hover:underline">Ver todas →</a>
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white shadow-xs overflow-hidden">
                @php $bidList = $showAll ? $bids : $recentBids; @endphp
                @if(!$bidList || $bidList->isEmpty())
                <div class="px-6 py-10 text-center">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="mx-auto size-10 text-gray-400">
                        <path d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m6.75 12H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <p class="mt-2 text-sm text-gray-500">Sin convocatorias. Ejecuta el sondeo para comenzar.</p>
                </div>
                @else
                <ul class="divide-y divide-gray-100">
                    @foreach($bidList as $bid)
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
                    @endphp
                    <li class="flex items-start justify-between gap-x-4 px-5 py-4">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-start gap-x-2">
                                <p class="text-sm font-semibold text-gray-900 line-clamp-1">{{ $bid->title }}</p>
                                <span class="mt-0.5 shrink-0 rounded-md px-1.5 py-0.5 text-xs font-medium ring-1 ring-inset {{ $statusStyle }}">
                                    {{ $bid->status ?? 'N/D' }}
                                </span>
                            </div>
                            <div class="mt-0.5 flex flex-wrap items-center gap-x-2 text-xs text-gray-500">
                                <span class="truncate max-w-[200px]">{{ $bid->buyer_name }}</span>
                                @if($bid->tender_deadline)
                                <span class="text-gray-400">·</span>
                                <span>Cierre {{ $bid->tender_deadline->format('d/m/Y') }}</span>
                                @endif
                                @if(!empty($bid->matched_rubros))
                                <span class="text-gray-400">·</span>
                                @foreach($bid->matched_rubros as $rubro)
                                @php $code = is_array($rubro) ? ($rubro['code'] ?? $rubro) : $rubro; @endphp
                                <span class="font-mono text-blue-600">{{ $code }}</span>
                                @endforeach
                                @endif
                            </div>
                        </div>
                        <div class="shrink-0 flex flex-col items-end gap-y-1">
                            @if($bid->amount_estimated && $bid->amount_estimated > 0)
                            <span class="text-xs font-semibold text-gray-900">
                                {{ $bid->currency === 'USD' ? 'US$' : 'RD$' }}{{ number_format($bid->amount_estimated, 0, '.', ',') }}
                            </span>
                            @endif
                            <a href="{{ $bid->secp_url }}" target="_blank" rel="noopener"
                               class="text-xs text-blue-600 hover:underline">Ver en SECP →</a>
                        </div>
                    </li>
                    @endforeach
                </ul>
                @if($showAll && $bids->hasPages())
                <div class="border-t border-gray-100 px-5 py-3">
                    {{ $bids->appends(['ver' => '1'])->links('components.pagination') }}
                </div>
                @endif
                @endif
            </div>
        </div>

        {{-- Right column: Sondeo + Bóveda (1/3 width) --}}
        <div class="space-y-6">

            {{-- Sondeo status --}}
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-xs">
                <h2 class="text-sm font-semibold text-gray-900 mb-4">Estado del sondeo</h2>

                <dl class="space-y-3 text-sm">
                    <div>
                        <dt class="text-xs text-gray-500">Último sondeo</dt>
                        <dd class="mt-0.5 font-medium text-gray-900">
                            @if($lastPolledAt)
                                {{ \Carbon\Carbon::parse($lastPolledAt)->format('d/m/Y H:i') }}
                                <span class="text-xs text-gray-400 font-normal">({{ \Carbon\Carbon::parse($lastPolledAt)->diffForHumans() }})</span>
                            @else
                                <span class="text-yellow-600">Sin sondear</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500">Próximo sondeo</dt>
                        <dd class="mt-0.5 font-medium text-gray-900">
                            @if($lastPolledAt)
                                @php $next = \Carbon\Carbon::parse($lastPolledAt)->addMinutes($pollIntervalMins); @endphp
                                {{ $next->format('d/m/Y H:i') }}
                                @if($next->isFuture())
                                <span class="text-xs text-gray-400 font-normal">(en {{ $next->diffForHumans(null, true) }})</span>
                                @else
                                <span class="text-xs text-amber-600 font-normal">(pendiente)</span>
                                @endif
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500">Intervalo</dt>
                        <dd class="mt-0.5 font-medium text-gray-900">{{ $pollIntervalMins }} minutos</dd>
                    </div>
                    @if($bidStats['unnotified'] > 0)
                    <div>
                        <dt class="text-xs text-gray-500">Sin notificar</dt>
                        <dd class="mt-0.5">
                            <span class="rounded-md bg-amber-50 px-2 py-0.5 text-xs font-semibold text-amber-700 ring-1 ring-inset ring-amber-600/20">
                                {{ $bidStats['unnotified'] }} convocatorias
                            </span>
                        </dd>
                    </div>
                    @endif
                </dl>

                <form method="POST" action="{{ route('poll.manual') }}" class="mt-5">
                    @csrf
                    <button type="submit"
                            class="w-full inline-flex items-center justify-center gap-x-2 rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-xs hover:bg-blue-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                        <svg viewBox="0 0 20 20" fill="currentColor" class="-ml-0.5 size-4">
                            <path fill-rule="evenodd" d="M15.312 11.424a5.5 5.5 0 0 1-9.201 2.466l-.312-.311h2.433a.75.75 0 0 0 0-1.5H3.989a.75.75 0 0 0-.75.75v4.242a.75.75 0 0 0 1.5 0v-2.43l.31.31a7 7 0 0 0 11.712-3.138.75.75 0 0 0-1.449-.39Zm1.23-3.723a.75.75 0 0 0 .219-.53V2.929a.75.75 0 0 0-1.5 0V5.36l-.31-.31A7 7 0 0 0 3.239 8.188a.75.75 0 1 0 1.448.389A5.5 5.5 0 0 1 13.89 6.11l.311.31h-2.432a.75.75 0 0 0 0 1.5h4.243a.75.75 0 0 0 .53-.219Z" clip-rule="evenodd"/>
                        </svg>
                        Sondear ahora
                    </button>
                </form>
            </div>

            {{-- Bóveda summary --}}
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-xs">
                <h2 class="text-sm font-semibold text-gray-900 mb-4">Resumen de bóveda</h2>
                <dl class="grid grid-cols-2 gap-3">
                    <a href="{{ route('personal.index') }}" class="rounded-lg bg-gray-50 p-3 hover:bg-gray-100 transition-colors">
                        <dt class="text-xs text-gray-500">Personal activo</dt>
                        <dd class="mt-1 text-2xl font-semibold text-gray-900">{{ $vaultStats['personnel'] }}</dd>
                    </a>
                    <a href="{{ route('proyectos.index') }}" class="rounded-lg bg-gray-50 p-3 hover:bg-gray-100 transition-colors">
                        <dt class="text-xs text-gray-500">Proyectos</dt>
                        <dd class="mt-1 text-2xl font-semibold text-gray-900">{{ $vaultStats['projects'] }}</dd>
                    </a>
                    <a href="{{ route('documentos.index') }}" class="rounded-lg bg-gray-50 p-3 hover:bg-gray-100 transition-colors">
                        <dt class="text-xs text-gray-500">Documentos</dt>
                        <dd class="mt-1 text-2xl font-semibold text-gray-900">{{ $vaultStats['documents'] }}</dd>
                    </a>
                    <a href="{{ route('financiero.index') }}" class="rounded-lg bg-gray-50 p-3 hover:bg-gray-100 transition-colors">
                        <dt class="text-xs text-gray-500">Años fiscales</dt>
                        <dd class="mt-1 text-2xl font-semibold text-gray-900">{{ $vaultStats['financials'] }}</dd>
                    </a>
                </dl>
            </div>

        </div>
    </div>

</div>
@endsection
