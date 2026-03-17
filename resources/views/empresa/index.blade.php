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

    <form method="POST" action="{{ route('empresa.update') }}" class="mt-8 space-y-8">
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

        <div class="flex justify-end">
            <button type="submit"
                    class="inline-flex items-center gap-x-2 rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-xs hover:bg-blue-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                Guardar cambios
            </button>
        </div>

    </form>
</div>
@endsection
