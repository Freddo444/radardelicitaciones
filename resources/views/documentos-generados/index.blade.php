@extends('layouts.app')
@section('title', 'Documentos Generados')

@section('content')
<div class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">

    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-base font-semibold text-gray-900">Documentos Generados</h1>
            <p class="mt-1 text-sm text-gray-500">Paquetes de prellenado generados para cada convocatoria.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="mt-4 rounded-md bg-green-50 p-4 text-sm text-green-800 ring-1 ring-inset ring-green-200">
            {{ session('success') }}
        </div>
    @endif

    <div class="mt-8">
        @if($packages->isEmpty())
            <div class="text-center py-16">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" class="mx-auto size-12 text-gray-400">
                    <path d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m6.75 12H9m1.5-12H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <h3 class="mt-2 text-sm font-semibold text-gray-900">Sin prellenados</h3>
                <p class="mt-1 text-sm text-gray-500">Genera tu primer paquete desde una convocatoria.</p>
                <div class="mt-4">
                    <a href="{{ route('convocatorias.index') }}" class="text-sm font-medium text-blue-600 hover:text-blue-500">Ver convocatorias &rarr;</a>
                </div>
            </div>
        @else
            <ul class="space-y-4">
                @foreach($packages as $pkg)
                <li class="rounded-xl bg-white shadow-sm ring-1 ring-gray-900/5 p-5">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0 flex-1">
                            <h3 class="text-sm font-semibold text-gray-900 truncate">
                                {{ $pkg->bid->title ?? 'Convocatoria eliminada' }}
                            </h3>
                            <p class="mt-0.5 text-xs text-gray-500">
                                {{ $pkg->bid->buyer_name ?? '' }}
                                @if($pkg->bid?->process_code)
                                    <span class="font-mono ml-2">{{ $pkg->bid->process_code }}</span>
                                @endif
                            </p>
                            <div class="mt-2 flex flex-wrap items-center gap-3 text-xs text-gray-500">
                                <span>
                                    {{ $pkg->created_at->format('d/m/Y') }} a las {{ $pkg->created_at->format('h:i a') }}
                                </span>
                                <span class="text-gray-300">&middot;</span>
                                <span>{{ $pkg->created_at->diffForHumans() }}</span>
                                <span class="text-gray-300">&middot;</span>
                                <span>{{ $pkg->files->count() }} documento{{ $pkg->files->count() !== 1 ? 's' : '' }}</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <a href="{{ route('documentos-generados.show', $pkg) }}"
                               class="rounded-md bg-gray-50 px-3 py-1.5 text-xs font-medium text-gray-700 ring-1 ring-inset ring-gray-300 hover:bg-gray-100">
                                Ver documentos
                            </a>
                            @if($pkg->zip_path)
                            <a href="{{ route('documentos-generados.zip', $pkg) }}"
                               class="rounded-md bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white shadow-xs hover:bg-blue-500">
                                Descargar todo
                            </a>
                            @endif
                        </div>
                    </div>
                </li>
                @endforeach
            </ul>

            @if($packages->hasPages())
                <div class="mt-6">{{ $packages->links('components.pagination') }}</div>
            @endif
        @endif
    </div>

</div>
@endsection
