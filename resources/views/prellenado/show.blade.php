@extends('layouts.app')
@section('title', 'Prellenado — ' . Str::limit($bid->title, 40))

@section('content')
<div x-data="prellenado()" class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">

    {{-- Breadcrumb --}}
    <nav class="flex mb-6" aria-label="Breadcrumb">
        <ol class="flex items-center space-x-2 text-sm text-gray-500">
            <li><a href="{{ route('convocatorias.index') }}" class="hover:text-gray-700">Convocatorias</a></li>
            <li><span class="mx-1">/</span></li>
            <li class="text-gray-900 font-medium truncate max-w-xs">{{ $bid->process_code }}</li>
            <li><span class="mx-1">/</span></li>
            <li class="text-gray-900 font-medium">Prellenado</li>
        </ol>
    </nav>

    <form method="POST" action="{{ route('prellenado.generate', $bid) }}" id="prellenado-form">
        @csrf

        {{-- ── Section 1: Header summary ──────────────────────────────── --}}
        <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-900/5 p-6">
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                {{-- Bid data --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-500">Datos de la licitación</h3>
                    <dl class="mt-3 space-y-2 text-sm">
                        <div>
                            <dt class="text-gray-500">Identificador</dt>
                            <dd class="font-mono text-gray-900">{{ $bid->process_code }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Título</dt>
                            <dd class="text-gray-900 font-medium">{{ $bid->title }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Institución</dt>
                            <dd class="text-gray-900">{{ $bid->buyer_name }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Monto</dt>
                            <dd class="text-gray-900">
                                @if($bid->amount_estimated && $bid->amount_estimated > 0)
                                    {{ $bid->currency === 'USD' ? 'US$' : 'RD$' }}{{ number_format($bid->amount_estimated, 2, '.', ',') }}
                                @else
                                    —
                                @endif
                            </dd>
                        </div>
                    </dl>
                </div>
                {{-- Company data --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-500">Detalles de la empresa</h3>
                    <dl class="mt-3 space-y-2 text-sm">
                        <div>
                            <dt class="text-gray-500">Nombre</dt>
                            <dd class="text-gray-900 font-medium">{{ $company->razon_social ?? 'Sin configurar' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">RNC</dt>
                            <dd class="text-gray-900 font-mono">{{ $company->rnc ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">RPE</dt>
                            <dd class="text-gray-900 font-mono">{{ $company->rpe_numero ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Dirección</dt>
                            <dd class="text-gray-900">{{ $company->direccion ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Teléfono</dt>
                            <dd class="text-gray-900">{{ $company->telefono ?? '—' }}</dd>
                        </div>
                    </dl>
                </div>
                {{-- Representative data --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-500">Detalles del representante</h3>
                    <dl class="mt-3 space-y-2 text-sm">
                        <div>
                            <dt class="text-gray-500">Nombre</dt>
                            <dd class="text-gray-900 font-medium">{{ $company->rep_legal_nombre ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Cargo</dt>
                            <dd class="text-gray-900">{{ $company->rep_legal_cargo ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Cédula</dt>
                            <dd class="text-gray-900 font-mono">{{ $company->rep_legal_cedula ?? '—' }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>

        {{-- ── Section 2: Document catalog ─────────────────────────────── --}}
        <div class="mt-6 rounded-xl bg-white shadow-sm ring-1 ring-gray-900/5 p-6">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-900">Documentos a generar</h3>
                <div class="flex items-center gap-3">
                    <span class="text-xs text-gray-500" x-text="selectedCount + ' seleccionado' + (selectedCount !== 1 ? 's' : '')"></span>
                    <input type="text" x-model="docSearch" placeholder="Buscar documentos..."
                           class="rounded-md border-0 py-1 px-3 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-blue-600 w-56">
                </div>
            </div>

            <div class="mt-4 space-y-6">
                @foreach($catalog as $category => $templates)
                <div x-show="categoryVisible('{{ $category }}')">
                    <h4 class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-2">{{ $category }}</h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                        @foreach($templates as $path => $label)
                        <label class="flex items-start gap-2 rounded-md border border-gray-200 px-3 py-2 text-sm cursor-pointer hover:bg-gray-50 transition-colors"
                               x-show="docSearch === '' || '{{ strtolower($label) }}'.includes(docSearch.toLowerCase())"
                               :class="selected.includes('{{ $path }}') && 'border-blue-300 bg-blue-50'">
                            <input type="checkbox" name="templates[]" value="{{ $path }}"
                                   x-model="selected"
                                   class="mt-0.5 rounded border-gray-300 text-blue-600 focus:ring-blue-600">
                            <span class="text-gray-700">{{ $label }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- ── Section 3: Vault documents ──────────────────────────────── --}}
        <div class="mt-6 rounded-xl bg-white shadow-sm ring-1 ring-gray-900/5 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-semibold text-gray-900">Documentos de la empresa</h3>
                    <p class="mt-0.5 text-xs text-gray-500">Se incluirán en el ZIP junto a los formularios generados.</p>
                </div>
                <a href="{{ route('documentos.index') }}" class="text-xs text-blue-600 hover:text-blue-500 font-medium">Gestionar documentos</a>
            </div>

            @if($vaultDocs->isEmpty())
                <p class="mt-4 text-sm text-gray-400">Sin documentos en la bóveda.</p>
            @else
                <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-2">
                    @foreach($vaultDocs as $doc)
                    <label class="flex items-center gap-2 rounded-md border border-gray-200 px-3 py-2 text-sm cursor-pointer hover:bg-gray-50">
                        <input type="checkbox" name="vault_doc_ids[]" value="{{ $doc->id }}"
                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-600">
                        <span class="text-gray-700 truncate">{{ $doc->label }}</span>
                        <span class="text-xs text-gray-400 ml-auto">{{ strtoupper($doc->extension ?? 'PDF') }}</span>
                    </label>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- ── Section 4: Resources ────────────────────────────────────── --}}
        <div class="mt-6 rounded-xl bg-white shadow-sm ring-1 ring-gray-900/5 p-6">
            <h3 class="text-sm font-semibold text-gray-900">Personal y recursos</h3>
            <p class="mt-0.5 text-xs text-gray-500 mb-4">Seleccione los recursos del vault que alimentarán los formularios.</p>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Personnel --}}
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Personal (D.045, D.048, F.037)</label>
                    @if($personnel->isEmpty())
                        <p class="text-sm text-gray-400">Sin personal activo. <a href="{{ route('personal.index') }}" class="text-blue-600">Agregar</a></p>
                    @else
                        <div class="space-y-1.5 max-h-40 overflow-y-auto border border-gray-200 rounded-md p-2">
                            @foreach($personnel as $p)
                            <label class="flex items-center gap-2 text-sm cursor-pointer">
                                <input type="checkbox" name="personnel_ids[]" value="{{ $p->id }}"
                                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-600">
                                <span class="text-gray-700">{{ $p->nombre }}{{ $p->cargo ? ' — ' . $p->cargo : '' }}</span>
                            </label>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Equipment --}}
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Equipos (F.036)</label>
                    @if($equipment->isEmpty())
                        <p class="text-sm text-gray-400">Sin equipos activos. <a href="{{ route('equipos.index') }}" class="text-blue-600">Agregar</a></p>
                    @else
                        <div class="space-y-1.5 max-h-40 overflow-y-auto border border-gray-200 rounded-md p-2">
                            @foreach($equipment as $eq)
                            <label class="flex items-center gap-2 text-sm cursor-pointer">
                                <input type="checkbox" name="equipment_ids[]" value="{{ $eq->id }}"
                                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-600">
                                <span class="text-gray-700">{{ $eq->descripcion }} — {{ $eq->marca }} {{ $eq->modelo }}</span>
                            </label>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Projects --}}
                <div class="lg:col-span-2">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Experiencia / Proyectos (D.049)</label>
                    @if($projects->isEmpty())
                        <p class="text-sm text-gray-400">Sin proyectos registrados. <a href="{{ route('proyectos.index') }}" class="text-blue-600">Agregar</a></p>
                    @else
                        <div class="space-y-1.5 max-h-40 overflow-y-auto border border-gray-200 rounded-md p-2">
                            @foreach($projects as $proj)
                            <label class="flex items-center gap-2 text-sm cursor-pointer">
                                <input type="checkbox" name="project_ids[]" value="{{ $proj->id }}"
                                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-600">
                                <span class="text-gray-700">{{ $proj->nombre }} — {{ $proj->cliente }}
                                    <span class="text-gray-400">{{ $proj->montoFormatted() }}</span>
                                </span>
                            </label>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- ── Section 5: Articles pricing ─────────────────────────────── --}}
        <div class="mt-6 rounded-xl bg-white shadow-sm ring-1 ring-gray-900/5 p-6">
            <h3 class="text-sm font-semibold text-gray-900">Artículos</h3>
            <p class="mt-0.5 text-xs text-gray-500 mb-4">Edite los precios para los formularios de oferta económica (F.033, FL-05).</p>

            @if(empty($articles))
                <p class="text-sm text-gray-400">Sin artículos en caché para esta convocatoria. Abra el detalle de la convocatoria para cargarlos desde la API.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200">
                                <th class="py-2 pr-2 text-left font-medium text-gray-900 w-8">#</th>
                                <th class="py-2 px-2 text-left font-medium text-gray-900">Descripción</th>
                                <th class="py-2 px-2 text-left font-medium text-gray-900 w-20">Unidad</th>
                                <th class="py-2 px-2 text-right font-medium text-gray-900 w-20">Cant.</th>
                                <th class="py-2 px-2 text-right font-medium text-gray-900 w-28">P. Unitario</th>
                                <th class="py-2 px-2 text-right font-medium text-gray-900 w-20">ITBIS</th>
                                <th class="py-2 px-2 text-right font-medium text-gray-900 w-28">Total RD$</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($articles as $i => $art)
                            <tr x-data="articleRow({{ json_encode([
                                'qty' => $art['cantidad'] ?? 1,
                                'price' => $art['precio_unitario_estimado'] ?? 0,
                                'itbis' => 18,
                            ]) }})">
                                <td class="py-2 pr-2 text-gray-500">{{ $i + 1 }}</td>
                                <td class="py-2 px-2 text-gray-700">
                                    {{ $art['descripcion_usuario'] ?? $art['descripcion_articulo'] ?? '—' }}
                                    <input type="hidden" name="articles[{{ $i }}][descripcion]" value="{{ $art['descripcion_usuario'] ?? $art['descripcion_articulo'] ?? '' }}">
                                </td>
                                <td class="py-2 px-2 text-gray-500">{{ $art['unidad_medida'] ?? '—' }}</td>
                                <td class="py-2 px-2">
                                    <input type="number" name="articles[{{ $i }}][cantidad]" x-model.number="qty" step="1" min="0"
                                           class="w-full text-right rounded border-0 py-1 text-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-blue-600">
                                </td>
                                <td class="py-2 px-2">
                                    <input type="number" name="articles[{{ $i }}][precio_unitario]" x-model.number="price" step="0.01" min="0"
                                           class="w-full text-right rounded border-0 py-1 text-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-blue-600">
                                </td>
                                <td class="py-2 px-2">
                                    <select name="articles[{{ $i }}][itbis]" x-model.number="itbis"
                                            class="w-full text-right rounded border-0 py-1 text-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-blue-600">
                                        <option value="0">0%</option>
                                        <option value="16">16%</option>
                                        <option value="18" selected>18%</option>
                                    </select>
                                </td>
                                <td class="py-2 px-2 text-right font-medium text-gray-900" x-text="total()"></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- ── Sticky footer ───────────────────────────────────────────── --}}
        <div class="sticky bottom-0 z-10 mt-6 -mx-4 border-t border-gray-200 bg-white px-4 py-4 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8 shadow-[0_-2px_8px_rgba(0,0,0,0.06)]">
            <div class="flex items-center justify-between max-w-7xl mx-auto">
                <a href="{{ route('convocatorias.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Cancelar</a>
                <div class="flex items-center gap-4">
                    <span class="text-sm text-gray-500" x-show="selectedCount > 0" x-text="selectedCount + ' documento' + (selectedCount !== 1 ? 's' : '')"></span>
                    <button type="submit" :disabled="selectedCount === 0"
                            class="rounded-md bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 disabled:opacity-40 disabled:cursor-not-allowed">
                        Solicitar prellenado
                    </button>
                </div>
            </div>
        </div>

    </form>
</div>

@if($errors->any())
<div class="fixed bottom-20 right-6 z-50 rounded-md bg-red-50 p-4 text-sm text-red-800 ring-1 ring-inset ring-red-200 max-w-sm">
    {{ $errors->first() }}
</div>
@endif

<script>
function prellenado() {
    return {
        selected: [],
        docSearch: '',
        get selectedCount() { return this.selected.length; },
        categoryVisible(category) {
            if (this.docSearch === '') return true;
            // Show category if any template label matches
            return true; // Individual templates handle their own visibility
        },
    };
}

function articleRow(init) {
    return {
        qty: init.qty,
        price: init.price,
        itbis: init.itbis,
        total() {
            const subtotal = this.qty * this.price;
            const tax = subtotal * (this.itbis / 100);
            return (subtotal + tax).toLocaleString('en', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
    };
}
</script>
@endsection
