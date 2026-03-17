@extends('layouts.app')
@section('title', 'Preparación de Ofertas')

@section('content')
<div class="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8">

    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-base font-semibold text-gray-900">Preparación de ofertas</h1>
            <p class="mt-1 text-sm text-gray-500">Gestiona tus propuestas de licitación desde el análisis del pliego hasta el envío.</p>
        </div>
        <div class="mt-4 sm:mt-0">
            <a href="{{ route('ofertas.create') }}"
               class="inline-flex items-center gap-x-2 rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-xs hover:bg-blue-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="-ml-0.5 size-5">
                    <path d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z"/>
                </svg>
                Nueva oferta
            </a>
        </div>
    </div>

    <div class="mt-8">
        @if($offers->isEmpty())
            <div class="text-center py-16">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" class="mx-auto size-12 text-gray-400">
                    <path d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <h3 class="mt-2 text-sm font-semibold text-gray-900">Sin ofertas</h3>
                <p class="mt-1 text-sm text-gray-500">Comienza preparando tu primera propuesta de licitación.</p>
                <div class="mt-6">
                    <a href="{{ route('ofertas.create') }}"
                       class="inline-flex items-center gap-x-1.5 rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-xs hover:bg-blue-500">
                        Nueva oferta
                    </a>
                </div>
            </div>
        @else
            <div class="overflow-hidden shadow-sm ring-1 ring-gray-900/5 rounded-xl">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">Proceso</th>
                            <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Entidad</th>
                            <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Estado</th>
                            <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Fecha límite</th>
                            <th class="relative py-3.5 pl-3 pr-4 sm:pr-6"><span class="sr-only">Abrir</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @foreach($offers as $oferta)
                        <tr class="hover:bg-gray-50">
                            <td class="py-4 pl-4 pr-3 sm:pl-6">
                                <div class="font-medium text-sm text-gray-900">{{ $oferta->proceso_nombre }}</div>
                                @if($oferta->proceso_codigo)
                                <div class="mt-0.5 font-mono text-xs text-gray-500">{{ $oferta->proceso_codigo }}</div>
                                @endif
                            </td>
                            <td class="px-3 py-4 text-sm text-gray-600">
                                {{ $oferta->entidad_nombre ?? '—' }}
                            </td>
                            <td class="px-3 py-4">
                                <span class="rounded-md px-2 py-0.5 text-xs font-medium {{ \App\Models\Offer::$estadoColors[$oferta->estado] ?? 'bg-gray-100 text-gray-700' }}">
                                    {{ \App\Models\Offer::$estados[$oferta->estado] ?? $oferta->estado }}
                                </span>
                            </td>
                            <td class="px-3 py-4 text-sm">
                                @if($oferta->fecha_limite)
                                    @php $dias = $oferta->diasRestantes(); @endphp
                                    <div class="{{ $oferta->deadlineColor() }} font-medium">{{ $oferta->fecha_limite->format('d/m/Y') }}</div>
                                    @if($dias !== null)
                                    <div class="text-xs {{ $oferta->deadlineColor() }}">
                                        @if($dias < 0)
                                            Vencida hace {{ abs($dias) }} días
                                        @elseif($dias === 0)
                                            Hoy
                                        @else
                                            {{ $dias }} días restantes
                                        @endif
                                    </div>
                                    @endif
                                @else
                                    <span class="text-gray-400">Sin fecha</span>
                                @endif
                            </td>
                            <td class="py-4 pl-3 pr-4 text-right sm:pr-6">
                                <a href="{{ route('ofertas.show', $oferta) }}"
                                   class="text-sm font-medium text-blue-600 hover:text-blue-500">
                                    Abrir →
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($offers->hasPages())
            <div class="mt-6">{{ $offers->links() }}</div>
            @endif
        @endif
    </div>

</div>
@endsection
