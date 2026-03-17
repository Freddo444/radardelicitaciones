@extends('layouts.app')
@section('title', 'Bóveda de Documentos')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">

    {{-- Header --}}
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-base font-semibold text-gray-900">Bóveda de documentos</h1>
            <p class="mt-1 text-sm text-gray-500">Documentos corporativos reutilizables para ofertas.</p>
        </div>
        <div class="mt-4 sm:mt-0">
            <button command="show-modal" commandfor="upload-drawer"
                    class="inline-flex items-center gap-x-2 rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-xs hover:bg-blue-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="-ml-0.5 size-5">
                    <path d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z"/>
                </svg>
                Subir documento
            </button>
        </div>
    </div>

    {{-- Expiry alert banner --}}
    @if($expiryAlerts > 0)
    <div class="mt-6 rounded-md bg-yellow-50 p-4">
        <div class="flex">
            <svg viewBox="0 0 20 20" fill="currentColor" class="size-5 shrink-0 text-yellow-400" aria-hidden="true">
                <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495ZM10 5a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0v-3.5A.75.75 0 0 1 10 5Zm0 9a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd"/>
            </svg>
            <p class="ml-3 text-sm text-yellow-800">
                <span class="font-semibold">{{ $expiryAlerts }} documento{{ $expiryAlerts !== 1 ? 's' : '' }}</span>
                vence{{ $expiryAlerts !== 1 ? 'n' : '' }} en los próximos 30 días o ya {{ $expiryAlerts !== 1 ? 'vencieron' : 'venció' }}.
            </p>
        </div>
    </div>
    @endif

    {{-- Document list by category --}}
    <div class="mt-8 space-y-8">

        @php $categoryLabels = \App\Models\VaultDocument::$categories; @endphp

        @forelse($categoryLabels as $key => $label)
            @php $docs = $documents->get($key, collect()); @endphp
            <div>
                <h2 class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $label }}</h2>

                @if($docs->isEmpty())
                    <p class="mt-2 text-sm text-gray-400 italic">Sin documentos en esta categoría.</p>
                @else
                    <ul role="list" class="mt-2 divide-y divide-gray-100 rounded-xl border border-gray-200 bg-white">
                        @foreach($docs as $doc)
                        @php
                            $expiry = $doc->expiryStatus();
                            $expiryColor = match($expiry) {
                                'expired'  => 'text-red-700 bg-red-50 ring-red-600/20',
                                'expiring' => 'text-yellow-800 bg-yellow-50 ring-yellow-600/20',
                                'valid'    => 'text-green-700 bg-green-50 ring-green-600/20',
                                default    => 'text-gray-600 bg-gray-100 ring-gray-500/10',
                            };
                        @endphp
                        <li class="flex items-center justify-between gap-x-6 px-4 py-3">
                            {{-- Left: name + meta --}}
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-x-2 flex-wrap">
                                    <p class="text-sm font-medium text-gray-900 truncate">{{ $doc->name }}</p>
                                    <span class="shrink-0 rounded-md px-1.5 py-0.5 text-xs font-medium ring-1 ring-inset {{ $expiryColor }}">
                                        @if($expiry === 'expired')
                                            Vencido {{ $doc->expires_at->diffForHumans() }}
                                        @elseif($expiry === 'expiring')
                                            Vence {{ $doc->expires_at->diffForHumans() }}
                                        @elseif($expiry === 'valid')
                                            Vence {{ $doc->expires_at->format('d/m/Y') }}
                                        @else
                                            Sin vencimiento
                                        @endif
                                    </span>
                                    @php $copyLabel = \App\Models\VaultDocument::$copyTypes[$doc->copy_type] ?? $doc->copy_type; @endphp
                                    <span class="shrink-0 rounded-md px-1.5 py-0.5 text-xs font-medium text-gray-500 bg-gray-100 ring-1 ring-inset ring-gray-500/10">
                                        {{ $copyLabel }}
                                    </span>
                                    @if($doc->internal_only)
                                    <span class="shrink-0 rounded-md px-1.5 py-0.5 text-xs font-medium text-purple-700 bg-purple-50 ring-1 ring-inset ring-purple-700/10">
                                        Interno
                                    </span>
                                    @endif
                                </div>
                                <div class="mt-0.5 flex flex-wrap items-center gap-x-2 text-xs text-gray-400">
                                    @if($doc->issuer)
                                        <span>{{ $doc->issuer }}</span>
                                        <svg viewBox="0 0 2 2" class="size-0.5 fill-current"><circle r="1" cx="1" cy="1"/></svg>
                                    @endif
                                    @if($doc->document_number)
                                        <span class="font-mono">{{ $doc->document_number }}</span>
                                        <svg viewBox="0 0 2 2" class="size-0.5 fill-current"><circle r="1" cx="1" cy="1"/></svg>
                                    @endif
                                    <span>{{ $doc->filename }}</span>
                                </div>
                            </div>

                            {{-- Right: actions --}}
                            <div class="flex shrink-0 items-center gap-x-1">
                                {{-- Download --}}
                                <a href="{{ route('documentos.download', $doc) }}"
                                   class="rounded-md p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50"
                                   title="Descargar">
                                    <span class="sr-only">Descargar</span>
                                    <svg viewBox="0 0 20 20" fill="currentColor" class="size-4">
                                        <path d="M10.75 2.75a.75.75 0 0 0-1.5 0v8.614L6.295 8.235a.75.75 0 1 0-1.09 1.03l4.25 4.5a.75.75 0 0 0 1.09 0l4.25-4.5a.75.75 0 0 0-1.09-1.03l-2.955 3.129V2.75Z"/>
                                        <path d="M3.5 12.75a.75.75 0 0 0-1.5 0v2.5A2.75 2.75 0 0 0 4.75 18h10.5A2.75 2.75 0 0 0 18 15.25v-2.5a.75.75 0 0 0-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5Z"/>
                                    </svg>
                                </a>
                                {{-- Version history --}}
                                @if($doc->replaces_document_id)
                                <a href="{{ route('documentos.versions', $doc) }}"
                                   class="rounded-md p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-50"
                                   title="Ver versiones anteriores">
                                    <span class="sr-only">Historial</span>
                                    <svg viewBox="0 0 20 20" fill="currentColor" class="size-4">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm.75-13a.75.75 0 0 0-1.5 0v5c0 .414.336.75.75.75h4a.75.75 0 0 0 0-1.5h-3.25V5Z" clip-rule="evenodd"/>
                                    </svg>
                                </a>
                                @endif
                                {{-- Replace --}}
                                <button type="button"
                                        onclick="openReplaceDrawer({{ $doc->id }}, '{{ e($doc->name) }}', '{{ $doc->category }}', '{{ $doc->copy_type }}')"
                                        command="show-modal" commandfor="replace-drawer"
                                        class="rounded-md p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50"
                                        title="Reemplazar archivo">
                                    <span class="sr-only">Reemplazar</span>
                                    <svg viewBox="0 0 20 20" fill="currentColor" class="size-4">
                                        <path fill-rule="evenodd" d="M15.312 11.424a5.5 5.5 0 0 1-9.201 2.466l-.312-.311h2.433a.75.75 0 0 0 0-1.5H3.989a.75.75 0 0 0-.75.75v4.242a.75.75 0 0 0 1.5 0v-2.43l.31.31a7 7 0 0 0 11.712-3.138.75.75 0 0 0-1.449-.39Zm1.23-3.723a.75.75 0 0 0 .219-.53V2.929a.75.75 0 0 0-1.5 0V5.36l-.31-.31A7 7 0 0 0 3.239 8.188a.75.75 0 1 0 1.448.389A5.5 5.5 0 0 1 13.89 6.11l.311.31h-2.432a.75.75 0 0 0 0 1.5h4.243a.75.75 0 0 0 .53-.219Z" clip-rule="evenodd"/>
                                    </svg>
                                </button>
                            </div>
                        </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @empty
            <p class="text-sm text-gray-400">Sin categorías.</p>
        @endforelse

    </div>
