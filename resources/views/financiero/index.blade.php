@extends('layouts.app')
@section('title', 'Bóveda Financiera')

@section('content')
<div class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">

    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-base font-semibold text-gray-900">Bóveda financiera</h1>
            <p class="mt-1 text-sm text-gray-500">Estados financieros por año fiscal e índices calculados.</p>
        </div>
        <div class="mt-4 sm:mt-0">
            <a href="{{ route('financiero.create') }}"
               class="inline-flex items-center gap-x-2 rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-xs hover:bg-blue-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="-ml-0.5 size-5">
                    <path d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z"/>
                </svg>
                Agregar año fiscal
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="mt-4 rounded-md bg-green-50 p-4 text-sm text-green-800 ring-1 ring-inset ring-green-200">
            {{ session('success') }}
        </div>
    @endif

    <div class="mt-8">
        @if($records->isEmpty())
            <div class="text-center py-16">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" class="mx-auto size-12 text-gray-400">
                    <path d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <h3 class="mt-2 text-sm font-semibold text-gray-900">Sin registros financieros</h3>
                <p class="mt-1 text-sm text-gray-500">Agrega al menos tres años fiscales para cubrir los requisitos típicos de licitaciones.</p>
            </div>
        @else
            <div class="overflow-hidden shadow-sm ring-1 ring-gray-900/5 rounded-xl">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">Año fiscal</th>
                            <th class="px-3 py-3.5 text-right text-sm font-semibold text-gray-900">Solvencia</th>
                            <th class="px-3 py-3.5 text-right text-sm font-semibold text-gray-900">Liquidez</th>
                            <th class="px-3 py-3.5 text-right text-sm font-semibold text-gray-900">Endeudamiento</th>
                            <th class="px-3 py-3.5 text-right text-sm font-semibold text-gray-900">Capital de trabajo</th>
                            <th class="px-3 py-3.5 text-center text-sm font-semibold text-gray-900">Docs</th>
                            <th class="relative py-3.5 pl-3 pr-4 sm:pr-6"><span class="sr-only">Ver</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @foreach($records as $rec)
                        <tr>
                            <td class="py-4 pl-4 pr-3 sm:pl-6">
                                <div class="font-medium text-sm text-gray-900">{{ $rec->anio_fiscal }}</div>
                                <div class="text-xs text-gray-500">{{ $rec->currency }}</div>
                            </td>
                            <td class="px-3 py-4 text-sm text-right">
                                @php $v = $rec->solvencia(); @endphp
                                <span class="{{ $rec->solvencia_override ? 'text-amber-600 font-medium' : 'text-gray-700' }}">
                                    {{ $rec->formatIndice($v) }}
                                </span>
                                @if($rec->solvencia_override)
                                    <span class="ml-1 inline-flex items-center rounded bg-amber-100 px-1 text-xs text-amber-700">M</span>
                                @endif
                            </td>
                            <td class="px-3 py-4 text-sm text-right">
                                @php $v = $rec->liquidez(); @endphp
                                <span class="{{ $rec->liquidez_override ? 'text-amber-600 font-medium' : 'text-gray-700' }}">
                                    {{ $rec->formatIndice($v) }}
                                </span>
                                @if($rec->liquidez_override)
                                    <span class="ml-1 inline-flex items-center rounded bg-amber-100 px-1 text-xs text-amber-700">M</span>
                                @endif
                            </td>
                            <td class="px-3 py-4 text-sm text-right">
                                @php $v = $rec->endeudamiento(); @endphp
                                <span class="{{ $rec->endeudamiento_override ? 'text-amber-600 font-medium' : 'text-gray-700' }}">
                                    {{ $rec->formatIndice($v) }}
                                </span>
                                @if($rec->endeudamiento_override)
                                    <span class="ml-1 inline-flex items-center rounded bg-amber-100 px-1 text-xs text-amber-700">M</span>
                                @endif
                            </td>
                            <td class="px-3 py-4 text-sm text-right text-gray-700">
                                {{ $rec->formatMonto($rec->capitalTrabajo()) }}
                            </td>
                            <td class="px-3 py-4 text-center">
                                <div class="flex justify-center gap-x-1">
                                    <span title="IR-2" class="{{ $rec->path_ir2 ? 'text-green-600' : 'text-gray-300' }}">
                                        <svg viewBox="0 0 16 16" fill="currentColor" class="size-4">
                                            <path d="M4 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V4.414A2 2 0 0 0 13.414 3L11 .586A2 2 0 0 0 9.586 0H4Zm7 1.5v2A1.5 1.5 0 0 0 12.5 5H14v9a.5.5 0 0 1-.5.5h-11A.5.5 0 0 1 2 14V2a.5.5 0 0 1 .5-.5H11Z"/>
                                        </svg>
                                    </span>
                                    <span title="Estado financiero" class="{{ $rec->path_estado_financiero ? 'text-green-600' : 'text-gray-300' }}">
                                        <svg viewBox="0 0 16 16" fill="currentColor" class="size-4">
                                            <path d="M4 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V4.414A2 2 0 0 0 13.414 3L11 .586A2 2 0 0 0 9.586 0H4Zm7 1.5v2A1.5 1.5 0 0 0 12.5 5H14v9a.5.5 0 0 1-.5.5h-11A.5.5 0 0 1 2 14V2a.5.5 0 0 1 .5-.5H11Z"/>
                                        </svg>
                                    </span>
                                </div>
                            </td>
                            <td class="py-4 pl-3 pr-4 text-right text-sm sm:pr-6">
                                <a href="{{ route('financiero.show', $rec) }}"
                                   class="text-blue-600 hover:text-blue-500 font-medium">Ver</a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <p class="mt-3 text-xs text-gray-500">
                <span class="inline-flex items-center rounded bg-amber-100 px-1 text-xs text-amber-700 font-medium">M</span>
                = índice con valor manual (override)
            </p>
        @endif
    </div>

</div>
@endsection
