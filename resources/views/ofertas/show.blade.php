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
                @endif
            </div>
        </div>
    </div>

    {{-- ── Tab nav ──────────────────────────────────────────────────────── --}}
    @php
        $tabs = [
            'pliego'      => 'Pliego',
            'checklist'   => 'Checklist',
            'composicion' => 'Composición',
            'formularios' => 'Formularios',
            'cronograma'  => 'Cronograma',
            'ensamblar'   => 'Ensamblar',
        ];
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
    {{-- TAB: PLIEGO                                                         --}}
    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    @if($tab === 'pliego')
    <div class="space-y-6">

        {{-- Parse status --}}
        @if($activeParse)
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
            <p class="mt-1 text-sm text-gray-500">Sube el pliego de condiciones y Gemini extraerá los requisitos automáticamente.</p>
        </div>
        @endif

        {{-- Upload pliego --}}
        @if($oferta->isEditable())
        <div class="rounded-xl border border-gray-200 bg-white">
            <div class="border-b border-gray-200 px-6 py-4">
                <h2 class="text-sm font-semibold text-gray-900">{{ $activeParse ? 'Re-subir pliego' : 'Subir pliego' }}</h2>
                <p class="mt-0.5 text-xs text-gray-500">PDF hasta 50 MB. Se analizará automáticamente con Gemini.</p>
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
            Sin requisitos. Sube el pliego en la pestaña <strong>Pliego</strong> para que Gemini los extraiga, o agrégalos manualmente.
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
                            <div class="flex items-center justify-end gap-x-3">
                                <button type="button"
                                        onclick="openAssignItemDrawer({{ $req->id }}, {{ json_encode(mb_substr($req->descripcion, 0, 60)) }})"
                                        class="text-xs font-medium text-blue-600 hover:text-blue-500">
                                    Asignar
                                </button>
                                <button type="button"
                                        onclick="openEditReqDrawer({{ $req->id }}, {{ json_encode($req->descripcion) }}, {{ json_encode($req->tipo) }}, {{ json_encode($req->estado) }}, {{ json_encode($req->notes) }}, {{ json_encode($req->acceptance_reason) }})"
                                        class="text-xs font-medium text-gray-600 hover:text-gray-900">
                                    Editar
                                </button>
                                <form method="POST" action="{{ route('ofertas.requirements.destroy', [$oferta, $req]) }}"
                                      onsubmit="return confirm('¿Eliminar este requisito?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-xs font-medium text-red-500 hover:text-red-700">Eliminar</button>
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
            'Formularios de oferta' => ['SNCC.F.033','SNCC.F.034','SNCC.F.036','SNCC.F.037','SNCC.F.042'],
            'Documentos técnicos'   => ['SNCC.D.045','SNCC.D.048','SNCC.D.049'],
            'Declaraciones'         => ['DECL.JURADA','DECL.COMPROMISO_ETICO'],
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
                    <a href="{{ route('ofertas.generated.download', [$oferta, $file]) }}"
                       class="inline-flex items-center gap-x-1.5 rounded-md bg-white px-3 py-1.5 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                        <svg viewBox="0 0 20 20" fill="currentColor" class="-ml-0.5 size-4 text-gray-400">
                            <path d="M10.75 2.75a.75.75 0 0 0-1.5 0v8.614L6.295 8.235a.75.75 0 1 0-1.09 1.03l4.25 4.5a.75.75 0 0 0 1.09 0l4.25-4.5a.75.75 0 0 0-1.09-1.03l-2.955 3.129V2.75Z"/>
                            <path d="M3.5 12.75a.75.75 0 0 0-1.5 0v2.5A2.75 2.75 0 0 0 4.75 18h10.5A2.75 2.75 0 0 0 18 15.25v-2.5a.75.75 0 0 0-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5Z"/>
                        </svg>
                        Descargar
                    </a>
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
                        <div class="w-12 flex-shrink-0 text-center">
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
        @if($oferta->estado === 'borrador')
        <div class="rounded-xl border border-red-200 bg-red-50 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-semibold text-red-800">Zona peligrosa</h3>
                    <p class="mt-0.5 text-xs text-red-600">Las ofertas solo pueden eliminarse en estado Borrador.</p>
                </div>
                <form method="POST" action="{{ route('ofertas.destroy', $oferta) }}"
                      onsubmit="return confirm('¿Eliminar esta oferta permanentemente? No hay vuelta atrás.')">
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
<el-dialog id="add-req-drawer" type="slideover" placement="right">
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
</el-dialog>

{{-- Edit requirement --}}
<el-dialog id="edit-req-drawer" type="slideover" placement="right">
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
</el-dialog>

{{-- Assign vault item to requirement --}}
<el-dialog id="assign-item-drawer" type="slideover" placement="right">
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
            <form id="assign-item-form" method="POST" action="" class="space-y-5">
                @csrf
                <input type="hidden" name="vault_ref_id" :value="vaultRefId"/>

                <div>
                    <label class="block text-sm font-medium text-gray-900">Tipo de recurso <span class="text-red-500">*</span></label>
                    <select name="vault_ref_type" x-model="vaultType" required
                            class="mt-1.5 w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600">
                        <option value="">— Seleccionar tipo —</option>
                        <option value="vault_documents">Documento de bóveda</option>
                        <option value="personnel">Personal</option>
                        <option value="projects">Proyecto</option>
                        <option value="equipment">Equipo</option>
                        <option value="financial_records">Año fiscal</option>
                        <option value="offer_generated_files">Formulario generado</option>
                    </select>
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
</el-dialog>

{{-- Add event --}}
<el-dialog id="add-event-drawer" type="slideover" placement="right">
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
</el-dialog>

{{-- Edit event --}}
<el-dialog id="edit-event-drawer" type="slideover" placement="right">
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
