@extends('layouts.app')
@section('title', ($oferta->proceso_codigo ? $oferta->proceso_codigo . ' — ' : '') . $oferta->proceso_nombre)

@section('content')
<div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">

    {{-- ── Header ──────────────────────────────────────────────────────── --}}
    <div class="mb-6 flex items-start justify-between gap-x-4">
        <div class="min-w-0">
            <a href="{{ route('ofertas.index') }}" class="text-sm text-blue-600 hover:underline">← Ofertas</a>
            <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1">
                <h1 class="text-base font-semibold text-gray-900 truncate">{{ $oferta->proceso_nombre }}</h1>
                <span class="rounded-md px-2.5 py-1 text-xs font-medium {{ \App\Models\Offer::$estadoColors[$oferta->estado] ?? 'bg-gray-100 text-gray-700' }}">
                    {{ \App\Models\Offer::$estados[$oferta->estado] ?? $oferta->estado }}
                </span>
            </div>
            <div class="mt-1 flex flex-wrap items-center gap-x-3 text-sm text-gray-500">
                @if($oferta->proceso_codigo)
                    <span class="font-mono text-xs">{{ $oferta->proceso_codigo }}</span>
                @endif
                @if($oferta->entidad_nombre)
                    <span>{{ $oferta->entidad_nombre }}</span>
                @endif
                @if($oferta->fecha_limite)
                    @php $dias = $oferta->diasRestantes(); @endphp
                    <span class="{{ $oferta->deadlineColor() }} text-xs font-medium">
                        Vence {{ $oferta->fecha_limite->format('d/m/Y') }}
                        @if($dias !== null)
                            @if($dias < 0) (vencida) @elseif($dias === 0) (hoy) @else ({{ $dias }}d) @endif
                        @endif
                    </span>
                    <a href="{{ route('calendar.offer', $oferta) }}"
                       class="inline-flex items-center rounded bg-gray-100 px-1.5 py-0.5 text-xs font-medium text-gray-600 hover:bg-blue-100 hover:text-blue-700 transition-colors"
                       title="Agregar al calendario">
                        <svg class="size-3.5 mr-0.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/>
                        </svg>
                        .ics
                    </a>
                @endif
            </div>
        </div>
    </div>

    {{-- ── Tab nav ──────────────────────────────────────────────────────── --}}
    @php
        $tabs = array_merge(
            $oferta->bid ? ['resumen' => 'Resumen'] : [],
            [
                'pliego'      => 'Pliego',
                'documentos'  => 'Documentos del proceso',
                'checklist'   => 'Checklist',
                'composicion' => 'Composición',
                'formularios' => 'Formularios',
                'cronograma'  => 'Cronograma',
                'ensamblar'   => 'Ensamblar',
            ]
        );
    @endphp
    <div class="border-b border-gray-200 mb-8">
        <nav class="-mb-px flex gap-x-6 overflow-x-auto" aria-label="Tabs">
            @foreach($tabs as $key => $label)
            <a href="{{ route('ofertas.show', [$oferta, 'tab' => $key]) }}"
               class="whitespace-nowrap border-b-2 py-3 text-sm font-medium {{ $tab === $key ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}">
                {{ $label }}
                @if($key === 'checklist' && $oferta->activeRequirements->count() > 0)
                    <span class="ml-1.5 rounded-full bg-gray-100 px-1.5 py-0.5 text-xs text-gray-600">{{ $oferta->activeRequirements->count() }}</span>
                @endif
            </a>
            @endforeach
        </nav>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    {{-- TAB: RESUMEN (overview general de la convocatoria vinculada)        --}}
    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    @if($tab === 'resumen' && $oferta->bid)
    @php
        $bid = $oferta->bid;
        $bidStatus = strtoupper($bid->status ?? '');
        $bidStatusStyle = match(true) {
            str_contains($bidStatus, 'PUBLICAD')  => 'bg-green-50 text-green-700 ring-green-600/20',
            str_contains($bidStatus, 'ADJUDIC')   => 'bg-gray-100 text-gray-600 ring-gray-500/10',
            str_contains($bidStatus, 'CANCEL')    => 'bg-red-50 text-red-700 ring-red-600/20',
            str_contains($bidStatus, 'DESIERTO')  => 'bg-yellow-50 text-yellow-800 ring-yellow-600/20',
            str_contains($bidStatus, 'CERRADA')   => 'bg-yellow-50 text-yellow-800 ring-yellow-600/20',
            str_contains($bidStatus, 'ABIERTO')   => 'bg-blue-50 text-blue-700 ring-blue-600/20',
            str_contains($bidStatus, 'APERTURAD') => 'bg-blue-50 text-blue-700 ring-blue-600/20',
            str_contains($bidStatus, 'EVALUAC')   => 'bg-blue-50 text-blue-700 ring-blue-600/20',
            default                               => 'bg-gray-100 text-gray-600 ring-gray-500/10',
        };
        $articles = $bid->cached_articles ?? [];
    @endphp
    <div class="space-y-6">

        {{-- Información general --}}
        <div class="rounded-xl border border-gray-200 bg-white">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-200 px-6 py-4">
                <div class="min-w-0">
                    <h2 class="text-sm font-semibold text-gray-900">Información general</h2>
                    <p class="mt-0.5 truncate text-xs text-gray-500">{{ $bid->title }}</p>
                </div>
                <div class="flex shrink-0 items-center gap-x-2">
                    <span class="rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset {{ $bidStatusStyle }}">{{ $bid->status ?? 'N/D' }}</span>
                    @if($bid->mipymes)
                    <span class="rounded-md bg-teal-50 px-2 py-1 text-xs font-medium text-teal-700 ring-1 ring-inset ring-teal-600/20">MIPYME</span>
                    @endif
                    @if($bid->mipymes_mujeres)
                    <span class="rounded-md bg-pink-50 px-2 py-1 text-xs font-medium text-pink-700 ring-1 ring-inset ring-pink-600/20">MIPYME Mujeres</span>
                    @endif
                </div>
            </div>
            <dl class="grid grid-cols-1 gap-x-6 gap-y-4 px-6 py-5 sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <dt class="text-xs font-medium text-gray-500">Código de proceso</dt>
                    <dd class="mt-0.5 font-mono text-sm text-gray-900">{{ $bid->process_code }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-500">Entidad contratante</dt>
                    <dd class="mt-0.5 text-sm text-gray-900">{{ $bid->buyer_name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-500">Modalidad</dt>
                    <dd class="mt-0.5 text-sm text-gray-900">{{ $bid->procurement_method ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-500">Monto estimado</dt>
                    <dd class="mt-0.5 text-sm font-semibold text-gray-900">
                        @if($bid->amount_estimated && $bid->amount_estimated > 0)
                            {{ $bid->currency === 'USD' ? 'US$' : 'RD$' }}{{ number_format($bid->amount_estimated, 2, '.', ',') }}
                        @else
                            —
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-500">Publicación</dt>
                    <dd class="mt-0.5 text-sm text-gray-900">{{ $bid->published_at?->format('d/m/Y h:i A') ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-500">Cierre de recepción</dt>
                    <dd class="mt-0.5 text-sm {{ $bid->tender_deadline?->isPast() ? 'font-semibold text-red-600' : 'text-gray-900' }}">
                        {{ $bid->tender_deadline?->format('d/m/Y h:i A') ?? '—' }}
                        @if($bid->tender_deadline?->isPast())
                        <span class="ml-1 text-xs font-medium">(vencido)</span>
                        @endif
                    </dd>
                </div>
                @if($bidInstitution['objeto'] ?? null)
                <div>
                    <dt class="text-xs font-medium text-gray-500">Objeto</dt>
                    <dd class="mt-0.5 text-sm text-gray-900">{{ $bidInstitution['objeto'] }}</dd>
                </div>
                @endif
                @if($bidInstitution['duracion_contrato'] ?? null)
                <div>
                    <dt class="text-xs font-medium text-gray-500">Duración del contrato</dt>
                    <dd class="mt-0.5 text-sm text-gray-900">{{ $bidInstitution['duracion_contrato'] }}</dd>
                </div>
                @endif
                @if($bidInstitution['encargado'] ?? null)
                <div>
                    <dt class="text-xs font-medium text-gray-500">Encargado</dt>
                    <dd class="mt-0.5 text-sm text-gray-900">{{ $bidInstitution['encargado'] }}</dd>
                </div>
                @endif
                @if($bidInstitution['email'] ?? null)
                <div>
                    <dt class="text-xs font-medium text-gray-500">Correo de contacto</dt>
                    <dd class="mt-0.5 text-sm text-gray-900"><a href="mailto:{{ $bidInstitution['email'] }}" class="text-blue-600 hover:underline">{{ $bidInstitution['email'] }}</a></dd>
                </div>
                @endif
                @if($bidInstitution['telefono'] ?? null)
                <div>
                    <dt class="text-xs font-medium text-gray-500">Teléfono</dt>
                    <dd class="mt-0.5 text-sm text-gray-900">{{ $bidInstitution['telefono'] }}</dd>
                </div>
                @endif
            </dl>
            <div class="flex flex-wrap items-center gap-x-4 gap-y-2 border-t border-gray-100 px-6 py-3">
                <a href="{{ route('convocatorias.index', ['open' => $bid->id]) }}"
                   class="text-xs font-medium text-blue-600 hover:underline">Ver en Convocatorias →</a>
                @if($bid->secp_url)
                <a href="{{ $bid->secp_url }}" target="_blank" rel="noopener"
                   class="text-xs font-medium text-gray-500 hover:text-gray-700 hover:underline">Ver en portal DGCP ↗</a>
                @endif
            </div>
        </div>

        {{-- Cronograma del proceso --}}
        <div class="rounded-xl border border-gray-200 bg-white">
            <div class="border-b border-gray-200 px-6 py-4">
                <h2 class="text-sm font-semibold text-gray-900">Cronograma del proceso</h2>
                <p class="mt-0.5 text-xs text-gray-500">Hitos publicados por la institución. Para sus propios recordatorios use la pestaña Cronograma.</p>
            </div>
            @if(empty($bidCronograma))
            <p class="px-6 py-8 text-center text-sm text-gray-500">Sin fechas publicadas para este proceso.</p>
            @else
            <ol class="divide-y divide-gray-100">
                @foreach($bidCronograma as $ev)
                <li class="flex items-center gap-x-3 px-6 py-3">
                    <span class="inline-block size-2.5 shrink-0 rounded-full {{ $ev['is_past'] ? 'bg-gray-300' : 'bg-blue-500' }}"></span>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm {{ $ev['is_past'] ? 'text-gray-500' : 'font-medium text-gray-900' }}">{{ $ev['label'] }}</p>
                    </div>
                    <div class="flex shrink-0 items-center gap-x-2">
                        @if($ev['countdown'])
                        <span class="rounded-md bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700 ring-1 ring-inset ring-amber-600/20">{{ $ev['countdown'] }}</span>
                        @endif
                        <span class="text-xs {{ $ev['is_past'] ? 'text-gray-400' : 'text-gray-600' }}">{{ $ev['date'] }}</span>
                    </div>
                </li>
                @endforeach
            </ol>
            @endif
        </div>

        {{-- Artículos y lotes --}}
        <div class="rounded-xl border border-gray-200 bg-white">
            <div class="border-b border-gray-200 px-6 py-4">
                <h2 class="text-sm font-semibold text-gray-900">Artículos y lotes</h2>
                @if(!empty($articles))
                <p class="mt-0.5 text-xs text-gray-500">{{ count($articles) }} artículo(s) publicados en el proceso.</p>
                @endif
            </div>
            @if(empty($articles))
            <div class="px-6 py-8 text-center">
                <p class="text-sm text-gray-500">Sin artículos en caché para este proceso.</p>
                <a href="{{ route('convocatorias.index', ['open' => $bid->id]) }}"
                   class="mt-2 inline-flex text-xs font-medium text-blue-600 hover:underline">Consultar en Convocatorias →</a>
            </div>
            @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 bg-gray-50/60">
                            <th class="py-2.5 pl-6 pr-3 text-left text-xs font-semibold text-gray-600">UNSPSC</th>
                            <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-600">Descripción</th>
                            <th class="px-3 py-2.5 text-right text-xs font-semibold text-gray-600">Cant.</th>
                            <th class="px-3 py-2.5 text-right text-xs font-semibold text-gray-600">P. Unit.</th>
                            <th class="py-2.5 pl-3 pr-6 text-right text-xs font-semibold text-gray-600">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @php $articlesTotal = 0; @endphp
                        @foreach($articles as $item)
                        @php
                            $unspsc = collect([$item['familia'] ?? null, $item['clase'] ?? null, $item['subclase'] ?? null])->filter()->join('-');
                            $lineTotal = $item['precio_total_estimado'] ?? null;
                            if (is_numeric($lineTotal)) { $articlesTotal += (float) $lineTotal; }
                        @endphp
                        <tr>
                            <td class="py-2.5 pl-6 pr-3 font-mono text-xs text-gray-500">{{ $unspsc ?: '—' }}</td>
                            <td class="px-3 py-2.5 text-gray-700">{{ $item['descripcion_usuario'] ?? $item['descripcion_articulo'] ?? '—' }}</td>
                            <td class="px-3 py-2.5 text-right text-gray-700">{{ $item['cantidad'] ?? '—' }}</td>
                            <td class="px-3 py-2.5 text-right text-gray-700">{{ isset($item['precio_unitario_estimado']) && is_numeric($item['precio_unitario_estimado']) ? number_format((float) $item['precio_unitario_estimado'], 2, '.', ',') : '—' }}</td>
                            <td class="py-2.5 pl-3 pr-6 text-right font-medium text-gray-900">{{ is_numeric($lineTotal) ? number_format((float) $lineTotal, 2, '.', ',') : '—' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    @if($articlesTotal > 0)
                    <tfoot>
                        <tr class="border-t border-gray-200 bg-gray-50/60">
                            <td colspan="4" class="py-2.5 pl-6 pr-3 text-right text-xs font-semibold text-gray-600">Total estimado</td>
                            <td class="py-2.5 pl-3 pr-6 text-right text-sm font-semibold text-gray-900">{{ number_format($articlesTotal, 2, '.', ',') }}</td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
            @endif
        </div>

    </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    {{-- TAB: PLIEGO                                                         --}}
    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    @if($tab === 'pliego')
    <div class="space-y-6">

        {{-- Parse status (hide when still running — polling progress bar handles that) --}}
        @if($activeParse && !$activeParse->isPending())
        <div class="rounded-xl border border-gray-200 bg-white">
            <div class="border-b border-gray-200 px-6 py-4 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-gray-900">Estado del análisis</h2>
                <span class="rounded-md px-2.5 py-1 text-xs font-medium {{ $activeParse->statusColor() }}">
                    {{ $activeParse->statusLabel() }}
                </span>
            </div>
            <div class="px-6 py-5 space-y-4">
                @if($activeParse->bidDocument)
                <p class="text-xs text-gray-500">
                    <span class="font-medium text-gray-700">{{ $activeParse->bidDocument->original_filename }}</span>
                    @if($activeParse->bidDocument->file_size_bytes)
                     · {{ number_format($activeParse->bidDocument->file_size_bytes / 1024, 0) }} KB
                    @endif
                     · subido {{ $activeParse->bidDocument->downloaded_at?->diffForHumans() }}
                </p>
                @endif

                @if($activeParse->confidence_score !== null)
                <div>
                    <div class="flex items-center justify-between text-xs text-gray-600 mb-1.5">
                        <span>Confianza de extracción</span>
                        <span class="font-semibold {{ $activeParse->confidence_score >= 70 ? 'text-green-700' : ($activeParse->confidence_score >= 40 ? 'text-amber-700' : 'text-red-700') }}">
                            {{ $activeParse->confidence_score }}%
                        </span>
                    </div>
                    <div class="h-2 rounded-full bg-gray-100 overflow-hidden">
                        <div class="h-2 rounded-full {{ $activeParse->confidence_score >= 70 ? 'bg-green-500' : ($activeParse->confidence_score >= 40 ? 'bg-amber-500' : 'bg-red-500') }}"
                             style="width: {{ $activeParse->confidence_score }}%"></div>
                    </div>
                </div>
                @endif

                @if($activeParse->failure_reason)
                <div class="rounded-md bg-red-50 p-3 text-xs text-red-700 ring-1 ring-inset ring-red-200">
                    <strong>Error:</strong> {{ $activeParse->failure_reason }}
                </div>
                @endif

                @if($activeParse->isVerified())
                <p class="text-xs text-green-700 font-medium">
                    ✓ Verificado manualmente el {{ $activeParse->human_verified_at?->format('d/m/Y H:i') }}
                </p>
                @endif

                @if($activeParse->needsReview())
                <div class="rounded-md bg-amber-50 p-3 text-xs text-amber-800 ring-1 ring-inset ring-amber-200">
                    Revisar los requisitos extraídos en la pestaña <strong>Checklist</strong> antes de verificar.
                </div>
                @endif

                @if($oferta->isEditable())
                <div class="flex items-center gap-x-3 pt-1">
                    @if($activeParse->needsReview())
                    <form method="POST" action="{{ route('ofertas.parse.verify', [$oferta, $activeParse]) }}">
                        @csrf
                        <button type="submit"
                                class="rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white hover:bg-green-500">
                            Verificar extracción
                        </button>
                    </form>
                    @endif
                    <form method="POST" action="{{ route('ofertas.parse', $oferta) }}"
                          onsubmit="return confirm('¿Re-analizar el pliego? Si hay una verificación activa, se perderá.')">
                        @csrf
                        <button type="submit"
                                class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                            Re-analizar
                        </button>
                    </form>
                </div>
                @endif
            </div>
        </div>

        {{-- Parse history (if more than 1 attempt) --}}
        @if($oferta->parseAttempts->count() > 1)
        <div class="rounded-xl border border-gray-200 bg-white">
            <div class="border-b border-gray-200 px-6 py-4">
                <h2 class="text-sm font-semibold text-gray-900">Historial de análisis</h2>
            </div>
            <div class="divide-y divide-gray-100">
                @foreach($oferta->parseAttempts as $attempt)
                @if($loop->first) @continue @endif
                <div class="flex items-center justify-between px-6 py-3">
                    <div class="flex items-center gap-x-3">
                        <span class="rounded-md px-2 py-0.5 text-xs font-medium {{ $attempt->statusColor() }}">{{ $attempt->statusLabel() }}</span>
                        @if($attempt->confidence_score !== null)
                        <span class="text-xs text-gray-500">{{ $attempt->confidence_score }}% confianza</span>
                        @endif
                    </div>
                    <span class="text-xs text-gray-400">{{ $attempt->created_at->format('d/m/Y H:i') }}</span>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        @else
        {{-- No parse attempts yet --}}
        <div class="rounded-xl border border-dashed border-gray-300 bg-white p-10 text-center">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="mx-auto size-12 text-gray-400">
                <path d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <h3 class="mt-3 text-sm font-semibold text-gray-900">Sin pliego analizado</h3>
            <p class="mt-1 text-sm text-gray-500">Sube el pliego de condiciones y la IA extraerá los requisitos automáticamente.</p>
        </div>
        @endif

        {{-- Fetch pliego from API --}}
        @if($oferta->isEditable() && $oferta->proceso_codigo)
        <div x-data="{
            docs: [],
            loading: false,
            loaded: false,
            parsing: false,
            step: 0,
            steps: [
                'Enviando solicitud...',
                'Descargando PDF del portal...',
                'Analizando con IA...',
                'Analizando pliego de condiciones...',
                'Extrayendo requisitos...',
                'Procesando resultados...',
            ],
            progress: 0,
            failed: false,
            failReason: '',
            pollTimer: null,
            stepTimer: null,
            init() {
                if ({{ ($activeParse && $activeParse->isPending()) ? 'true' : 'false' }}) {
                    this.startPolling(3);
                }
            },
            startPolling(initialStep = 0) {
                this.parsing = true;
                this.step = initialStep;
                this.progress = Math.max(5, initialStep / this.steps.length * 85);
                this.failed = false;
                this.failReason = '';
                this.stepTimer = setInterval(() => {
                    if (this.step < this.steps.length - 1) {
                        this.step++;
                        this.progress = Math.min(90, 5 + (this.step / this.steps.length) * 85);
                    }
                }, 8000);
                this.pollTimer = setInterval(async () => {
                    try {
                        const sr = await fetch('{{ route('ofertas.parse-status', $oferta) }}');
                        const data = await sr.json();
                        if (data.status === 'pending' || data.status === 'running') {
                            this.step = Math.max(this.step, data.status === 'running' ? 3 : 1);
                            this.progress = Math.max(this.progress, data.status === 'running' ? 50 : 15);
                        } else if (data.status === 'parsed' || data.status === 'needs_review' || data.status === 'verified') {
                            this.progress = 100;
                            clearInterval(this.pollTimer);
                            clearInterval(this.stepTimer);
                            setTimeout(() => window.location.reload(), 600);
                        } else if (data.status === 'failed') {
                            clearInterval(this.pollTimer);
                            clearInterval(this.stepTimer);
                            this.failed = true;
                            this.failReason = data.failure_reason || 'Error desconocido';
                            this.parsing = false;
                        }
                    } catch (e) { /* keep polling */ }
                }, 3000);
            },
            stopPolling() {
                clearInterval(this.pollTimer);
                clearInterval(this.stepTimer);
            },
            async fetchDocs() {
                this.loading = true;
                try {
                    const res = await fetch('{{ route('ofertas.api-docs', $oferta) }}');
                    const json = await res.json();
                    this.docs = json.docs || [];
                } catch (e) {
                    console.error(e);
                }
                this.loading = false;
                this.loaded = true;
            },
            async parseDoc(url, filename) {
                if (this.parsing) return;
                try {
                    const res = await fetch('{{ route('ofertas.parse-from-api', $oferta) }}', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}'},
                        body: JSON.stringify({ url, filename })
                    });
                    if (!res.ok) throw new Error('Error al iniciar análisis');
                    this.startPolling(0);
                } catch (e) {
                    this.failed = true;
                    this.failReason = e.message;
                    this.parsing = false;
                }
            }
        }" class="rounded-xl border border-gray-200 bg-white">
            <div class="border-b border-gray-200 px-6 py-4 flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-semibold text-gray-900">Documentos del proceso</h2>
                    <p class="mt-0.5 text-xs text-gray-500">Selecciona el pliego de la API para analizarlo con IA.</p>
                </div>
                <button @click="fetchDocs()" x-show="!loaded && !parsing" :disabled="loading"
                        class="rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-500 disabled:opacity-50">
                    <span x-show="!loading">Cargar documentos</span>
                    <span x-show="loading" x-cloak>Cargando...</span>
                </button>
            </div>

            {{-- Progress bar --}}
            <template x-if="parsing">
                <div class="px-6 py-6">
                    <div class="flex items-center gap-3 mb-3">
                        <svg class="animate-spin size-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="text-sm font-medium text-gray-900" x-text="steps[step]"></span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2.5 overflow-hidden">
                        <div class="bg-blue-600 h-2.5 rounded-full transition-all duration-700 ease-out"
                             :style="'width: ' + progress + '%'"></div>
                    </div>
                    <p class="mt-2 text-xs text-gray-500">Esto puede tomar hasta un minuto...</p>
                </div>
            </template>

            {{-- Error state --}}
            <template x-if="failed">
                <div class="px-6 py-4">
                    <div class="rounded-lg bg-red-50 p-4">
                        <div class="flex">
                            <svg class="size-5 text-red-400 shrink-0" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd"/>
                            </svg>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-red-800" x-text="failReason"></p>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            {{-- Document list --}}
            <template x-if="loaded && !parsing">
                <div class="px-6 py-4">
                    <template x-if="docs.length === 0">
                        <p class="text-sm text-gray-500">No se encontraron documentos en la API para este proceso.</p>
                    </template>
                    <ul class="divide-y divide-gray-100">
                        <template x-for="(doc, i) in docs" :key="i">
                            <li class="flex items-center justify-between py-3 gap-x-4">
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium text-gray-900 truncate" x-text="doc.nombre_documento || doc.tipo_documento || 'Documento'"></p>
                                    <p class="text-xs text-gray-500" x-text="(doc.tipo_documento || '') + (doc.fecha_carga_archivo ? ' — ' + doc.fecha_carga_archivo : '')"></p>
                                </div>
                                <template x-if="doc.url_documento">
                                    <button @click="parseDoc(doc.url_documento, (doc.nombre_documento || 'pliego.pdf'))"
                                            class="shrink-0 rounded-md bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700 ring-1 ring-inset ring-blue-600/20 hover:bg-blue-100">
                                        Analizar
                                    </button>
                                </template>
                            </li>
                        </template>
                    </ul>
                </div>
            </template>
        </div>
        @endif

        {{-- Manual upload fallback --}}
        @if($oferta->isEditable())
        <div class="rounded-xl border border-gray-200 bg-white">
            <div class="border-b border-gray-200 px-6 py-4">
                <h2 class="text-sm font-semibold text-gray-900">{{ $activeParse ? 'Re-subir pliego manualmente' : 'Subir pliego manualmente' }}</h2>
                <p class="mt-0.5 text-xs text-gray-500">Si el documento no aparece en la API, sube el PDF directamente.</p>
            </div>
            <div class="px-6 py-5">
                <form method="POST" action="{{ route('ofertas.pliego.upload', $oferta) }}" enctype="multipart/form-data">
                    @csrf
                    <div class="flex flex-col gap-y-3 sm:flex-row sm:items-center sm:gap-x-4">
                        <input type="file" name="pliego" accept=".pdf" required
                               class="block w-full text-sm text-gray-500 file:mr-4 file:rounded-md file:border-0 file:bg-blue-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-blue-700 hover:file:bg-blue-100"/>
                        <button type="submit"
                                class="shrink-0 rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500">
                            Subir y analizar
                        </button>
                    </div>
                </form>
            </div>
        </div>
        @endif

    </div>
    @endif


    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    {{-- TAB: DOCUMENTOS DEL PROCESO                                         --}}
    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    @if($tab === 'documentos')
    <div x-data="{
        docs: [],
        loading: true,
        async init() {
            try {
                const res = await fetch('{{ route('ofertas.api-docs', $oferta) }}');
                const json = await res.json();
                this.docs = json.docs || [];
            } catch (e) {
                console.error(e);
            }
            this.loading = false;
        }
    }" class="space-y-6">
        <div class="rounded-xl border border-gray-200 bg-white">
            <div class="border-b border-gray-200 px-6 py-4">
                <h2 class="text-sm font-semibold text-gray-900">Documentos del proceso</h2>
                <p class="mt-0.5 text-xs text-gray-500">Documentos publicados por la institución contratante en el portal DGCP.</p>
            </div>

            <template x-if="loading">
                <div class="px-6 py-8 text-center">
                    <svg class="animate-spin size-5 text-gray-400 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <p class="mt-2 text-sm text-gray-500">Cargando documentos...</p>
                </div>
            </template>

            <template x-if="!loading && docs.length === 0">
                <div class="px-6 py-8 text-center">
                    <svg class="mx-auto size-10 text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
                    </svg>
                    <p class="mt-2 text-sm text-gray-500">No se encontraron documentos para este proceso.</p>
                </div>
            </template>

            <template x-if="!loading && docs.length > 0">
                <ul class="divide-y divide-gray-100">
                    <template x-for="(doc, i) in docs" :key="i">
                        <li class="flex items-center justify-between px-6 py-4 gap-x-4">
                            <div class="flex items-center gap-x-3 min-w-0 flex-1">
                                <svg class="size-8 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
                                </svg>
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-gray-900 truncate" x-text="doc.nombre_documento || doc.tipo_documento || 'Documento'"></p>
                                    <p class="text-xs text-gray-500">
                                        <span x-text="doc.tipo_documento || ''"></span>
                                        <span x-show="doc.fecha_carga_archivo" x-text="' · ' + doc.fecha_carga_archivo"></span>
                                    </p>
                                </div>
                            </div>
                            <template x-if="doc.url_documento">
                                <a :href="doc.url_documento" target="_blank" rel="noopener"
                                   class="shrink-0 inline-flex items-center gap-x-1.5 rounded-md bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                                    </svg>
                                    Descargar
                                </a>
                            </template>
                        </li>
                    </template>
                </ul>
            </template>
        </div>
    </div>
    @endif


    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    {{-- TAB: CHECKLIST                                                      --}}
    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    @if($tab === 'checklist')
    @php
        $metCount   = $oferta->activeRequirements->filter(fn($r) => in_array($r->estado, ['CUMPLE', 'ACEPTADO']))->count();
        $totalCount = $oferta->activeRequirements->count();
        $vaultDocs  = \App\Models\VaultDocument::where('company_id', $oferta->company_id)
                          ->where('is_current', true)->orderBy('name')->get();
    @endphp
    <div class="space-y-4">

        <div class="flex items-center justify-between">
            <div class="flex items-center gap-x-4">
                <span class="text-sm text-gray-700">
                    <span class="font-semibold text-gray-900">{{ $metCount }}/{{ $totalCount }}</span> requisitos cumplidos
                </span>
                @if($totalCount > 0)
                <div class="h-2 w-32 rounded-full bg-gray-100 overflow-hidden">
                    <div class="h-2 rounded-full bg-green-500 transition-all"
                         style="width: {{ $totalCount > 0 ? round($metCount / $totalCount * 100) : 0 }}%"></div>
                </div>
                @endif
            </div>
            @if($oferta->isEditable())
            <button command="show-modal" commandfor="add-req-drawer"
                    class="inline-flex items-center gap-x-1.5 rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-xs hover:bg-blue-500">
                <svg viewBox="0 0 20 20" fill="currentColor" class="-ml-0.5 size-4"><path d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z"/></svg>
                Agregar requisito
            </button>
            @endif
        </div>

        @if($oferta->activeRequirements->isEmpty())
        <div class="rounded-xl border border-dashed border-gray-300 bg-white p-10 text-center text-sm text-gray-500">
            Sin requisitos. Sube el pliego en la pestaña <strong>Pliego</strong> para que la IA los extraiga, o agrégalos manualmente.
        </div>
        @else
        <div class="overflow-hidden shadow-sm ring-1 ring-gray-900/5 rounded-xl">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="py-3.5 pl-4 pr-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 sm:pl-6">Tipo</th>
                        <th class="px-3 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Descripción</th>
                        <th class="px-3 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Estado</th>
                        <th class="px-3 py-3.5 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Documentos asignados</th>
                        @if($oferta->isEditable())
                        <th class="relative py-3.5 pl-3 pr-4 sm:pr-6"><span class="sr-only">Acciones</span></th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @foreach($oferta->activeRequirements as $req)
                    <tr>
                        <td class="py-4 pl-4 pr-3 sm:pl-6">
                            <span class="rounded-md bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700">
                                {{ \App\Models\OfferRequirement::$tipos[$req->tipo] ?? $req->tipo }}
                            </span>
                            @if($req->source === 'gemini')
                            <div class="mt-1 text-xs text-blue-500">IA</div>
                            @endif
                        </td>
                        <td class="px-3 py-4 text-sm text-gray-900 max-w-xs">
                            <div>{{ $req->descripcion }}</div>
                            @if($req->notes)
                            <div class="mt-0.5 text-xs text-gray-400">{{ $req->notes }}</div>
                            @endif
                        </td>
                        <td class="px-3 py-4">
                            <span class="rounded-md px-2 py-0.5 text-xs font-medium {{ $req->estadoColor() }}">
                                {{ $req->estado }}
                            </span>
                        </td>
                        <td class="px-3 py-4">
                            @if($req->items->isNotEmpty())
                            <div class="flex flex-wrap gap-1">
                                @foreach($req->items as $item)
                                <span class="inline-flex items-center gap-x-1 rounded-md bg-gray-50 px-2 py-0.5 text-xs text-gray-700 ring-1 ring-inset ring-gray-200">
                                    {{ $item->refLabel() }}
                                    @if($oferta->isEditable())
                                    <form method="POST" action="{{ route('ofertas.requirements.items.destroy', [$oferta, $req, $item]) }}" class="inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="ml-0.5 text-gray-400 hover:text-red-500 leading-none">×</button>
                                    </form>
                                    @endif
                                </span>
                                @endforeach
                            </div>
                            @else
                            <span class="text-xs text-gray-400">Sin asignar</span>
                            @endif
                        </td>
                        @if($oferta->isEditable())
                        <td class="py-4 pl-3 pr-4 text-right sm:pr-6">
                            <div class="flex items-center justify-end gap-x-2">
                                <button type="button" title="Asignar"
                                        onclick="openAssignItemDrawer({{ $req->id }}, {{ json_encode(mb_substr($req->descripcion, 0, 60)) }})"
                                        class="text-gray-400 hover:text-blue-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4">
                                        <path d="M12.232 4.232a2.5 2.5 0 0 1 3.536 3.536l-1.225 1.224a.75.75 0 0 0 1.061 1.06l1.224-1.224a4 4 0 0 0-5.656-5.656l-3 3a4 4 0 0 0 .225 5.865.75.75 0 0 0 .977-1.138 2.5 2.5 0 0 1-.142-3.667l3-3Z"/>
                                        <path d="M11.603 7.963a.75.75 0 0 0-.977 1.138 2.5 2.5 0 0 1 .142 3.667l-3 3a2.5 2.5 0 0 1-3.536-3.536l1.225-1.224a.75.75 0 0 0-1.061-1.06l-1.224 1.224a4 4 0 1 0 5.656 5.656l3-3a4 4 0 0 0-.225-5.865Z"/>
                                    </svg>
                                </button>
                                <button type="button" title="Editar"
                                        onclick="openEditReqDrawer({{ $req->id }}, {{ json_encode($req->descripcion) }}, {{ json_encode($req->tipo) }}, {{ json_encode($req->estado) }}, {{ json_encode($req->notes) }}, {{ json_encode($req->acceptance_reason) }})"
                                        class="text-gray-400 hover:text-gray-700">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4">
                                        <path d="m5.433 13.917 1.262-3.155A4 4 0 0 1 7.58 9.42l6.92-6.918a2.121 2.121 0 0 1 3 3l-6.92 6.918c-.383.383-.84.685-1.343.886l-3.154 1.262a.5.5 0 0 1-.65-.65Z"/>
                                        <path d="M3.5 5.75c0-.69.56-1.25 1.25-1.25H10A.75.75 0 0 0 10 3H4.75A2.75 2.75 0 0 0 2 5.75v9.5A2.75 2.75 0 0 0 4.75 18h9.5A2.75 2.75 0 0 0 17 15.25V10a.75.75 0 0 0-1.5 0v5.25c0 .69-.56 1.25-1.25 1.25h-9.5c-.69 0-1.25-.56-1.25-1.25v-9.5Z"/>
                                    </svg>
                                </button>
                                <form method="POST" action="{{ route('ofertas.requirements.destroy', [$oferta, $req]) }}"
                                      onsubmit="return confirm('¿Eliminar este requisito?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" title="Eliminar" class="text-gray-400 hover:text-red-500">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4">
                                            <path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 0 0 6 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 1 0 .23 1.482l.149-.022.841 10.518A2.75 2.75 0 0 0 7.596 19h4.807a2.75 2.75 0 0 0 2.742-2.53l.841-10.519.149.023a.75.75 0 0 0 .23-1.482A41.03 41.03 0 0 0 14 4.193V3.75A2.75 2.75 0 0 0 11.25 1h-2.5ZM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4ZM8.58 7.72a.75.75 0 0 0-1.5.06l.3 7.5a.75.75 0 1 0 1.5-.06l-.3-7.5Zm4.34.06a.75.75 0 1 0-1.5-.06l-.3 7.5a.75.75 0 1 0 1.5.06l.3-7.5Z" clip-rule="evenodd"/>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                        @endif
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

    </div>
    @endif


    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    {{-- TAB: COMPOSICIÓN                                                    --}}
    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    @if($tab === 'composicion')
    @php
        $inputCls = 'w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600';
    @endphp
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

        {{-- Personnel --}}
        <div class="rounded-xl border border-gray-200 bg-white">
            <div class="border-b border-gray-200 px-6 py-4">
                <h2 class="text-sm font-semibold text-gray-900">Personal</h2>
                <p class="mt-0.5 text-xs text-gray-500">{{ $oferta->personnel->count() }} asignados</p>
            </div>
            <div class="px-6 py-4 space-y-2">
                @forelse($oferta->personnel as $op)
                <div class="flex items-start justify-between gap-x-3 py-1">
                    <div>
                        <div class="text-sm font-medium text-gray-900">{{ $op->person?->nombre ?? '(eliminado)' }}</div>
                        @if($op->person?->cargo)<div class="text-xs text-gray-500">{{ $op->person->cargo }}</div>@endif
                        @if($op->role_note)<div class="text-xs text-blue-600">{{ $op->role_note }}</div>@endif
                    </div>
                    @if($oferta->isEditable())
                    <form method="POST" action="{{ route('ofertas.personnel.remove', [$oferta, $op]) }}" class="shrink-0">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-xs text-red-500 hover:text-red-700">Remover</button>
                    </form>
                    @endif
                </div>
                @empty
                <p class="py-2 text-xs text-gray-400">Sin personal asignado.</p>
                @endforelse

                @if($oferta->isEditable())
                <form method="POST" action="{{ route('ofertas.personnel.add', $oferta) }}" class="mt-3 space-y-2 border-t border-gray-100 pt-4">
                    @csrf
                    <select name="personnel_id" class="{{ $inputCls }}">
                        <option value="">— Seleccionar personal —</option>
                        @foreach($availablePersonnel as $p)
                        <option value="{{ $p->id }}">{{ $p->nombre }}{{ $p->cargo ? ' — ' . $p->cargo : '' }}</option>
                        @endforeach
                    </select>
                    <div class="flex gap-x-2">
                        <input type="text" name="role_note" placeholder="Rol en esta oferta"
                               class="flex-1 {{ $inputCls }}"/>
                        <button type="submit"
                                class="shrink-0 rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-500">
                            Agregar
                        </button>
                    </div>
                </form>
                @endif
            </div>
        </div>

        {{-- Projects --}}
        <div class="rounded-xl border border-gray-200 bg-white">
            <div class="border-b border-gray-200 px-6 py-4">
                <h2 class="text-sm font-semibold text-gray-900">Proyectos de referencia</h2>
                <p class="mt-0.5 text-xs text-gray-500">{{ $oferta->projects->count() }} asignados</p>
            </div>
            <div class="px-6 py-4 space-y-2">
                @forelse($oferta->projects as $op)
                <div class="flex items-start justify-between gap-x-3 py-1">
                    <div>
                        <div class="text-sm font-medium text-gray-900">{{ $op->project?->nombre ?? '(eliminado)' }}</div>
                        @if($op->project?->cliente)<div class="text-xs text-gray-500">{{ $op->project->cliente }}</div>@endif
                        @if($op->role_note)<div class="text-xs text-blue-600">{{ $op->role_note }}</div>@endif
                    </div>
                    @if($oferta->isEditable())
                    <form method="POST" action="{{ route('ofertas.projects.remove', [$oferta, $op]) }}" class="shrink-0">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-xs text-red-500 hover:text-red-700">Remover</button>
                    </form>
                    @endif
                </div>
                @empty
                <p class="py-2 text-xs text-gray-400">Sin proyectos asignados.</p>
                @endforelse

                @if($oferta->isEditable())
                <form method="POST" action="{{ route('ofertas.projects.add', $oferta) }}" class="mt-3 space-y-2 border-t border-gray-100 pt-4">
                    @csrf
                    <select name="project_id" class="{{ $inputCls }}">
                        <option value="">— Seleccionar proyecto —</option>
                        @foreach($availableProjects as $p)
                        <option value="{{ $p->id }}">{{ $p->nombre }}{{ $p->cliente ? ' — ' . $p->cliente : '' }}</option>
                        @endforeach
                    </select>
                    <div class="flex gap-x-2">
                        <input type="text" name="role_note" placeholder="Nota sobre este proyecto"
                               class="flex-1 {{ $inputCls }}"/>
                        <button type="submit"
                                class="shrink-0 rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-500">
                            Agregar
                        </button>
                    </div>
                </form>
                @endif
            </div>
        </div>

        {{-- Equipment --}}
        <div class="rounded-xl border border-gray-200 bg-white">
            <div class="border-b border-gray-200 px-6 py-4">
                <h2 class="text-sm font-semibold text-gray-900">Equipos</h2>
                <p class="mt-0.5 text-xs text-gray-500">{{ $oferta->equipment->count() }} asignados</p>
            </div>
            <div class="px-6 py-4 space-y-2">
                @forelse($oferta->equipment as $oe)
                <div class="flex items-start justify-between gap-x-3 py-1">
                    <div>
                        <div class="text-sm font-medium text-gray-900">
                            {{ $oe->equipment?->fichaLabel() ?: ($oe->equipment?->descripcion ?? '(eliminado)') }}
                        </div>
                        @if($oe->role_note)<div class="text-xs text-blue-600">{{ $oe->role_note }}</div>@endif
                    </div>
                    @if($oferta->isEditable())
                    <form method="POST" action="{{ route('ofertas.equipment.remove', [$oferta, $oe]) }}" class="shrink-0">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-xs text-red-500 hover:text-red-700">Remover</button>
                    </form>
                    @endif
                </div>
                @empty
                <p class="py-2 text-xs text-gray-400">Sin equipos asignados.</p>
                @endforelse

                @if($oferta->isEditable())
                <form method="POST" action="{{ route('ofertas.equipment.add', $oferta) }}" class="mt-3 space-y-2 border-t border-gray-100 pt-4">
                    @csrf
                    <div class="flex gap-x-2">
                        <select name="equipment_id" class="flex-1 {{ $inputCls }}">
                            <option value="">— Seleccionar equipo —</option>
                            @foreach($availableEquipment as $e)
                            <option value="{{ $e->id }}">{{ $e->fichaLabel() ?: $e->descripcion }}</option>
                            @endforeach
                        </select>
                        <button type="submit"
                                class="shrink-0 rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-500">
                            Agregar
                        </button>
                    </div>
                </form>
                @endif
            </div>
        </div>

        {{-- Financials --}}
        <div class="rounded-xl border border-gray-200 bg-white">
            <div class="border-b border-gray-200 px-6 py-4">
                <h2 class="text-sm font-semibold text-gray-900">Estados financieros</h2>
                <p class="mt-0.5 text-xs text-gray-500">{{ $oferta->financials->count() }} años asignados</p>
            </div>
            <div class="px-6 py-4 space-y-2">
                @forelse($oferta->financials as $of)
                <div class="flex items-start justify-between gap-x-3 py-1">
                    <div>
                        <div class="text-sm font-medium text-gray-900">
                            Año {{ $of->financialRecord?->anio_fiscal ?? '?' }}
                            @if($of->financialRecord?->currency)
                             — {{ $of->financialRecord->currency }}
                            @endif
                        </div>
                        @if($of->role_note)<div class="text-xs text-blue-600">{{ $of->role_note }}</div>@endif
                    </div>
                    @if($oferta->isEditable())
                    <form method="POST" action="{{ route('ofertas.financials.remove', [$oferta, $of]) }}" class="shrink-0">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-xs text-red-500 hover:text-red-700">Remover</button>
                    </form>
                    @endif
                </div>
                @empty
                <p class="py-2 text-xs text-gray-400">Sin años fiscales asignados.</p>
                @endforelse

                @if($oferta->isEditable())
                <form method="POST" action="{{ route('ofertas.financials.add', $oferta) }}" class="mt-3 space-y-2 border-t border-gray-100 pt-4">
                    @csrf
                    <div class="flex gap-x-2">
                        <select name="financial_record_id" class="flex-1 {{ $inputCls }}">
                            <option value="">— Seleccionar año fiscal —</option>
                            @foreach($availableFinancials as $fr)
                            <option value="{{ $fr->id }}">{{ $fr->anio_fiscal }} — {{ $fr->currency }}</option>
                            @endforeach
                        </select>
                        <button type="submit"
                                class="shrink-0 rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-500">
                            Agregar
                        </button>
                    </div>
                </form>
                @endif
            </div>
        </div>

    </div>
    @endif


    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    {{-- TAB: FORMULARIOS                                                    --}}
    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    @if($tab === 'formularios')
    @php
        $groups = [
            'Formularios de oferta' => ['CARATULA.A','CARATULA.B','SNCC.F.033','SNCC.F.034','SNCC.F.035','SNCC.F.036','SNCC.F.037','SNCC.F.040','SNCC.F.042','SNCC.F.047','SNCC.F.056','OFERTA.TECNICA'],
            'Documentos técnicos'   => ['SNCC.D.038','SNCC.D.043','SNCC.D.044','SNCC.D.045','SNCC.D.048','SNCC.D.049','SNCC.D.051','SNCC.D.052'],
            'Cartas'                => ['CARTA.ACEPTACION','CARTA.ENTREGA','CARTA.PAGO','CARTA.GARANTIA','CARTA.PRECIO'],
            'Aseguradoras / Fianzas'=> ['FIANZA.DCS','FIANZA.DCS.FC','FIANZA.APS'],
            'Declaraciones juradas' => ['DECL.JURADA','DECL.COMPROMISO_ETICO','DECL.INTEGRIDAD','DECL.CRONOGRAMA','DECL.GARANTIA','DECL.NATURALES'],
            'Formularios FL'        => ['FL.01','FL.02','FL.03','FL.04','FL.05','FL.06'],
            'Ley 47-25'            => ['LEY47.JURADA','LEY47.COLUSION','LEY47.JURIDICAS','LEY47.BENEFICIARIOS','LEY47.BENEFICIARIO.FORM','LEY47.DILIGENCIA','LEY47.SIG007'],
        ];
        $formLabels = \App\Models\OfferGeneratedFile::$forms;
    @endphp
    <div class="space-y-6">

        @if($oferta->isEditable())
        <div class="rounded-xl border border-gray-200 bg-white" x-data="ofertaFormGen()">
            <div class="border-b border-gray-200 px-6 py-4">
                <h2 class="text-sm font-semibold text-gray-900">Generar formulario</h2>
                <p class="mt-0.5 text-xs text-gray-500">Los datos de empresa y proceso se pre-completan desde la oferta.</p>
            </div>
            <div class="px-6 py-6">
                <form method="POST" action="{{ route('ofertas.generate.form', $oferta) }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-900">Formulario <span class="text-red-500">*</span></label>
                        <select name="form_code" x-model="formCode" required
                                class="mt-1.5 w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600">
                            <option value="">— Seleccionar —</option>
                            @foreach($groups as $groupLabel => $codes)
                            <optgroup label="{{ $groupLabel }}">
                                @foreach($codes as $code)
                                <option value="{{ $code }}">{{ $formLabels[$code] ?? $code }}</option>
                                @endforeach
                            </optgroup>
                            @endforeach
                        </select>
                    </div>

                    {{-- Personnel picker (D.045, D.048) --}}
                    <div x-show="needsPersonnel" x-cloak>
                        <label class="block text-sm font-medium text-gray-900">Personal <span class="text-red-500">*</span></label>
                        <select name="personnel_id" :required="needsPersonnel"
                                class="mt-1.5 w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600">
                            <option value="">— Seleccionar —</option>
                            @foreach($oferta->personnel as $op)
                            @if($op->person)
                            <option value="{{ $op->person->id }}">{{ $op->person->nombre }}</option>
                            @endif
                            @endforeach
                        </select>
                        @if($oferta->personnel->isEmpty())
                        <p class="mt-1 text-xs text-amber-600">Agrega personal en la pestaña Composición primero.</p>
                        @endif
                    </div>

                    {{-- Cargo propuesto (D.045 only) --}}
                    <div x-show="formCode === 'SNCC.D.045'" x-cloak>
                        <label class="block text-sm font-medium text-gray-900">Cargo propuesto</label>
                        <input type="text" name="cargo_propuesto" placeholder="ej. Gerente de proyecto"
                               class="mt-1.5 w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600"/>
                    </div>

                    {{-- Projects picker (D.049) --}}
                    <div x-show="needsProjects" x-cloak>
                        <label class="block text-sm font-medium text-gray-900">Proyectos de referencia</label>
                        @if($oferta->projects->isNotEmpty())
                        <div class="mt-2 space-y-1.5">
                            @foreach($oferta->projects as $op)
                            @if($op->project)
                            <label class="flex items-center gap-x-2">
                                <input type="checkbox" name="project_ids[]" value="{{ $op->project->id }}"
                                       class="size-4 rounded border-gray-300 text-blue-600 focus:ring-blue-600"/>
                                <span class="text-sm text-gray-700">{{ $op->project->nombre }}{{ $op->project->cliente ? ' — ' . $op->project->cliente : '' }}</span>
                            </label>
                            @endif
                            @endforeach
                        </div>
                        @else
                        <p class="mt-1 text-xs text-amber-600">Agrega proyectos en la pestaña Composición primero.</p>
                        @endif
                    </div>

                    <div class="flex justify-end pt-2">
                        <button type="submit" :disabled="!formCode"
                                class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500 disabled:opacity-40">
                            Generar
                        </button>
                    </div>
                </form>
            </div>
        </div>
        @endif

        {{-- Generated files --}}
        <div class="rounded-xl border border-gray-200 bg-white">
            <div class="border-b border-gray-200 px-6 py-4">
                <h2 class="text-sm font-semibold text-gray-900">Formularios generados</h2>
            </div>
            @if($oferta->generatedFiles->isEmpty())
            <div class="px-6 py-10 text-center text-sm text-gray-500">
                Sin formularios generados para esta oferta.
            </div>
            @else
            <div class="divide-y divide-gray-100">
                @foreach($oferta->generatedFiles as $file)
                <div class="flex items-center justify-between px-6 py-4">
                    <div>
                        <div class="text-sm font-medium text-gray-900">{{ $formLabels[$file->form_code] ?? $file->form_code }}</div>
                        <div class="mt-0.5 text-xs text-gray-500">
                            {{ $file->generated_at?->format('d/m/Y H:i') }} · {{ $file->fileSizeFormatted() }}
                            @if($file->supersedes_id)
                            <span class="ml-1 text-amber-600">· versión nueva</span>
                            @endif
                        </div>
                    </div>
                    <div class="flex items-center gap-x-2">
                        <a href="{{ route('ofertas.generated.view', [$oferta, $file]) }}" target="_blank" title="Ver PDF"
                           class="inline-flex items-center rounded-md bg-white px-2 py-1.5 text-sm text-gray-600 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4">
                                <path d="M10 12.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z"/>
                                <path fill-rule="evenodd" d="M.664 10.59a1.651 1.651 0 0 1 0-1.186A10.004 10.004 0 0 1 10 3c4.257 0 7.893 2.66 9.336 6.41.147.381.146.804 0 1.186A10.004 10.004 0 0 1 10 17c-4.257 0-7.893-2.66-9.336-6.41ZM14 10a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z" clip-rule="evenodd"/>
                            </svg>
                        </a>
                        <a href="{{ route('ofertas.generated.download', [$oferta, $file]) }}" title="Descargar DOCX"
                           class="inline-flex items-center rounded-md bg-white px-2 py-1.5 text-sm text-gray-600 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                            <svg viewBox="0 0 20 20" fill="currentColor" class="size-4">
                                <path d="M10.75 2.75a.75.75 0 0 0-1.5 0v8.614L6.295 8.235a.75.75 0 1 0-1.09 1.03l4.25 4.5a.75.75 0 0 0 1.09 0l4.25-4.5a.75.75 0 0 0-1.09-1.03l-2.955 3.129V2.75Z"/>
                                <path d="M3.5 12.75a.75.75 0 0 0-1.5 0v2.5A2.75 2.75 0 0 0 4.75 18h10.5A2.75 2.75 0 0 0 18 15.25v-2.5a.75.75 0 0 0-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5Z"/>
                            </svg>
                        </a>
                        <form method="POST" action="{{ route('ofertas.generated.destroy', [$oferta, $file]) }}"
                              onsubmit="return confirm('¿Eliminar este formulario generado?')">
                            @csrf @method('DELETE')
                            <button type="submit" title="Eliminar" class="inline-flex items-center rounded-md bg-white px-2 py-1.5 text-sm text-gray-400 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-red-50 hover:text-red-500">
                                <svg viewBox="0 0 20 20" fill="currentColor" class="size-4">
                                    <path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 0 0 6 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 1 0 .23 1.482l.149-.022.841 10.518A2.75 2.75 0 0 0 7.596 19h4.807a2.75 2.75 0 0 0 2.742-2.53l.841-10.519.149.023a.75.75 0 0 0 .23-1.482A41.03 41.03 0 0 0 14 4.193V3.75A2.75 2.75 0 0 0 11.25 1h-2.5ZM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4ZM8.58 7.72a.75.75 0 0 1 .7.8l-.35 5.25a.75.75 0 0 1-1.497-.1l.35-5.25a.75.75 0 0 1 .797-.7Zm3.64.7a.75.75 0 0 0-1.497.1l.35 5.25a.75.75 0 0 0 1.497-.1l-.35-5.25Z" clip-rule="evenodd"/>
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>

    </div>
    @endif


    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    {{-- TAB: CRONOGRAMA                                                     --}}
    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    @if($tab === 'cronograma')
    <div class="space-y-4">

        <div class="flex justify-end">
            @if($oferta->isEditable())
            <button command="show-modal" commandfor="add-event-drawer"
                    class="inline-flex items-center gap-x-1.5 rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-xs hover:bg-blue-500">
                <svg viewBox="0 0 20 20" fill="currentColor" class="-ml-0.5 size-4"><path d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z"/></svg>
                Agregar evento
            </button>
            @endif
        </div>

        @if($oferta->events->isEmpty())
        <div class="rounded-xl border border-dashed border-gray-300 bg-white p-10 text-center text-sm text-gray-500">
            Sin eventos en el cronograma. El análisis del pliego puede detectarlos automáticamente, o agrégalos manualmente.
        </div>
        @else
        <div class="rounded-xl border border-gray-200 bg-white overflow-hidden shadow-sm">
            <div class="divide-y divide-gray-100">
                @foreach($oferta->events->sortBy('event_date') as $event)
                @php
                    $daysUntil = $event->daysUntil();
                    $isPast    = $event->isPast();
                @endphp
                <div class="flex items-center justify-between px-6 py-4 {{ $isPast ? 'opacity-60' : '' }}">
                    <div class="flex items-start gap-x-4">
                        <div class="w-12 shrink-0 text-center">
                            <div class="text-2xl font-bold {{ $isPast ? 'text-gray-400' : 'text-gray-900' }} leading-none">
                                {{ $event->event_date?->format('d') }}
                            </div>
                            <div class="text-xs uppercase text-gray-400 mt-0.5">{{ $event->event_date?->format('M') }}</div>
                            <div class="text-xs text-gray-400">{{ $event->event_date?->format('Y') }}</div>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-gray-900">{{ $event->typeLabel() }}</div>
                            @if($event->description)
                            <div class="text-xs text-gray-500">{{ $event->description }}</div>
                            @endif
                            <div class="mt-1 flex items-center gap-x-2">
                                @if($isPast)
                                <span class="text-xs text-gray-400">Pasado · {{ $event->event_date?->format('H:i') }}</span>
                                @elseif($daysUntil !== null)
                                <span class="text-xs font-medium {{ $daysUntil <= 3 ? 'text-red-600' : ($daysUntil <= 7 ? 'text-amber-600' : 'text-gray-500') }}">
                                    {{ $daysUntil === 0 ? 'Hoy' : 'En ' . $daysUntil . ' días' }}
                                </span>
                                @endif
                                @if($event->alert_days_before > 0)
                                <span class="text-xs text-gray-400">· alerta {{ $event->alert_days_before }}d antes</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    @if($oferta->isEditable())
                    <div class="flex items-center gap-x-3 ml-4 shrink-0">
                        <button type="button"
                                onclick="openEditEventDrawer({{ $event->id }}, {{ json_encode($event->description) }}, '{{ $event->event_type }}', '{{ $event->event_date?->format('Y-m-d') }}', {{ $event->alert_days_before }}, '{{ $event->status }}')"
                                class="text-xs font-medium text-gray-600 hover:text-gray-900">
                            Editar
                        </button>
                        <form method="POST" action="{{ route('ofertas.events.destroy', [$oferta, $event]) }}"
                              onsubmit="return confirm('¿Eliminar este evento?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-xs font-medium text-red-500 hover:text-red-700">Eliminar</button>
                        </form>
                    </div>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
        @endif

    </div>
    @endif


    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    {{-- TAB: ENSAMBLAR                                                      --}}
    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    @if($tab === 'ensamblar')
    @php
        $hasParse   = $activeParse?->isVerified();
        $allMet     = $oferta->allRequirementsMet();
        $hasReqs    = $oferta->activeRequirements->count() > 0;
        $hasSnap    = $oferta->snapshots->isNotEmpty();
        $preflight  = [
            ['ok' => in_array($oferta->estado, ['en_preparacion', 'listo']), 'label' => 'Oferta en preparación o lista'],
            ['ok' => $hasParse,  'label' => 'Pliego analizado y verificado'],
            ['ok' => $hasReqs,   'label' => 'Al menos un requisito extraído'],
            ['ok' => $allMet,    'label' => 'Todos los requisitos en estado CUMPLE o ACEPTADO'],
        ];
    @endphp
    <div class="space-y-6">

        {{-- Pre-flight --}}
        <div class="rounded-xl border border-gray-200 bg-white">
            <div class="border-b border-gray-200 px-6 py-4">
                <h2 class="text-sm font-semibold text-gray-900">Condiciones para marcar como Listo</h2>
            </div>
            <div class="px-6 py-5 space-y-3">
                @foreach($preflight as $check)
                <div class="flex items-center gap-x-3">
                    @if($check['ok'])
                    <svg class="size-5 shrink-0 text-green-500" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd"/>
                    </svg>
                    @else
                    <svg class="size-5 shrink-0 text-gray-300" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16ZM8.28 7.22a.75.75 0 0 0-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 1 0 1.06 1.06L10 11.06l1.72 1.72a.75.75 0 1 0 1.06-1.06L11.06 10l1.72-1.72a.75.75 0 0 0-1.06-1.06L10 8.94 8.28 7.22Z" clip-rule="evenodd"/>
                    </svg>
                    @endif
                    <span class="text-sm {{ $check['ok'] ? 'text-gray-900' : 'text-gray-400' }}">{{ $check['label'] }}</span>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Sobre assignment --}}
        @if($oferta->activeRequirements->isNotEmpty())
        @php
            $metReqs = $oferta->activeRequirements->filter(fn($r) => in_array($r->estado, ['CUMPLE', 'ACEPTADO']));
            $sobreA = $oferta->activeRequirements->where('sobre', 'A');
            $sobreB = $oferta->activeRequirements->where('sobre', 'B');
            $sobreU = $oferta->activeRequirements->where('sobre', 'U');
            $hasSobres = $sobreA->isNotEmpty() || $sobreB->isNotEmpty() || $sobreU->isNotEmpty();
        @endphp
        <div class="rounded-xl border border-gray-200 bg-white" x-data="{ setAll(v) { $root.querySelectorAll('.js-sobre-select').forEach(s => { s.value = v; }); } }">
            <div class="border-b border-gray-200 px-6 py-4">
                <h2 class="text-sm font-semibold text-gray-900">Asignar requisitos a Sobres</h2>
                <p class="mt-0.5 text-xs text-gray-500">Clasifica cada requisito como Sobre A (administrativo/legal), Sobre B (técnico/económico), o Sobre Único cuando el proceso no separa sobres. Luego genera los paquetes PDF.</p>
                {{-- Bulk categorizer: sets every dropdown at once, then Guardar persists it. --}}
                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <span class="text-xs font-medium text-gray-400">Asignar todos a:</span>
                    <button type="button" @click="setAll('U')" class="rounded-md bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200 hover:bg-emerald-100">Sobre Único</button>
                    <button type="button" @click="setAll('A')" class="rounded-md bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700 ring-1 ring-inset ring-blue-200 hover:bg-blue-100">Sobre A</button>
                    <button type="button" @click="setAll('B')" class="rounded-md bg-purple-50 px-2.5 py-1 text-xs font-semibold text-purple-700 ring-1 ring-inset ring-purple-200 hover:bg-purple-100">Sobre B</button>
                    <button type="button" @click="setAll('')" class="rounded-md bg-white px-2.5 py-1 text-xs font-semibold text-gray-600 ring-1 ring-inset ring-gray-300 hover:bg-gray-50">Limpiar</button>
                    <span class="text-xs text-gray-400">(recuerde Guardar)</span>
                </div>
            </div>
            <form method="POST" action="{{ route('ofertas.sobres.save', $oferta) }}">
                @csrf
                <div class="divide-y divide-gray-100">
                    @foreach($oferta->activeRequirements as $req)
                    <div class="flex items-center justify-between gap-x-4 px-6 py-3">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm text-gray-900 truncate">{{ $req->descripcion }}</p>
                            <div class="mt-0.5 flex items-center gap-x-2">
                                <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium {{ $req->estadoColor() }}">{{ $req->estado }}</span>
                                <span class="text-xs text-gray-400">{{ $req->items->count() }} doc(s)</span>
                            </div>
                        </div>
                        <select name="sobres[{{ $req->id }}]"
                                class="js-sobre-select w-36 shrink-0 rounded-md bg-white px-2.5 py-1.5 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600">
                            <option value="">— Sin asignar —</option>
                            <option value="A" {{ $req->sobre === 'A' ? 'selected' : '' }}>Sobre A</option>
                            <option value="B" {{ $req->sobre === 'B' ? 'selected' : '' }}>Sobre B</option>
                            <option value="U" {{ $req->sobre === 'U' ? 'selected' : '' }}>Sobre Único</option>
                        </select>
                    </div>
                    @endforeach
                </div>
                <div class="flex items-center justify-between border-t border-gray-200 px-6 py-4">
                    <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-gray-500">
                        <span class="inline-flex items-center gap-x-1"><span class="inline-block size-2.5 rounded-full bg-blue-500"></span> Sobre A: {{ $sobreA->count() }}</span>
                        <span class="inline-flex items-center gap-x-1"><span class="inline-block size-2.5 rounded-full bg-purple-500"></span> Sobre B: {{ $sobreB->count() }}</span>
                        <span class="inline-flex items-center gap-x-1"><span class="inline-block size-2.5 rounded-full bg-emerald-500"></span> Sobre Único: {{ $sobreU->count() }}</span>
                    </div>
                    <div class="flex items-center gap-x-3">
                        <button type="submit"
                                class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                            Guardar asignación
                        </button>
                    </div>
                </div>
            </form>
            @if($hasSobres)
            <div class="border-t border-gray-200 px-6 py-4">
                <div class="flex items-center gap-x-3">
                    <form method="POST" action="{{ route('ofertas.sobres.generate', $oferta) }}">
                        @csrf
                        <button type="submit"
                                class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                            Generar Sobres PDF
                        </button>
                    </form>
                    @php
                        $code = preg_replace('/[^A-Za-z0-9_\-]/', '_', $oferta->proceso_codigo ?? 'oferta');
                        $sobreAFile = glob(storage_path("app/generated/sobres/Sobre A-{$code}.zip"));
                        $sobreBFile = glob(storage_path("app/generated/sobres/Sobre B-{$code}.zip"));
                        $sobreUFile = glob(storage_path("app/generated/sobres/Sobre U-{$code}.zip"));
                    @endphp
                    @if(!empty($sobreAFile))
                    <a href="{{ route('ofertas.sobres.download', [$oferta, 'A']) }}"
                       class="inline-flex items-center gap-x-1.5 rounded-md bg-blue-50 px-3 py-2 text-sm font-semibold text-blue-700 ring-1 ring-inset ring-blue-200 hover:bg-blue-100">
                        <svg viewBox="0 0 20 20" fill="currentColor" class="-ml-0.5 size-4">
                            <path d="M10.75 2.75a.75.75 0 0 0-1.5 0v8.614L6.295 8.235a.75.75 0 1 0-1.09 1.03l4.25 4.5a.75.75 0 0 0 1.09 0l4.25-4.5a.75.75 0 0 0-1.09-1.03l-2.955 3.129V2.75Z"/>
                            <path d="M3.5 12.75a.75.75 0 0 0-1.5 0v2.5A2.75 2.75 0 0 0 4.75 18h10.5A2.75 2.75 0 0 0 18 15.25v-2.5a.75.75 0 0 0-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5Z"/>
                        </svg>
                        Sobre A
                    </a>
                    @endif
                    @if(!empty($sobreBFile))
                    <a href="{{ route('ofertas.sobres.download', [$oferta, 'B']) }}"
                       class="inline-flex items-center gap-x-1.5 rounded-md bg-purple-50 px-3 py-2 text-sm font-semibold text-purple-700 ring-1 ring-inset ring-purple-200 hover:bg-purple-100">
                        <svg viewBox="0 0 20 20" fill="currentColor" class="-ml-0.5 size-4">
                            <path d="M10.75 2.75a.75.75 0 0 0-1.5 0v8.614L6.295 8.235a.75.75 0 1 0-1.09 1.03l4.25 4.5a.75.75 0 0 0 1.09 0l4.25-4.5a.75.75 0 0 0-1.09-1.03l-2.955 3.129V2.75Z"/>
                            <path d="M3.5 12.75a.75.75 0 0 0-1.5 0v2.5A2.75 2.75 0 0 0 4.75 18h10.5A2.75 2.75 0 0 0 18 15.25v-2.5a.75.75 0 0 0-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5Z"/>
                        </svg>
                        Sobre B
                    </a>
                    @endif
                    @if(!empty($sobreUFile))
                    <a href="{{ route('ofertas.sobres.download', [$oferta, 'U']) }}"
                       class="inline-flex items-center gap-x-1.5 rounded-md bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200 hover:bg-emerald-100">
                        <svg viewBox="0 0 20 20" fill="currentColor" class="-ml-0.5 size-4">
                            <path d="M10.75 2.75a.75.75 0 0 0-1.5 0v8.614L6.295 8.235a.75.75 0 1 0-1.09 1.03l4.25 4.5a.75.75 0 0 0 1.09 0l4.25-4.5a.75.75 0 0 0-1.09-1.03l-2.955 3.129V2.75Z"/>
                            <path d="M3.5 12.75a.75.75 0 0 0-1.5 0v2.5A2.75 2.75 0 0 0 4.75 18h10.5A2.75 2.75 0 0 0 18 15.25v-2.5a.75.75 0 0 0-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5Z"/>
                        </svg>
                        Sobre Único
                    </a>
                    @endif
                </div>
            </div>
            @endif
        </div>
        @endif

        {{-- State transition buttons --}}
        <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
            @if($oferta->estado === 'en_preparacion')
            <form method="POST" action="{{ route('ofertas.markListo', $oferta) }}">
                @csrf @method('PATCH')
                <button type="submit" {{ $oferta->canMarkListo() ? '' : 'disabled' }}
                        class="rounded-md bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-500 disabled:opacity-40 disabled:cursor-not-allowed">
                    Marcar como Listo
                </button>
            </form>
            @endif

            @if($oferta->estado === 'listo')
            <form method="POST" action="{{ route('ofertas.assemble', $oferta) }}">
                @csrf
                <button type="submit"
                        class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500">
                    Ensamblar paquete ZIP
                </button>
            </form>
            <form method="POST" action="{{ route('ofertas.markEnviado', $oferta) }}"
                  onsubmit="return confirm('¿Marcar como Enviado? Asegúrate de haber ensamblado el paquete.')">
                @csrf @method('PATCH')
                <button type="submit" {{ $hasSnap ? '' : 'disabled' }}
                        class="rounded-md bg-purple-600 px-4 py-2 text-sm font-semibold text-white hover:bg-purple-500 disabled:opacity-40 disabled:cursor-not-allowed">
                    Marcar como Enviado
                </button>
            </form>
            @if(!$hasSnap)
            <p class="text-xs text-gray-500">Ensambla el paquete ZIP primero para habilitar el envío.</p>
            @endif
            @endif

            @if($oferta->estado === 'enviado')
            <div class="flex items-center gap-x-4">
                <span class="text-sm text-purple-700 font-medium">
                    ✓ Enviado el {{ $oferta->enviado_at?->format('d/m/Y H:i') }}
                </span>
                <form method="POST" action="{{ route('ofertas.reabrir', $oferta) }}"
                      onsubmit="return confirm('¿Reabrir la oferta? Volverá a estado En preparación.')">
                    @csrf @method('PATCH')
                    <button type="submit" class="rounded-md bg-amber-600 px-3 py-2 text-sm font-semibold text-white hover:bg-amber-500">
                        Reabrir oferta
                    </button>
                </form>
            </div>
            @endif
        </div>

        {{-- Snapshot history --}}
        @if($oferta->snapshots->isNotEmpty())
        <div class="rounded-xl border border-gray-200 bg-white">
            <div class="border-b border-gray-200 px-6 py-4">
                <h2 class="text-sm font-semibold text-gray-900">Paquetes ensamblados</h2>
            </div>
            <div class="divide-y divide-gray-100">
                @foreach($oferta->snapshots as $snapshot)
                <div class="flex items-center justify-between px-6 py-4">
                    <div>
                        <div class="text-sm font-medium text-gray-900">
                            Paquete {{ $oferta->snapshots->count() - $loop->index }}
                        </div>
                        <div class="mt-0.5 text-xs text-gray-500">
                            {{ $snapshot->assembled_at?->format('d/m/Y H:i') }}
                            @if($snapshot->zip_sha256)
                             · SHA256: <span class="font-mono">{{ substr($snapshot->zip_sha256, 0, 16) }}…</span>
                            @endif
                        </div>
                    </div>
                    <a href="{{ route('ofertas.snapshots.download', [$oferta, $snapshot]) }}"
                       class="inline-flex items-center gap-x-1.5 rounded-md bg-white px-3 py-1.5 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                        <svg viewBox="0 0 20 20" fill="currentColor" class="-ml-0.5 size-4 text-gray-400">
                            <path d="M10.75 2.75a.75.75 0 0 0-1.5 0v8.614L6.295 8.235a.75.75 0 1 0-1.09 1.03l4.25 4.5a.75.75 0 0 0 1.09 0l4.25-4.5a.75.75 0 0 0-1.09-1.03l-2.955 3.129V2.75Z"/>
                            <path d="M3.5 12.75a.75.75 0 0 0-1.5 0v2.5A2.75 2.75 0 0 0 4.75 18h10.5A2.75 2.75 0 0 0 18 15.25v-2.5a.75.75 0 0 0-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5Z"/>
                        </svg>
                        Descargar ZIP
                    </a>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Danger zone --}}
        @if(in_array($oferta->estado, ['borrador', 'en_preparacion']))
        <div class="rounded-xl border border-red-200 bg-red-50 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-semibold text-red-800">Zona peligrosa</h3>
                    <p class="mt-0.5 text-xs text-red-600">Eliminar esta oferta y todos sus datos asociados.</p>
                </div>
                <form method="POST" action="{{ route('ofertas.destroy', $oferta) }}"
                      onsubmit="return confirm('¿Eliminar esta oferta permanentemente? Se perderán el análisis del pliego, checklist y todos los datos asociados.')">
                    @csrf @method('DELETE')
                    <button type="submit"
                            class="rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white hover:bg-red-500">
                        Eliminar oferta
                    </button>
                </form>
            </div>
        </div>
        @endif

    </div>
    @endif

</div>


{{-- ═══════════════════════════════════════════════════════════════════════ --}}
{{-- DRAWERS                                                                  --}}
{{-- ═══════════════════════════════════════════════════════════════════════ --}}

{{-- Add requirement --}}
<el-dialog>
    <dialog id="add-req-drawer" class="backdrop:bg-transparent">
        <el-dialog-backdrop class="fixed inset-0 bg-gray-900/80 transition-opacity duration-300 ease-linear data-closed:opacity-0"></el-dialog-backdrop>
        <div tabindex="0" class="fixed inset-0 flex justify-end focus:outline-none">
            <el-dialog-panel class="group/dialog-panel pointer-events-auto w-screen max-w-md transform transition duration-300 ease-in-out data-closed:translate-x-full">
                <div class="flex h-full flex-col overflow-y-scroll bg-white shadow-xl">
                    <div class="bg-blue-800 px-4 py-6 sm:px-6">
                        <div class="flex items-center justify-between">
                            <h2 class="text-base font-semibold text-white">Agregar requisito</h2>
                            <button command="close" commandfor="add-req-drawer" class="text-blue-200 hover:text-white">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="size-6">
                                    <path d="M6 18 18 6M6 6l12 12" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="flex flex-1 flex-col gap-y-5 px-4 py-6 sm:px-6">
                        <form method="POST" action="{{ route('ofertas.requirements.store', $oferta) }}" class="space-y-5">
                            @csrf
                            <div>
                                <label class="block text-sm font-medium text-gray-900">Descripción <span class="text-red-500">*</span></label>
                                <textarea name="descripcion" required rows="3"
                                          placeholder="ej. Certificado de registro de proveedores vigente"
                                          class="mt-1.5 w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600"></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-900">Tipo <span class="text-red-500">*</span></label>
                                <select name="tipo" required
                                        class="mt-1.5 w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600">
                                    @foreach(\App\Models\OfferRequirement::$tipos as $val => $lbl)
                                    <option value="{{ $val }}">{{ $lbl }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-900">Notas</label>
                                <textarea name="notes" rows="2"
                                          class="mt-1.5 w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600"></textarea>
                            </div>
                            <div class="flex justify-end gap-x-3 pt-4">
                                <button type="button" command="close" commandfor="add-req-drawer"
                                        class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                                    Cancelar
                                </button>
                                <button type="submit"
                                        class="rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-xs hover:bg-blue-500">
                                    Agregar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </el-dialog-panel>
        </div>
    </dialog>
</el-dialog>

{{-- Edit requirement --}}
<el-dialog>
    <dialog id="edit-req-drawer" class="backdrop:bg-transparent">
        <el-dialog-backdrop class="fixed inset-0 bg-gray-900/80 transition-opacity duration-300 ease-linear data-closed:opacity-0"></el-dialog-backdrop>
        <div tabindex="0" class="fixed inset-0 flex justify-end focus:outline-none">
            <el-dialog-panel class="group/dialog-panel pointer-events-auto w-screen max-w-md transform transition duration-300 ease-in-out data-closed:translate-x-full">
                <div class="flex h-full flex-col overflow-y-scroll bg-white shadow-xl">
                    <div class="bg-blue-800 px-4 py-6 sm:px-6">
                        <div class="flex items-center justify-between">
                            <h2 class="text-base font-semibold text-white">Editar requisito</h2>
                            <button command="close" commandfor="edit-req-drawer" class="text-blue-200 hover:text-white">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="size-6">
                                    <path d="M6 18 18 6M6 6l12 12" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="flex flex-1 flex-col gap-y-5 px-4 py-6 sm:px-6">
                        <form id="edit-req-form" method="POST" action="" class="space-y-5">
                            @csrf @method('PATCH')
                            <div>
                                <label class="block text-sm font-medium text-gray-900">Descripción <span class="text-red-500">*</span></label>
                                <textarea id="edit-req-descripcion" name="descripcion" required rows="3"
                                          class="mt-1.5 w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600"></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-900">Tipo <span class="text-red-500">*</span></label>
                                <select id="edit-req-tipo" name="tipo" required
                                        class="mt-1.5 w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600">
                                    @foreach(\App\Models\OfferRequirement::$tipos as $val => $lbl)
                                    <option value="{{ $val }}">{{ $lbl }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-900">Estado</label>
                                <select id="edit-req-estado" name="estado"
                                        class="mt-1.5 w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600">
                                    <option value="PENDIENTE">PENDIENTE</option>
                                    <option value="CUMPLE">CUMPLE</option>
                                    <option value="NO_CUMPLE">NO_CUMPLE</option>
                                    <option value="ACEPTADO">ACEPTADO</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-900">Notas</label>
                                <textarea id="edit-req-notes" name="notes" rows="2"
                                          class="mt-1.5 w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600"></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-900">Razón de aceptación</label>
                                <input id="edit-req-acceptance" type="text" name="acceptance_reason"
                                       placeholder="Requerido si el estado es ACEPTADO"
                                       class="mt-1.5 w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600"/>
                            </div>
                            <div class="flex justify-end gap-x-3 pt-4">
                                <button type="button" command="close" commandfor="edit-req-drawer"
                                        class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                                    Cancelar
                                </button>
                                <button type="submit"
                                        class="rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-xs hover:bg-blue-500">
                                    Guardar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </el-dialog-panel>
        </div>
    </dialog>
</el-dialog>

{{-- Assign vault item to requirement --}}
<el-dialog>
    <dialog id="assign-item-drawer" class="backdrop:bg-transparent">
        <el-dialog-backdrop class="fixed inset-0 bg-gray-900/80 transition-opacity duration-300 ease-linear data-closed:opacity-0"></el-dialog-backdrop>
        <div tabindex="0" class="fixed inset-0 flex justify-end focus:outline-none">
            <el-dialog-panel class="group/dialog-panel pointer-events-auto w-screen max-w-md transform transition duration-300 ease-in-out data-closed:translate-x-full">
                <div class="flex h-full flex-col overflow-y-scroll bg-white shadow-xl"
                     x-data="{ vaultType: '', vaultRefId: '' }">
                    <div class="bg-blue-800 px-4 py-6 sm:px-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-base font-semibold text-white">Asignar documento</h2>
                                <p id="assign-req-label" class="mt-1 text-sm text-blue-300 truncate max-w-xs"></p>
                            </div>
                            <button command="close" commandfor="assign-item-drawer" class="text-blue-200 hover:text-white">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="size-6">
                                    <path d="M6 18 18 6M6 6l12 12" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="flex flex-1 flex-col gap-y-5 px-4 py-6 sm:px-6">
                        <form id="assign-item-form" method="POST" action="" enctype="multipart/form-data" class="space-y-5">
                            @csrf
                            <input type="hidden" name="vault_ref_id" :value="vaultRefId"/>

                            <div>
                                <label class="block text-sm font-medium text-gray-900">Tipo de recurso <span class="text-red-500">*</span></label>
                                <select name="vault_ref_type" x-model="vaultType" required
                                        class="mt-1.5 w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600">
                                    <option value="">— Seleccionar tipo —</option>
                                    <option value="uploaded_file">Documento cargado</option>
                                    <option value="vault_documents">Documento de bóveda</option>
                                    <option value="personnel">Personal</option>
                                    <option value="projects">Proyecto</option>
                                    <option value="equipment">Equipo</option>
                                    <option value="financial_records">Año fiscal</option>
                                    <option value="offer_generated_files">Formulario generado</option>
                                </select>
                            </div>

                            {{-- Upload file directly --}}
                            <div x-show="vaultType === 'uploaded_file'" x-cloak class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-900">Nombre del documento <span class="text-red-500">*</span></label>
                                    <input type="text" name="upload_name" placeholder="ej. Certificación DGII, Acta constitutiva"
                                           class="mt-1.5 w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600"/>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-900">Archivo <span class="text-red-500">*</span></label>
                                    <input type="file" name="upload_file" @change="vaultRefId = $event.target.files.length ? 'upload' : ''"
                                           class="mt-1.5 w-full rounded-md bg-white px-3 py-1.5 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 file:mr-3 file:rounded file:border-0 file:bg-blue-50 file:px-3 file:py-1 file:text-sm file:font-medium file:text-blue-700 hover:file:bg-blue-100"/>
                                    <p class="mt-1 text-xs text-gray-500">PDF, Word, Excel, imágenes — máx. 20 MB</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-900">Categoría</label>
                                    <select name="upload_category"
                                            class="mt-1.5 w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600">
                                        @foreach(\App\Models\VaultDocument::$categories as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            {{-- Vault documents --}}
                            <div x-show="vaultType === 'vault_documents'" x-cloak>
                                <label class="block text-sm font-medium text-gray-900">Documento</label>
                                <select @change="vaultRefId = $event.target.value"
                                        class="mt-1.5 w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600">
                                    <option value="">— Seleccionar —</option>
                                    @if($tab === 'checklist')
                                    @foreach($vaultDocs as $doc)
                                    <option value="{{ $doc->id }}">{{ $doc->name }} ({{ \App\Models\VaultDocument::$categories[$doc->category] ?? $doc->category }})</option>
                                    @endforeach
                                    @endif
                                </select>
                            </div>

                            {{-- Personnel --}}
                            <div x-show="vaultType === 'personnel'" x-cloak>
                                <label class="block text-sm font-medium text-gray-900">Personal</label>
                                <select @change="vaultRefId = $event.target.value"
                                        class="mt-1.5 w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600">
                                    <option value="">— Seleccionar —</option>
                                    @foreach($availablePersonnel as $p)
                                    <option value="{{ $p->id }}">{{ $p->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Projects --}}
                            <div x-show="vaultType === 'projects'" x-cloak>
                                <label class="block text-sm font-medium text-gray-900">Proyecto</label>
                                <select @change="vaultRefId = $event.target.value"
                                        class="mt-1.5 w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600">
                                    <option value="">— Seleccionar —</option>
                                    @foreach($availableProjects as $p)
                                    <option value="{{ $p->id }}">{{ $p->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Equipment --}}
                            <div x-show="vaultType === 'equipment'" x-cloak>
                                <label class="block text-sm font-medium text-gray-900">Equipo</label>
                                <select @change="vaultRefId = $event.target.value"
                                        class="mt-1.5 w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600">
                                    <option value="">— Seleccionar —</option>
                                    @foreach($availableEquipment as $e)
                                    <option value="{{ $e->id }}">{{ $e->fichaLabel() ?: $e->descripcion }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Financial records --}}
                            <div x-show="vaultType === 'financial_records'" x-cloak>
                                <label class="block text-sm font-medium text-gray-900">Año fiscal</label>
                                <select @change="vaultRefId = $event.target.value"
                                        class="mt-1.5 w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600">
                                    <option value="">— Seleccionar —</option>
                                    @foreach($availableFinancials as $fr)
                                    <option value="{{ $fr->id }}">Año {{ $fr->anio_fiscal }} — {{ $fr->currency }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Generated files --}}
                            <div x-show="vaultType === 'offer_generated_files'" x-cloak>
                                <label class="block text-sm font-medium text-gray-900">Formulario generado</label>
                                <select @change="vaultRefId = $event.target.value"
                                        class="mt-1.5 w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600">
                                    <option value="">— Seleccionar —</option>
                                    @foreach($oferta->generatedFiles as $gf)
                                    <option value="{{ $gf->id }}">{{ \App\Models\OfferGeneratedFile::$forms[$gf->form_code] ?? $gf->form_code }} ({{ $gf->generated_at?->format('d/m/Y') }})</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-900">Nota de rol</label>
                                <input type="text" name="role_note" placeholder="ej. Copia notariada, Versión 2025"
                                       class="mt-1.5 w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600"/>
                            </div>

                            <div class="flex justify-end gap-x-3 pt-4">
                                <button type="button" command="close" commandfor="assign-item-drawer"
                                        class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                                    Cancelar
                                </button>
                                <button type="submit" :disabled="!vaultType || !vaultRefId"
                                        class="rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-xs hover:bg-blue-500 disabled:opacity-40">
                                    Asignar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </el-dialog-panel>
        </div>
    </dialog>
</el-dialog>

{{-- Add event --}}
<el-dialog>
    <dialog id="add-event-drawer" class="backdrop:bg-transparent">
        <el-dialog-backdrop class="fixed inset-0 bg-gray-900/80 transition-opacity duration-300 ease-linear data-closed:opacity-0"></el-dialog-backdrop>
        <div tabindex="0" class="fixed inset-0 flex justify-end focus:outline-none">
            <el-dialog-panel class="group/dialog-panel pointer-events-auto w-screen max-w-md transform transition duration-300 ease-in-out data-closed:translate-x-full">
                <div class="flex h-full flex-col overflow-y-scroll bg-white shadow-xl">
                    <div class="bg-blue-800 px-4 py-6 sm:px-6">
                        <div class="flex items-center justify-between">
                            <h2 class="text-base font-semibold text-white">Agregar evento</h2>
                            <button command="close" commandfor="add-event-drawer" class="text-blue-200 hover:text-white">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="size-6">
                                    <path d="M6 18 18 6M6 6l12 12" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="flex flex-1 flex-col gap-y-5 px-4 py-6 sm:px-6">
                        <form method="POST" action="{{ route('ofertas.events.store', $oferta) }}" class="space-y-5">
                            @csrf
                            <div>
                                <label class="block text-sm font-medium text-gray-900">Tipo de evento <span class="text-red-500">*</span></label>
                                <select name="event_type" required id="add-event-type"
                                        class="mt-1.5 w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600">
                                    @foreach(\App\Models\OfferEvent::$types as $val => $lbl)
                                    <option value="{{ $val }}">{{ $lbl }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-900">Descripción / nota</label>
                                <input type="text" name="description" placeholder="ej. Sala de conferencias piso 3"
                                       class="mt-1.5 w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600"/>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-900">Fecha <span class="text-red-500">*</span></label>
                                <input type="date" name="event_date" required
                                       class="mt-1.5 w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600"/>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-900">Días de alerta anticipada</label>
                                <input type="number" name="alert_days_before" min="0" max="30" value="1"
                                       class="mt-1.5 w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600"/>
                            </div>
                            <div class="flex justify-end gap-x-3 pt-4">
                                <button type="button" command="close" commandfor="add-event-drawer"
                                        class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                                    Cancelar
                                </button>
                                <button type="submit"
                                        class="rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-xs hover:bg-blue-500">
                                    Agregar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </el-dialog-panel>
        </div>
    </dialog>
</el-dialog>

{{-- Edit event --}}
<el-dialog>
    <dialog id="edit-event-drawer" class="backdrop:bg-transparent">
        <el-dialog-backdrop class="fixed inset-0 bg-gray-900/80 transition-opacity duration-300 ease-linear data-closed:opacity-0"></el-dialog-backdrop>
        <div tabindex="0" class="fixed inset-0 flex justify-end focus:outline-none">
            <el-dialog-panel class="group/dialog-panel pointer-events-auto w-screen max-w-md transform transition duration-300 ease-in-out data-closed:translate-x-full">
                <div class="flex h-full flex-col overflow-y-scroll bg-white shadow-xl">
                    <div class="bg-blue-800 px-4 py-6 sm:px-6">
                        <div class="flex items-center justify-between">
                            <h2 class="text-base font-semibold text-white">Editar evento</h2>
                            <button command="close" commandfor="edit-event-drawer" class="text-blue-200 hover:text-white">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="size-6">
                                    <path d="M6 18 18 6M6 6l12 12" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="flex flex-1 flex-col gap-y-5 px-4 py-6 sm:px-6">
                        <form id="edit-event-form" method="POST" action="" class="space-y-5">
                            @csrf @method('PATCH')
                            <div>
                                <label class="block text-sm font-medium text-gray-900">Descripción / nota</label>
                                <input type="text" id="edit-event-description" name="description"
                                       class="mt-1.5 w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600"/>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-900">Fecha <span class="text-red-500">*</span></label>
                                <input type="date" id="edit-event-date" name="event_date" required
                                       class="mt-1.5 w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600"/>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-900">Días de alerta anticipada</label>
                                <input type="number" id="edit-event-alert" name="alert_days_before" min="0" max="30"
                                       class="mt-1.5 w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600"/>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-900">Estado</label>
                                <select id="edit-event-status" name="status"
                                        class="mt-1.5 w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600">
                                    <option value="pending">Pendiente</option>
                                    <option value="completed">Completado</option>
                                    <option value="missed">Perdido</option>
                                </select>
                            </div>
                            <div class="flex justify-end gap-x-3 pt-4">
                                <button type="button" command="close" commandfor="edit-event-drawer"
                                        class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                                    Cancelar
                                </button>
                                <button type="submit"
                                        class="rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-xs hover:bg-blue-500">
                                    Guardar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </el-dialog-panel>
        </div>
    </dialog>
</el-dialog>

<script>
function openEditReqDrawer(id, descripcion, tipo, estado, notes, acceptanceReason) {
    document.getElementById('edit-req-form').action = '/ofertas/{{ $oferta->id }}/requirements/' + id;
    document.getElementById('edit-req-descripcion').value = descripcion || '';
    document.getElementById('edit-req-tipo').value = tipo || 'documento';
    document.getElementById('edit-req-estado').value = estado || 'PENDIENTE';
    document.getElementById('edit-req-notes').value = notes || '';
    document.getElementById('edit-req-acceptance').value = acceptanceReason || '';

    const drawer = document.getElementById('edit-req-drawer');
    drawer.showModal ? drawer.showModal() : drawer.setAttribute('open', '');
}

function openAssignItemDrawer(reqId, reqDescription) {
    document.getElementById('assign-req-label').textContent = reqDescription;
    document.getElementById('assign-item-form').action = '/ofertas/{{ $oferta->id }}/requirements/' + reqId + '/items';

    const drawer = document.getElementById('assign-item-drawer');
    drawer.showModal ? drawer.showModal() : drawer.setAttribute('open', '');
}

function openEditEventDrawer(id, description, eventType, eventDate, alertDays, status) {
    document.getElementById('edit-event-form').action = '/ofertas/{{ $oferta->id }}/events/' + id;
    document.getElementById('edit-event-description').value = description || '';
    document.getElementById('edit-event-date').value = eventDate || '';
    document.getElementById('edit-event-alert').value = alertDays ?? 1;
    document.getElementById('edit-event-status').value = status || 'pending';

    const drawer = document.getElementById('edit-event-drawer');
    drawer.showModal ? drawer.showModal() : drawer.setAttribute('open', '');
}

function ofertaFormGen() {
    return {
        formCode: '',
        get needsPersonnel() {
            return ['SNCC.D.045', 'SNCC.D.048'].includes(this.formCode);
        },
        get needsProjects() {
            return this.formCode === 'SNCC.D.049';
        },
    };
}
</script>
@endsection
