@extends('layouts.app')
@section('title', 'Paquete — ' . ($package->bid->process_code ?? 'Prellenado'))

@section('content')
<div class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">

    {{-- Breadcrumb --}}
    <nav class="flex mb-6" aria-label="Breadcrumb">
        <ol class="flex items-center space-x-2 text-sm text-gray-500">
            <li><a href="{{ route('documentos-generados.index') }}" class="hover:text-gray-700">Docs. Generados</a></li>
            <li><span class="mx-1">/</span></li>
            <li class="text-gray-900 font-medium">Paquete #{{ $package->id }}</li>
        </ol>
    </nav>

    {{-- Header --}}
    <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-900/5 p-6">
        <div class="sm:flex sm:items-start sm:justify-between">
            <div>
                <h1 class="text-base font-semibold text-gray-900">{{ $package->bid->title ?? 'Convocatoria eliminada' }}</h1>
                <p class="mt-1 text-sm text-gray-500">
                    {{ $package->bid->buyer_name ?? '' }}
                    @if($package->bid?->process_code)
                        <span class="font-mono ml-2">{{ $package->bid->process_code }}</span>
                    @endif
                </p>
                <p class="mt-2 text-xs text-gray-400">
                    Generado el {{ $package->created_at->format('d/m/Y') }} a las {{ $package->created_at->format('h:i a') }}
                    ({{ $package->created_at->diffForHumans() }})
                </p>
            </div>
            @if($package->zip_path)
            <a href="{{ route('documentos-generados.zip', $package) }}"
               class="mt-4 sm:mt-0 inline-flex items-center gap-1.5 rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-xs hover:bg-blue-500">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                </svg>
                Descargar todo (ZIP)
            </a>
            @endif
        </div>
    </div>

    {{-- Files list --}}
    <div class="mt-6">
        <h2 class="text-sm font-semibold text-gray-900 mb-3">Documentos en este paquete ({{ $package->files->count() }})</h2>

        @if($package->files->isEmpty())
            <p class="text-sm text-gray-500">No se generaron documentos en este paquete.</p>
        @else
            <ul class="divide-y divide-gray-200 rounded-xl bg-white shadow-sm ring-1 ring-gray-900/5">
                @foreach($package->files as $file)
                <li class="flex items-center justify-between px-5 py-3 gap-4">
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-gray-900 truncate">
                            {{ \App\Models\OfferGeneratedFile::$forms[$file->form_code] ?? $file->form_code }}
                        </p>
                        <p class="text-xs text-gray-500">
                            {{ $file->generated_at?->format('d/m/Y H:i') }}
                            @if($file->file_size) &middot; {{ $file->fileSizeFormatted() }}@endif
                        </p>
                    </div>
                    <a href="{{ route('documentos-generados.file', $file) }}"
                       class="shrink-0 rounded-md bg-gray-50 px-3 py-1.5 text-xs font-medium text-gray-700 ring-1 ring-inset ring-gray-300 hover:bg-gray-100">
                        Descargar
                    </a>
                </li>
                @endforeach
            </ul>
        @endif
    </div>

    {{-- Back --}}
    <div class="mt-6">
        <a href="{{ route('documentos-generados.index') }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; Volver a documentos generados</a>
    </div>

</div>
@endsection
