@extends('layouts.app')
@section('title', 'Perfil de Empresa')

@section('content')
<div class="mx-auto max-w-4xl px-4 py-10 sm:px-6 lg:px-8">

    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-base font-semibold text-gray-900">Perfil de empresa</h1>
            <p class="mt-1 text-sm text-gray-500">Información de la empresa usada para auto-completar formularios de oferta.</p>
        </div>
    </div>

    {{-- RPE / CPA expiry warnings --}}
    @if($company->exists)
        @php
            $rpeDays = $company->rpeExpiryDays();
            $cpaDays = $company->cpaExpiryDays();
        @endphp
        @if($rpeDays !== null && $rpeDays <= 30)
            <div class="mt-6 rounded-md {{ $rpeDays < 0 ? 'bg-red-50' : 'bg-yellow-50' }} p-4">
                <p class="text-sm font-medium {{ $rpeDays < 0 ? 'text-red-800' : 'text-yellow-800' }}">
                    @if($rpeDays < 0)
                        RPE vencido hace {{ abs($rpeDays) }} días.
                    @else
                        RPE vence en {{ $rpeDays }} día{{ $rpeDays !== 1 ? 's' : '' }}.
                    @endif
                    Número: {{ $company->rpe_numero ?? 'N/D' }} — Vence: {{ $company->rpe_vence?->format('d/m/Y') }}
                </p>
            </div>
        @endif
        @if($cpaDays !== null && $cpaDays <= 30)
            <div class="mt-3 rounded-md {{ $cpaDays < 0 ? 'bg-red-50' : 'bg-yellow-50' }} p-4">
                <p class="text-sm font-medium {{ $cpaDays < 0 ? 'text-red-800' : 'text-yellow-800' }}">
                    @if($cpaDays < 0)
                        CPA vencido hace {{ abs($cpaDays) }} días.
                    @else
                        CPA vence en {{ $cpaDays }} día{{ $cpaDays !== 1 ? 's' : '' }}.
                    @endif
                    Número: {{ $company->cpa_numero ?? 'N/D' }} — Vence: {{ $company->cpa_vence?->format('d/m/Y') }}
                </p>
            </div>
        @endif
    @endif

    <form id="empresa-form" method="POST" action="{{ route('empresa.update') }}" class="mt-8 space-y-8">
        @csrf

        {{-- Section 1: Company info --}}
        <div class="rounded-xl border border-gray-200 bg-white">
            <div class="border-b border-gray-200 px-6 py-4">
                <h2 class="text-sm font-semibold text-gray-900">Datos de la empresa</h2>
            </div>
            <div class="grid grid-cols-1 gap-x-6 gap-y-5 px-6 py-6 sm:grid-cols-2">

                <div class="sm:col-span-2">
                    <label for="razon_social" class="block text-sm font-medium text-gray-900">Razón social <span class="text-red-500">*</span></label>
                    <input type="text" id="razon_social" name="razon_social" required
                           value="{{ old('razon_social', $company->razon_social) }}"
                           class="mt-1.5 w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600 @error('razon_social') outline-red-500 @enderror"/>
                    @error('razon_social')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="nombre_comercial" class="block text-sm font-medium text-gray-900">Nombre comercial</label>
                    <input type="text" id="nombre_comercial" name="nombre_comercial"
                           value="{{ old('nombre_comercial', $company->nombre_comercial) }}"
                           class="mt-1.5 w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600"/>
                </div>

                <div>
                    <label for="rnc" class="block text-sm font-medium text-gray-900">RNC</label>
                    <input type="text" id="rnc" name="rnc" maxlength="20"
                           value="{{ old('rnc', $company->rnc) }}"
                           class="mt-1.5 w-full rounded-md bg-white px-3 py-2 text-sm font-mono text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600"/>
                </div>

                <div class="sm:col-span-2">
                    <label for="direccion" class="block text-sm font-medium text-gray-900">Dirección</label>
                    <input type="text" id="direccion" name="direccion"
                           value="{{ old('direccion', $company->direccion) }}"
                           class="mt-1.5 w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600"/>
                </div>

                <div>
                    <label for="municipio" class="block text-sm font-medium text-gray-900">Municipio</label>
                    <input type="text" id="municipio" name="municipio"
                           value="{{ old('municipio', $company->municipio) }}"
                           class="mt-1.5 w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600"/>
                </div>

                <div>
                    <label for="provincia" class="block text-sm font-medium text-gray-900">Provincia</label>
                    <input type="text" id="provincia" name="provincia"
                           value="{{ old('provincia', $company->provincia) }}"
                           class="mt-1.5 w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600"/>
                </div>

                <div>
                    <label for="telefono" class="block text-sm font-medium text-gray-900">Teléfono</label>
                    <input type="text" id="telefono" name="telefono" maxlength="30"
                           value="{{ old('telefono', $company->telefono) }}"
                           class="mt-1.5 w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600"/>
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-900">Correo electrónico</label>
                    <input type="email" id="email" name="email"
                           value="{{ old('email', $company->email) }}"
                           class="mt-1.5 w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600"/>
                </div>

                <div class="sm:col-span-2">
                    <label for="web" class="block text-sm font-medium text-gray-900">Sitio web</label>
                    <input type="url" id="web" name="web"
                           placeholder="https://ejemplo.com"
                           value="{{ old('web', $company->web) }}"
                           class="mt-1.5 w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600"/>
                </div>

            </div>
        </div>

        {{-- Section 2: Legal representative --}}
        <div class="rounded-xl border border-gray-200 bg-white">
            <div class="border-b border-gray-200 px-6 py-4">
                <h2 class="text-sm font-semibold text-gray-900">Representante legal</h2>
            </div>
            <div class="grid grid-cols-1 gap-x-6 gap-y-5 px-6 py-6 sm:grid-cols-2">

                <div class="sm:col-span-2">
                    <label for="rep_legal_nombre" class="block text-sm font-medium text-gray-900">Nombre completo</label>
                    <input type="text" id="rep_legal_nombre" name="rep_legal_nombre"
                           value="{{ old('rep_legal_nombre', $company->rep_legal_nombre) }}"
                           class="mt-1.5 w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600"/>
                </div>

                <div>
                    <label for="rep_legal_cedula" class="block text-sm font-medium text-gray-900">Cédula</label>
                    <input type="text" id="rep_legal_cedula" name="rep_legal_cedula" maxlength="20"
                           value="{{ old('rep_legal_cedula', $company->rep_legal_cedula) }}"
                           class="mt-1.5 w-full rounded-md bg-white px-3 py-2 text-sm font-mono text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600"/>
                </div>

                <div>
                    <label for="rep_legal_cargo" class="block text-sm font-medium text-gray-900">Cargo</label>
                    <input type="text" id="rep_legal_cargo" name="rep_legal_cargo"
                           placeholder="Gerente General"
                           value="{{ old('rep_legal_cargo', $company->rep_legal_cargo) }}"
                           class="mt-1.5 w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600"/>
                </div>
                <div>
                    <label for="rep_legal_nacionalidad" class="block text-sm font-medium text-gray-900">Nacionalidad</label>
                    <input type="text" id="rep_legal_nacionalidad" name="rep_legal_nacionalidad"
                           placeholder="Dominicano/a"
                           value="{{ old('rep_legal_nacionalidad', $company->rep_legal_nacionalidad) }}"
                           class="mt-1.5 w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600"/>
                </div>
                <div>
                    <label for="rep_legal_estado_civil" class="block text-sm font-medium text-gray-900">Estado civil</label>
                    <select id="rep_legal_estado_civil" name="rep_legal_estado_civil"
                            class="mt-1.5 w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600">
                        <option value="">— Seleccionar —</option>
                        @foreach(['Soltero/a','Casado/a','Unión libre','Divorciado/a','Viudo/a'] as $ec)
                        <option value="{{ $ec }}" {{ old('rep_legal_estado_civil', $company->rep_legal_estado_civil) === $ec ? 'selected' : '' }}>{{ $ec }}</option>
                        @endforeach
                    </select>
                </div>

            </div>
        </div>

        {{-- Section 3: Certifications --}}
        <div class="rounded-xl border border-gray-200 bg-white">
            <div class="border-b border-gray-200 px-6 py-4">
                <h2 class="text-sm font-semibold text-gray-900">Habilitaciones</h2>
                <p class="mt-0.5 text-xs text-gray-500">RPE y CPA — requeridos en la mayoría de las licitaciones.</p>
            </div>
            <div class="grid grid-cols-1 gap-x-6 gap-y-5 px-6 py-6 sm:grid-cols-2">

                <div>
                    <label for="rpe_numero" class="block text-sm font-medium text-gray-900">Número RPE</label>
                    <input type="text" id="rpe_numero" name="rpe_numero" maxlength="50"
                           value="{{ old('rpe_numero', $company->rpe_numero) }}"
                           class="mt-1.5 w-full rounded-md bg-white px-3 py-2 text-sm font-mono text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600"/>
                </div>

                <div>
                    <label for="rpe_vence" class="block text-sm font-medium text-gray-900">RPE — fecha de vencimiento</label>
                    <input type="date" id="rpe_vence" name="rpe_vence"
                           value="{{ old('rpe_vence', $company->rpe_vence?->format('Y-m-d')) }}"
                           class="mt-1.5 w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600"/>
                </div>

                <div>
                    <label for="cpa_numero" class="block text-sm font-medium text-gray-900">Número CPA</label>
                    <input type="text" id="cpa_numero" name="cpa_numero" maxlength="50"
                           value="{{ old('cpa_numero', $company->cpa_numero) }}"
                           class="mt-1.5 w-full rounded-md bg-white px-3 py-2 text-sm font-mono text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600"/>
                </div>

                <div>
                    <label for="cpa_vence" class="block text-sm font-medium text-gray-900">CPA — fecha de vencimiento</label>
                    <input type="date" id="cpa_vence" name="cpa_vence"
                           value="{{ old('cpa_vence', $company->cpa_vence?->format('Y-m-d')) }}"
                           class="mt-1.5 w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600"/>
                </div>

            </div>
        </div>

    </form>

    {{-- Section 4: Corporate images --}}
    <div class="mt-8 rounded-xl border border-gray-200 bg-white">
        <div class="border-b border-gray-200 px-6 py-4">
            <h2 class="text-sm font-semibold text-gray-900">Imágenes corporativas</h2>
            <p class="mt-0.5 text-xs text-gray-500">Firma, sello y logo para insertar en formularios generados.</p>
        </div>
        <div class="grid grid-cols-1 gap-6 px-6 py-6 sm:grid-cols-3">

            @foreach([
                ['type' => 'firma', 'label' => 'Firma del representante legal', 'path' => $company->firma_path],
                ['type' => 'sello', 'label' => 'Sello de la empresa', 'path' => $company->sello_path],
                ['type' => 'logo',  'label' => 'Logo de la empresa', 'path' => $company->logo_path],
            ] as $img)
            <div>
                <p class="text-sm font-medium text-gray-900 mb-3">{{ $img['label'] }}</p>

                @if($img['path'])
                {{-- Has image --}}
                <div class="relative group">
                    <div class="flex items-center justify-center rounded-lg border border-gray-200 bg-gray-50 p-4 h-40">
                        <img src="{{ asset('storage/'.$img['path']) }}" alt="{{ $img['label'] }}"
                             class="max-h-full max-w-full object-contain"/>
                    </div>
                    <div class="mt-2 flex items-center justify-between">
                        <form method="POST" action="{{ route('empresa.uploadImage') }}" enctype="multipart/form-data" class="flex-1">
                            @csrf
                            <input type="hidden" name="type" value="{{ $img['type'] }}"/>
                            <label class="cursor-pointer text-xs font-medium text-blue-600 hover:text-blue-500">
                                Reemplazar
                                <input type="file" name="image" accept="image/*" class="hidden"
                                       onchange="this.form.submit()"/>
                            </label>
                        </form>
                        <form method="POST" action="{{ route('empresa.deleteImage') }}"
                              onsubmit="return confirm('¿Eliminar esta imagen?')">
                            @csrf @method('DELETE')
                            <input type="hidden" name="type" value="{{ $img['type'] }}"/>
                            <button type="submit" class="text-xs font-medium text-red-500 hover:text-red-700">Eliminar</button>
                        </form>
                    </div>
                </div>
                @else
                {{-- Empty state --}}
                <form method="POST" action="{{ route('empresa.uploadImage') }}" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="type" value="{{ $img['type'] }}"/>
                    <label class="flex h-40 cursor-pointer flex-col items-center justify-center rounded-lg border-2 border-dashed border-gray-300 bg-gray-50 hover:border-blue-400 hover:bg-blue-50/50 transition-colors">
                        <svg class="size-8 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0 0 22.5 18.75V5.25A2.25 2.25 0 0 0 20.25 3H3.75A2.25 2.25 0 0 0 1.5 5.25v13.5A2.25 2.25 0 0 0 3.75 21Z"/>
                        </svg>
                        <span class="mt-2 text-xs font-medium text-gray-500">Subir imagen</span>
                        <span class="mt-0.5 text-[10px] text-gray-400">PNG, JPG — máx. 2 MB</span>
                        <input type="file" name="image" accept="image/*" class="hidden"
                               onchange="this.form.submit()"/>
                    </label>
                </form>
                @endif
            </div>
            @endforeach

        </div>
    </div>

    {{-- Save button for the main form --}}
    <div class="mt-8 flex justify-end">
        <button type="submit" form="empresa-form"
                class="inline-flex items-center gap-x-2 rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-xs hover:bg-blue-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
            Guardar cambios
        </button>
    </div>

</div>
@endsection
