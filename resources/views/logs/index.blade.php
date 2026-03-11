@extends('layouts.app')
@section('title', 'Registros')

@section('content')
<div class="px-4 py-10 sm:px-6 lg:px-8">

    {{-- Header --}}
    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-base font-semibold text-gray-900">Registros de notificaciones</h1>
            <p class="mt-2 text-sm text-gray-700">Historial de todos los intentos de envío por email y Telegram.</p>
        </div>
        @if($logs->total() > 0)
        <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none">
            <span class="text-sm text-gray-500">{{ number_format($logs->total()) }} registro{{ $logs->total() !== 1 ? 's' : '' }}</span>
        </div>
        @endif
    </div>

    @if($logs->isEmpty())
        {{-- Empty state --}}
        <div class="mt-16 text-center">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" class="mx-auto size-12 text-gray-400">
                <path d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <h3 class="mt-2 text-sm font-semibold text-gray-900">Sin registros aún</h3>
            <p class="mt-1 text-sm text-gray-500">Los registros aparecerán aquí después del primer sondeo con coincidencias.</p>
        </div>
    @else
        {{-- Table --}}
        <div class="mt-8 flow-root">
            <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                    <table class="min-w-full divide-y divide-gray-300">
                        <thead>
                            <tr>
                                <th scope="col" class="py-3.5 pr-3 pl-4 text-left text-sm font-semibold text-gray-900 sm:pl-3">Fecha</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Convocatoria</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Canal</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Estado</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Detalle</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white">
                            @foreach($logs as $log)
                            <tr class="even:bg-gray-50">
                                {{-- Fecha --}}
                                <td class="py-4 pr-3 pl-4 text-sm whitespace-nowrap text-gray-500 sm:pl-3">
                                    <time datetime="{{ $log->created_at->toIso8601String() }}"
                                          title="{{ $log->created_at->format('d/m/Y H:i:s') }}">
                                        {{ $log->created_at->format('d/m/Y H:i') }}
                                    </time>
                                </td>

                                {{-- Convocatoria --}}
                                <td class="px-3 py-4 text-sm text-gray-900">
                                    @if($log->bid)
                                        <a href="{{ $log->bid->secp_url }}" target="_blank" rel="noopener"
                                           class="max-w-xs truncate font-medium hover:text-indigo-600"
                                           title="{{ $log->bid->title }}">
                                            {{ Str::limit($log->bid->title, 60) }}
                                        </a>
                                        <p class="text-xs text-gray-400">{{ $log->bid->process_code }}</p>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>

                                {{-- Canal --}}
                                <td class="px-3 py-4 text-sm whitespace-nowrap">
                                    @if($log->channel === 'email')
                                        <span class="inline-flex items-center gap-x-1.5 rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" class="size-3">
                                                <path d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                            Email
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-x-1.5 rounded-md bg-indigo-50 px-2 py-1 text-xs font-medium text-indigo-700">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" class="size-3">
                                                <path d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                            Telegram
                                        </span>
                                    @endif
                                </td>

                                {{-- Estado --}}
                                <td class="px-3 py-4 text-sm whitespace-nowrap">
                                    @if($log->status === 'sent')
                                        <span class="inline-flex items-center gap-x-1.5 rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700">
                                            <svg viewBox="0 0 6 6" aria-hidden="true" class="size-1.5 fill-green-500"><circle r="3" cx="3" cy="3"/></svg>
                                            Enviado
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-x-1.5 rounded-md bg-red-50 px-2 py-1 text-xs font-medium text-red-700">
                                            <svg viewBox="0 0 6 6" aria-hidden="true" class="size-1.5 fill-red-500"><circle r="3" cx="3" cy="3"/></svg>
                                            Fallido
                                        </span>
                                    @endif
                                </td>

                                {{-- Detalle --}}
                                <td class="px-3 py-4 text-sm text-gray-500">
                                    @if($log->error_message)
                                        <span class="max-w-xs truncate block" title="{{ $log->error_message }}">
                                            {{ Str::limit($log->error_message, 80) }}
                                        </span>
                                    @else
                                        <span class="text-gray-300">—</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Pagination --}}
        @if($logs->hasPages())
        <div class="mt-6 border-t border-gray-100 pt-4">
            {{ $logs->links() }}
        </div>
        @endif
    @endif

</div>
@endsection
