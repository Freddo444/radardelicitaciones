@extends('layouts.app')
@section('title', 'Versiones — ' . $documento->name)

@section('content')
<div class="mx-auto max-w-4xl px-4 py-10 sm:px-6 lg:px-8">

    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <a href="{{ route('documentos.index') }}" class="text-sm text-blue-600 hover:underline">← Bóveda de documentos</a>
            <h1 class="mt-2 text-base font-semibold text-gray-900">{{ $documento->name }}</h1>
            <p class="mt-1 text-sm text-gray-500">Historial de versiones — {{ $chain->count() }} versión{{ $chain->count() !== 1 ? 'es' : '' }}</p>
        </div>
    </div>

    <div class="mt-8">
        <ul role="list" class="divide-y divide-gray-100 rounded-xl border border-gray-200 bg-white">
            @foreach($chain as $version)
            <li class="flex items-start justify-between gap-x-6 px-4 py-4">
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-x-2">
                        @if($version->is_current)
                            <span class="inline-flex items-center rounded-md bg-green-50 px-1.5 py-0.5 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">
                                Actual
                            </span>
                        @else
                            <span class="inline-flex items-center rounded-md bg-gray-100 px-1.5 py-0.5 text-xs font-medium text-gray-500 ring-1 ring-inset ring-gray-500/10">
                                Histórico
                            </span>
                        @endif
                        <p class="text-sm font-medium text-gray-900">{{ $version->filename }}</p>
                    </div>
                    <div class="mt-1 flex flex-wrap items-center gap-x-3 text-xs text-gray-500">
                        <span>Subido {{ $version->created_at->format('d/m/Y H:i') }}</span>
                        @if($version->issued_at)
                            <span>Emisión: {{ $version->issued_at->format('d/m/Y') }}</span>
                        @endif
                        @if($version->expires_at)
                            <span>Vence: {{ $version->expires_at->format('d/m/Y') }}</span>
                        @endif
                        @if($version->superseded_at)
                            <span class="text-gray-400">Reemplazado: {{ $version->superseded_at->format('d/m/Y H:i') }}</span>
                        @endif
                    </div>
                </div>
                <div>
                    <a href="{{ route('documentos.download', $version) }}"
                       class="rounded-md bg-white px-2.5 py-1.5 text-xs font-semibold text-gray-900 shadow-xs inset-ring inset-ring-gray-300 hover:bg-gray-50">
                        Descargar
                    </a>
                </div>
            </li>
            @endforeach
        </ul>
    </div>

</div>
@endsection
