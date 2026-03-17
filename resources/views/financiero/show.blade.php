@extends('layouts.app')
@section('title', 'Financiero ' . $financiero->anio_fiscal)

@section('content')
<div class="mx-auto max-w-4xl px-4 py-10 sm:px-6 lg:px-8">

    {{-- Header --}}
    <div class="sm:flex sm:items-center sm:justify-between">
        <nav class="flex items-center gap-x-2 text-sm text-gray-500">
            <a href="{{ route('financiero.index') }}" class="hover:text-gray-700">Financiero</a>
            <span>/</span>
            <span class="text-gray-900 font-medium">Año {{ $financiero->anio_fiscal }}</span>
        </nav>
        <form method="POST" action="{{ route('financiero.destroy', $financiero) }}"
              onsubmit="return confirm('¿Eliminar este año fiscal y sus documentos?')"
              class="mt-4 sm:mt-0">
            @csrf @method('DELETE')
            <button type="submit"
                    class="inline-flex items-center gap-x-1.5 rounded-md bg-red-50 px-2.5 py-1.5 text-sm font-semibold text-red-700 ring-1 ring-inset ring-red-200 hover:bg-red-100">
                Eliminar año
            </button>
        </form>
    </div>

    @if(session('success'))
        <div class="mt-4 rounded-md bg-green-50 p-4 text-sm text-green-800 ring-1 ring-inset ring-green-200">
            {{ session('success') }}
        </div>
    @endif

    {{-- ── Calculated indices (read-only display) ─────────────── --}}
    <div class="mt-8 grid grid-cols-2 gap-4 sm:grid-cols-4">
        @php
            $indices = [
                ['label' => 'Solvencia', 'value' => $financiero->solvencia(), 'override' => $financiero->solvencia_override, 'ref' => '≥ 1.0'],
                ['label' => 'Liquidez', 'value' => $financiero->liquidez(), 'override' => $financiero->liquidez_override, 'ref' => '≥ 1.0'],
                ['label' => 'Endeudamiento', 'value' => $financiero->endeudamiento(), 'override' => $financiero->endeudamiento_override, 'ref' => '≤ 0.60'],
                ['label' => 'Capital de trabajo', 'value' => $financiero->capitalTrabajo(), 'override' => $financiero->capital_trabajo_override, 'ref' => null, 'monto' => true],
            ];
        @endphp
        @foreach($indices as $idx)
        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-900/5">
            <div class="flex items-center justify-between">
                <p class="text-xs font-medium text-gray-500">{{ $idx['label'] }}</p>
                @if($idx['override'])
                    <span class="inline-flex items-center rounded bg-amber-100 px-1 text-xs text-amber-700 font-medium">Manual</span>
                @endif
            </div>
            <p class="mt-1 text-2xl font-semibold tracking-tight {{ $idx['override'] ? 'text-amber-600' : 'text-gray-900' }}">
                @if(isset($idx['monto']) && $idx['monto'])
                    {{ $financiero->formatMonto($idx['value']) }}
                @else
                    {{ $financiero->formatIndice($idx['value']) }}
                @endif
            </p>
            @if($idx['ref'])
                <p class="mt-0.5 text-xs text-gray-400">Ref. DGCP: {{ $idx['ref'] }}</p>
            @endif
        </div>
        @endforeach
    </div>

    {{-- ── Balance form ────────────────────────────────────────── --}}
    <form method="POST" action="{{ route('financiero.update', $financiero) }}" class="mt-8 space-y-6">
        @csrf

        <div class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-xl">
            <div class="px-4 py-5 sm:p-6">
                <div class="flex items-center justify-between mb-5">
                    <h2 class="text-sm font-semibold text-gray-900">Estado financiero — {{ $financiero->anio_fiscal }}</h2>
                    <select name="currency"
                            class="rounded-md border-0 py-1.5 pl-3 pr-8 text-gray-900 ring-1 ring-inset ring-gray-300 text-sm focus:ring-2 focus:ring-blue-600">
                        <option value="DOP" @selected($financiero->currency === 'DOP')>DOP</option>
                        <option value="USD" @selected($financiero->currency === 'USD')>USD</option>
                    </select>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    @php
                        $fields = [
                            ['name' => 'activo_total',      'label' => 'Activo total'],
                            ['name' => 'activo_circulante', 'label' => 'Activo circulante'],
                            ['name' => 'inventarios',       'label' => 'Inventarios'],
                            ['name' => 'pasivo_total',      'label' => 'Pasivo total'],
                            ['name' => 'pasivo_circulante', 'label' => 'Pasivo circulante'],
                            ['name' => 'patrimonio',        'label' => 'Patrimonio'],
                            ['name' => 'ingresos',          'label' => 'Ingresos (ventas)'],
                            ['name' => 'utilidad',          'label' => 'Utilidad neta'],
                        ];
                    @endphp
                    @foreach($fields as $f)
                    <div>
                        <label class="block text-sm font-medium text-gray-900">{{ $f['label'] }}</label>
                        <input type="number" name="{{ $f['name'] }}" step="0.01"
                               value="{{ old($f['name'], $financiero->{$f['name']}) }}"
                               class="mt-1 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-blue-600 sm:text-sm">
                    </div>
                    @endforeach
                </div>

                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-900">Notas</label>
                    <textarea name="notas" rows="2"
                              class="mt-1 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-blue-600 sm:text-sm">{{ old('notas', $financiero->notas) }}</textarea>
                </div>
            </div>
        </div>

        {{-- Override section --}}
        <div class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-xl">
            <div class="px-4 py-5 sm:p-6">
                <h2 class="text-sm font-semibold text-gray-900 mb-1">Valores manuales (override)</h2>
                <p class="text-xs text-gray-500 mb-5">Deja en blanco para usar el valor calculado automáticamente. Usa esto solo si los índices presentados en la licitación difieren del cálculo estándar.</p>
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Solvencia</label>
                        <input type="number" name="solvencia_override" step="0.0001" min="0"
                               value="{{ old('solvencia_override', $financiero->solvencia_override) }}"
                               class="mt-1 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-amber-300 focus:ring-2 focus:ring-amber-500 sm:text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Liquidez</label>
                        <input type="number" name="liquidez_override" step="0.0001" min="0"
                               value="{{ old('liquidez_override', $financiero->liquidez_override) }}"
                               class="mt-1 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-amber-300 focus:ring-2 focus:ring-amber-500 sm:text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Endeudamiento</label>
                        <input type="number" name="endeudamiento_override" step="0.0001" min="0"
                               value="{{ old('endeudamiento_override', $financiero->endeudamiento_override) }}"
                               class="mt-1 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-amber-300 focus:ring-2 focus:ring-amber-500 sm:text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Capital de trabajo</label>
                        <input type="number" name="capital_trabajo_override" step="0.01"
                               value="{{ old('capital_trabajo_override', $financiero->capital_trabajo_override) }}"
                               class="mt-1 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-amber-300 focus:ring-2 focus:ring-amber-500 sm:text-sm">
                    </div>
                </div>
                <div class="mt-3">
                    <label class="block text-xs font-medium text-gray-700">Razón del override</label>
                    <input type="text" name="override_razon" maxlength="500"
                           value="{{ old('override_razon', $financiero->override_razon) }}"
                           placeholder="ej. Cifras reexpresadas según NIIF, difieren del cálculo aritmético"
                           class="mt-1 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-blue-600 sm:text-sm">
                </div>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit"
                    class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                Guardar y recalcular índices
            </button>
        </div>
    </form>

    {{-- ── Supporting documents ───────────────────────────────── --}}
    <div class="mt-12 space-y-5">
        <h2 class="text-sm font-semibold text-gray-900">Documentos de soporte</h2>

        @foreach([['tipo' => 'ir2', 'label' => 'IR-2 / Declaración ISR'], ['tipo' => 'estado_financiero', 'label' => 'Estado financiero certificado (CPA)']] as $doc)
        <div class="bg-white shadow-sm ring-1 ring-gray-900/5 rounded-xl px-4 py-4 sm:px-6">
            <div class="sm:flex sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-900">{{ $doc['label'] }}</p>
                    @if($financiero->{'path_' . $doc['tipo']})
                        <p class="text-xs text-gray-500 mt-0.5">{{ $financiero->{'filename_' . $doc['tipo']} }}</p>
                    @else
                        <p class="text-xs text-gray-400 mt-0.5">Sin documento</p>
                    @endif
                </div>
                <div class="mt-3 sm:mt-0 flex items-center gap-x-3">
                    @if($financiero->{'path_' . $doc['tipo']})
                        <a href="{{ route('financiero.documents.download', [$financiero, $doc['tipo']]) }}"
                           class="text-sm text-blue-600 hover:text-blue-500">Descargar</a>
                    @endif
                    <form method="POST" action="{{ route('financiero.documents.upload', $financiero) }}"
                          enctype="multipart/form-data" class="flex items-center gap-x-2">
                        @csrf
                        <input type="hidden" name="tipo" value="{{ $doc['tipo'] }}">
                        <input type="file" name="file" accept=".pdf" required class="text-xs text-gray-700">
                        <button type="submit"
                                class="rounded-md bg-white px-2.5 py-1.5 text-xs font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                            {{ $financiero->{'path_' . $doc['tipo']} ? 'Reemplazar' : 'Subir' }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @endforeach
    </div>

</div>
@endsection
