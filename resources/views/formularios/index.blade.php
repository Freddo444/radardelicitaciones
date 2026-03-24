@extends('layouts.app')
@section('title', 'Formularios')

@section('content')
<div class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">

    <div>
        <h1 class="text-base font-semibold text-gray-900">Generador de formularios</h1>
        <p class="mt-1 text-sm text-gray-500">Auto-completa los formularios SNCC desde la bóveda de datos y los genera como DOCX.</p>
    </div>

    @if(session('success'))
        <div class="mt-4 rounded-md bg-green-50 p-4 text-sm text-green-800 ring-1 ring-inset ring-green-200 flex items-center justify-between">
            <span>{{ session('success') }}</span>
            @if(session('download_id'))
                <a href="{{ route('formularios.download', session('download_id')) }}"
                   class="ml-4 inline-flex items-center gap-x-1.5 rounded-md bg-green-700 px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-green-600">
                    Descargar DOCX
                </a>
            @endif
        </div>
    @endif

    @if($errors->any())
        <div class="mt-4 rounded-md bg-red-50 p-4 text-sm text-red-800 ring-1 ring-inset ring-red-200">
            {{ $errors->first() }}
        </div>
    @endif

    {{-- ── Generator form ──────────────────────────────────────────── --}}
    <div class="mt-8 bg-white shadow-sm ring-1 ring-gray-900/5 rounded-xl px-4 py-6 sm:p-6">
        <h2 class="text-sm font-semibold text-gray-900 mb-5">Generar formulario</h2>

        <form method="POST" action="{{ route('formularios.generate') }}" x-data="formSelector()">
            @csrf

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">

                {{-- Form code --}}
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-900">Formulario <span class="text-red-500">*</span></label>
                    <select name="form_code" x-model="formCode" required
                            class="mt-1 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-blue-600 sm:text-sm">
                        <option value="">— Seleccionar —</option>
                        @php
                            $groups = [
                                'Formularios de oferta' => ['CARATULA.A','CARATULA.B','SNCC.F.033','SNCC.F.034','SNCC.F.035','SNCC.F.036','SNCC.F.037','SNCC.F.040','SNCC.F.042','SNCC.F.047','SNCC.F.056','OFERTA.TECNICA'],
                                'Documentos técnicos'   => ['SNCC.D.038','SNCC.D.043','SNCC.D.044','SNCC.D.045','SNCC.D.048','SNCC.D.049','SNCC.D.051','SNCC.D.052'],
                                'Cartas'                => ['CARTA.ACEPTACION','CARTA.ENTREGA','CARTA.PAGO','CARTA.GARANTIA','CARTA.PRECIO'],
                                'Aseguradoras / Fianzas'=> ['FIANZA.CDS','FIANZA.DCS','FIANZA.DCS.FC','FIANZA.APS','FIANZA.RESERVAS'],
                                'Declaraciones juradas' => ['DECL.JURADA','DECL.COMPROMISO_ETICO','DECL.INTEGRIDAD','DECL.CRONOGRAMA','DECL.GARANTIA','DECL.NATURALES'],
                                'Formularios FL'        => ['FL.01','FL.02','FL.03','FL.04','FL.05','FL.06'],
                                'Ley 47-25'            => ['LEY47.JURADA','LEY47.COLUSION','LEY47.JURIDICAS','LEY47.BENEFICIARIOS','LEY47.BENEFICIARIO.FORM','LEY47.DILIGENCIA','LEY47.SIG007'],
                            ];
                            $forms = \App\Models\OfferGeneratedFile::$forms;
                        @endphp
                        @foreach($groups as $groupLabel => $codes)
                            <optgroup label="{{ $groupLabel }}">
                                @foreach($codes as $code)
                                    <option value="{{ $code }}">{{ $forms[$code] }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>

                {{-- Process context (most forms) --}}
                <div x-show="needsProcess" x-cloak>
                    <label class="block text-sm font-medium text-gray-900">Ref. del proceso</label>
                    <input type="text" name="proceso_ref" placeholder="ej. MINPRE-CCC-2026-001"
                           class="mt-1 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-blue-600 sm:text-sm">
                </div>
                <div x-show="needsProcess" x-cloak>
                    <label class="block text-sm font-medium text-gray-900">Entidad contratante</label>
                    <input type="text" name="entidad_nombre"
                           class="mt-1 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-blue-600 sm:text-sm">
                </div>
                <div x-show="needsProcess" x-cloak>
                    <label class="block text-sm font-medium text-gray-900">Nombre / objeto del proceso</label>
                    <input type="text" name="proceso_nombre"
                           class="mt-1 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-blue-600 sm:text-sm">
                </div>
                <div x-show="needsProcess" x-cloak>
                    <label class="block text-sm font-medium text-gray-900">Tipo de procedimiento</label>
                    <input type="text" name="proceso_tipo" placeholder="ej. Licitación Pública Nacional"
                           class="mt-1 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-blue-600 sm:text-sm">
                </div>

                {{-- Personnel selector (D.045, D.048, F.037) --}}
                <div x-show="needsPersonnel" x-cloak class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-900">Personal <span class="text-red-500">*</span></label>
                    <select name="personnel_id" :required="needsPersonnel"
                            class="mt-1 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-blue-600 sm:text-sm">
                        <option value="">— Seleccionar persona —</option>
                        @foreach($personnel as $p)
                            <option value="{{ $p->id }}">{{ $p->nombre }}{{ $p->cargo ? ' — ' . $p->cargo : '' }}</option>
                        @endforeach
                    </select>
                </div>
                <div x-show="needsPersonnel" x-cloak>
                    <label class="block text-sm font-medium text-gray-900">Cargo propuesto en esta oferta</label>
                    <input type="text" name="cargo_propuesto" placeholder="ej. Director Técnico"
                           class="mt-1 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-blue-600 sm:text-sm">
                </div>

                {{-- Projects (D.049) --}}
                <div x-show="needsProjects" x-cloak class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-900">Proyectos a incluir <span class="text-red-500">*</span></label>
                    <div class="mt-2 space-y-2 max-h-48 overflow-y-auto rounded-md border border-gray-200 p-3">
                        @foreach($projects as $proj)
                            <label class="flex items-start gap-x-2 text-sm text-gray-700 cursor-pointer">
                                <input type="checkbox" name="project_ids[]" value="{{ $proj->id }}"
                                       class="mt-0.5 rounded border-gray-300 text-blue-600 focus:ring-blue-600">
                                <span>{{ $proj->nombre }}
                                    <span class="text-gray-400">— {{ $proj->cliente }}</span>
                                    <span class="text-gray-400">{{ $proj->periodoLabel() }}</span>
                                </span>
                            </label>
                        @endforeach
                        @if($projects->isEmpty())
                            <p class="text-sm text-gray-400">Sin proyectos registrados.</p>
                        @endif
                    </div>
                </div>

                {{-- Declaraciones extras --}}
                <div x-show="needsDecl" x-cloak>
                    <label class="block text-sm font-medium text-gray-900">Nacionalidad del representante</label>
                    <input type="text" name="rep_nacionalidad" value="Dominicano/a"
                           class="mt-1 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-blue-600 sm:text-sm">
                </div>
                <div x-show="needsDecl" x-cloak>
                    <label class="block text-sm font-medium text-gray-900">Estado civil del representante</label>
                    <input type="text" name="rep_estado_civil" placeholder="ej. Soltero/a"
                           class="mt-1 block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-blue-600 sm:text-sm">
                </div>

            </div>

            <div class="mt-6 flex justify-end">
                <button type="submit" :disabled="!formCode"
                        class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 disabled:opacity-40 disabled:cursor-not-allowed focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                    Generar DOCX
                </button>
            </div>
        </form>
    </div>

    {{-- ── Generated files ─────────────────────────────────────────── --}}
    <div class="mt-12">
        <h2 class="text-sm font-semibold text-gray-900">Formularios generados (standalone)</h2>
        <p class="text-xs text-gray-500 mt-0.5">Los generados dentro de una oferta aparecen en la ficha de esa oferta.</p>

        @if($generated->isEmpty())
            <p class="mt-4 text-sm text-gray-500">Sin formularios generados aún.</p>
        @else
            <ul class="mt-4 divide-y divide-gray-200">
                @foreach($generated as $f)
                <li class="flex items-center justify-between py-3 gap-x-4">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-gray-900 truncate">{{ \App\Models\OfferGeneratedFile::$forms[$f->form_code] ?? $f->form_code }}</p>
                        <p class="text-xs text-gray-500">
                            {{ $f->generated_at->format('d/m/Y H:i') }}
                            @if($f->file_size) · {{ $f->fileSizeFormatted() }}@endif
                            @if($f->supersedes_id) · <span class="text-amber-600">Reemplazó versión anterior</span>@endif
                        </p>
                    </div>
                    <a href="{{ route('formularios.download', $f) }}"
                       class="shrink-0 text-sm text-blue-600 hover:text-blue-500 font-medium">Descargar</a>
                </li>
                @endforeach
            </ul>
        @endif
    </div>

</div>

<script>
function formSelector() {
    return {
        formCode: '',
        get needsProcess() {
            return this.formCode !== '' && !['DECL.JURADA','DECL.COMPROMISO_ETICO'].includes(this.formCode);
        },
        get needsPersonnel() {
            return ['SNCC.D.045','SNCC.D.048','SNCC.F.037'].includes(this.formCode);
        },
        get needsProjects() {
            return this.formCode === 'SNCC.D.049';
        },
        get needsDecl() {
            return ['DECL.JURADA','DECL.COMPROMISO_ETICO'].includes(this.formCode);
        },
    }
}
</script>
@endsection