</div>

{{-- ── Upload drawer ──────────────────────────────────────────── --}}
<el-dialog>
    <dialog id="upload-drawer" aria-labelledby="upload-drawer-title"
            class="fixed inset-0 size-auto max-h-none max-w-none overflow-hidden bg-transparent backdrop:bg-transparent">
        <div tabindex="0" class="absolute inset-0 pl-10 focus:outline-none sm:pl-16">
            <el-dialog-panel class="ml-auto block size-full max-w-md transform transition duration-500 ease-in-out data-closed:translate-x-full sm:duration-700">
                <div class="relative flex h-full flex-col divide-y divide-gray-200 bg-white shadow-xl">

                    <div class="flex items-start justify-between px-4 py-6 sm:px-6">
                        <h2 id="upload-drawer-title" class="text-base font-semibold text-gray-900">Subir documento</h2>
                        <button type="button" command="close" commandfor="upload-drawer"
                                class="relative ml-3 rounded-md text-gray-400 hover:text-gray-500 focus-visible:outline-2 focus-visible:outline-blue-600">
                            <span class="sr-only">Cerrar</span>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="size-6">
                                <path d="M6 18 18 6M6 6l12 12" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                    </div>

                    <form method="POST" action="{{ route('documentos.store') }}" enctype="multipart/form-data"
                          class="flex min-h-0 flex-1 flex-col">
                        @csrf
                        <div class="flex-1 space-y-5 overflow-y-auto px-4 py-6 sm:px-6">

                            <div>
                                <label for="up-name" class="block text-sm font-medium text-gray-900">Nombre del documento <span class="text-red-500">*</span></label>
                                <input type="text" id="up-name" name="name" required
                                       placeholder="Ej: Certificado de cumplimiento fiscal"
                                       class="mt-1.5 w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600"/>
                            </div>

                            <div>
                                <label for="up-category" class="block text-sm font-medium text-gray-900">Categoría <span class="text-red-500">*</span></label>
                                <select id="up-category" name="category" required
                                        class="mt-1.5 w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600">
                                    @foreach(\App\Models\VaultDocument::$categories as $val => $lbl)
                                        <option value="{{ $val }}">{{ $lbl }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="up-file" class="block text-sm font-medium text-gray-900">Archivo <span class="text-red-500">*</span></label>
                                <input type="file" id="up-file" name="file" required
                                       class="mt-1.5 w-full text-sm text-gray-900 file:mr-3 file:rounded-md file:border-0 file:bg-blue-50 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-blue-700 hover:file:bg-blue-100"/>
                                <p class="mt-1 text-xs text-gray-400">PDF, DOCX, XLSX, JPG — máx. 20 MB</p>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="up-issued" class="block text-sm font-medium text-gray-900">Fecha de emisión</label>
                                    <input type="date" id="up-issued" name="issued_at"
                                           class="mt-1.5 w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600"/>
                                </div>
                                <div>
                                    <label for="up-expires" class="block text-sm font-medium text-gray-900">Vencimiento</label>
                                    <input type="date" id="up-expires" name="expires_at"
                                           class="mt-1.5 w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600"/>
                                </div>
                            </div>

                            <div>
                                <label for="up-issuer" class="block text-sm font-medium text-gray-900">Emisor</label>
                                <input type="text" id="up-issuer" name="issuer"
                                       placeholder="DGII, TSS, Registro Mercantil…"
                                       class="mt-1.5 w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600"/>
                            </div>

                            <div>
                                <label for="up-docnum" class="block text-sm font-medium text-gray-900">Número de documento</label>
                                <input type="text" id="up-docnum" name="document_number"
                                       class="mt-1.5 w-full rounded-md bg-white px-3 py-2 text-sm font-mono text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600"/>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="up-copytype" class="block text-sm font-medium text-gray-900">Tipo de copia</label>
                                    <select id="up-copytype" name="copy_type"
                                            class="mt-1.5 w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600">
                                        @foreach(\App\Models\VaultDocument::$copyTypes as $val => $lbl)
                                            <option value="{{ $val }}">{{ $lbl }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label for="up-lang" class="block text-sm font-medium text-gray-900">Idioma</label>
                                    <select id="up-lang" name="language"
                                            class="mt-1.5 w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600">
                                        <option value="es">Español</option>
                                        <option value="en">Inglés</option>
                                        <option value="other">Otro</option>
                                    </select>
                                </div>
                            </div>

                            <div class="flex items-center gap-x-3">
                                <input type="checkbox" id="up-notarized" name="notarized" value="1"
                                       class="size-4 rounded border-gray-300 text-blue-600 focus:ring-blue-600"/>
                                <label for="up-notarized" class="text-sm text-gray-700">Notariado / legalizado</label>
                            </div>

                            <div class="flex items-center gap-x-3">
                                <input type="checkbox" id="up-internal" name="internal_only" value="1"
                                       class="size-4 rounded border-gray-300 text-blue-600 focus:ring-blue-600"/>
                                <label for="up-internal" class="text-sm text-gray-700">Solo interno (excluir de paquetes de oferta)</label>
                            </div>

                            <div>
                                <label for="up-notes" class="block text-sm font-medium text-gray-900">Notas</label>
                                <textarea id="up-notes" name="notes" rows="2"
                                          class="mt-1.5 w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600"></textarea>
                            </div>

                        </div>

                        <div class="flex shrink-0 justify-end gap-x-3 px-4 py-4">
                            <button type="button" command="close" commandfor="upload-drawer"
                                    class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-xs inset-ring inset-ring-gray-300 hover:inset-ring-gray-400">
                                Cancelar
                            </button>
                            <button type="submit"
                                    class="inline-flex justify-center rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-xs hover:bg-blue-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                                Subir documento
                            </button>
                        </div>
                    </form>

                </div>
            </el-dialog-panel>
        </div>
    </dialog>
</el-dialog>

{{-- ── Replace drawer ─────────────────────────────────────────── --}}
<el-dialog>
    <dialog id="replace-drawer" aria-labelledby="replace-drawer-title"
            class="fixed inset-0 size-auto max-h-none max-w-none overflow-hidden bg-transparent backdrop:bg-transparent">
        <div tabindex="0" class="absolute inset-0 pl-10 focus:outline-none sm:pl-16">
            <el-dialog-panel class="ml-auto block size-full max-w-md transform transition duration-500 ease-in-out data-closed:translate-x-full sm:duration-700">
                <div class="relative flex h-full flex-col divide-y divide-gray-200 bg-white shadow-xl">

                    <div class="flex items-start justify-between px-4 py-6 sm:px-6">
                        <div>
                            <h2 id="replace-drawer-title" class="text-base font-semibold text-gray-900">Reemplazar documento</h2>
                            <p id="replace-drawer-subtitle" class="mt-1 text-sm text-gray-500"></p>
                        </div>
                        <button type="button" command="close" commandfor="replace-drawer"
                                class="relative ml-3 rounded-md text-gray-400 hover:text-gray-500 focus-visible:outline-2 focus-visible:outline-blue-600">
                            <span class="sr-only">Cerrar</span>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="size-6">
                                <path d="M6 18 18 6M6 6l12 12" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                    </div>

                    <div class="rounded-none bg-yellow-50 px-4 py-3 text-sm text-yellow-800">
                        La versión anterior se conservará como histórico. Las ofertas existentes no se modifican.
                    </div>

                    <form id="replace-form" method="POST" action="" enctype="multipart/form-data"
                          class="flex min-h-0 flex-1 flex-col">
                        @csrf
                        <div class="flex-1 space-y-5 overflow-y-auto px-4 py-6 sm:px-6">

                            <div>
                                <label for="rp-name" class="block text-sm font-medium text-gray-900">Nombre (opcional — mantiene el actual si se deja vacío)</label>
                                <input type="text" id="rp-name" name="name"
                                       class="mt-1.5 w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600"/>
                            </div>

                            <div>
                                <label for="rp-file" class="block text-sm font-medium text-gray-900">Nuevo archivo <span class="text-red-500">*</span></label>
                                <input type="file" id="rp-file" name="file" required
                                       class="mt-1.5 w-full text-sm text-gray-900 file:mr-3 file:rounded-md file:border-0 file:bg-blue-50 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-blue-700 hover:file:bg-blue-100"/>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="rp-issued" class="block text-sm font-medium text-gray-900">Fecha de emisión</label>
                                    <input type="date" id="rp-issued" name="issued_at"
                                           class="mt-1.5 w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600"/>
                                </div>
                                <div>
                                    <label for="rp-expires" class="block text-sm font-medium text-gray-900">Vencimiento</label>
                                    <input type="date" id="rp-expires" name="expires_at"
                                           class="mt-1.5 w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600"/>
                                </div>
                            </div>

                            <div>
                                <label for="rp-issuer" class="block text-sm font-medium text-gray-900">Emisor</label>
                                <input type="text" id="rp-issuer" name="issuer"
                                       class="mt-1.5 w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600"/>
                            </div>

                            <div>
                                <label for="rp-docnum" class="block text-sm font-medium text-gray-900">Número de documento</label>
                                <input type="text" id="rp-docnum" name="document_number"
                                       class="mt-1.5 w-full rounded-md bg-white px-3 py-2 text-sm font-mono text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600"/>
                            </div>

                            <div>
                                <label for="rp-copytype" class="block text-sm font-medium text-gray-900">Tipo de copia</label>
                                <select id="rp-copytype" name="copy_type"
                                        class="mt-1.5 w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600">
                                    @foreach(\App\Models\VaultDocument::$copyTypes as $val => $lbl)
                                        <option value="{{ $val }}">{{ $lbl }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="rp-notes" class="block text-sm font-medium text-gray-900">Notas</label>
                                <textarea id="rp-notes" name="notes" rows="2"
                                          class="mt-1.5 w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600"></textarea>
                            </div>

                        </div>

                        <div class="flex shrink-0 justify-end gap-x-3 px-4 py-4">
                            <button type="button" command="close" commandfor="replace-drawer"
                                    class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-xs inset-ring inset-ring-gray-300 hover:inset-ring-gray-400">
                                Cancelar
                            </button>
                            <button type="submit"
                                    class="inline-flex justify-center rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-xs hover:bg-blue-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                                Guardar nueva versión
                            </button>
                        </div>
                    </form>

                </div>
            </el-dialog-panel>
        </div>
    </dialog>
</el-dialog>

<script>
function openReplaceDrawer(docId, docName, category, copyType) {
    document.getElementById('replace-drawer-subtitle').textContent = docName;
    document.getElementById('replace-form').action = '/documentos/' + docId + '/replace';
    document.getElementById('rp-name').value = '';
    document.getElementById('rp-copytype').value = copyType;
}
</script>

@endsection
