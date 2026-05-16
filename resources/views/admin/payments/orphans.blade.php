@extends('admin.layout')
@section('title', 'Pagos Azul Huérfanos')

@section('content')
<div class="mb-10 lg:mb-12">
    <h1 class="text-2xl font-semibold tracking-tight text-zinc-900 sm:text-3xl">Pagos Azul Huérfanos</h1>
    <p class="mt-3 max-w-2xl text-base leading-relaxed text-zinc-600">Tarjeta cobrada pero cuenta no creada. Revisar y reembolsar desde el panel de Azul si corresponde.</p>
</div>

@if(session('success'))
    <div class="mb-6 rounded-lg bg-green-50 px-4 py-3 text-sm text-green-800 ring-1 ring-green-200">{{ session('success') }}</div>
@endif

@if($orphans->isEmpty())
    <div class="rounded-xl bg-white px-6 py-12 text-center shadow-sm ring-1 ring-zinc-900/5">
        <p class="text-sm text-zinc-500">No hay pagos huérfanos pendientes.</p>
    </div>
@else
    <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-zinc-900/5">
        <table class="min-w-full divide-y divide-zinc-200 text-sm">
            <thead class="bg-zinc-50">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-zinc-600">Fecha</th>
                    <th class="px-4 py-3 text-left font-semibold text-zinc-600">Order Number</th>
                    <th class="px-4 py-3 text-left font-semibold text-zinc-600">RRN</th>
                    <th class="px-4 py-3 text-left font-semibold text-zinc-600">Auth</th>
                    <th class="px-4 py-3 text-left font-semibold text-zinc-600">Tarjeta</th>
                    <th class="px-4 py-3 text-left font-semibold text-zinc-600">Plan</th>
                    <th class="px-4 py-3 text-left font-semibold text-zinc-600">Monto</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100">
                @foreach($orphans as $orphan)
                    @php $plan = $orphan->plan ?? []; @endphp
                    <tr class="{{ $orphan->created_at->lt(now()->subHours(24)) ? 'bg-red-50' : '' }}">
                        <td class="px-4 py-3 text-zinc-700 whitespace-nowrap">{{ $orphan->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <span class="font-mono text-xs text-zinc-800">{{ $orphan->order_number }}</span>
                                <button onclick="navigator.clipboard.writeText('{{ $orphan->order_number }}')"
                                        title="Copiar"
                                        class="text-zinc-400 hover:text-zinc-700">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 0 1-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 0 1 1.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 0 0-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 0 1-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H9.75"/></svg>
                                </button>
                            </div>
                        </td>
                        <td class="px-4 py-3 font-mono text-xs text-zinc-600">{{ $orphan->rrn ?? '—' }}</td>
                        <td class="px-4 py-3 font-mono text-xs text-zinc-600">{{ $orphan->auth_code ?? '—' }}</td>
                        <td class="px-4 py-3 text-zinc-600">{{ $orphan->card_last_four ? '••••'.$orphan->card_last_four : '—' }}</td>
                        <td class="px-4 py-3 text-zinc-600 text-xs">
                            @if(!empty($plan))
                                {{ $plan['max_companies'] ?? '?' }} emp / {{ $plan['max_users'] ?? '?' }} usr
                                <span class="text-zinc-400">({{ $plan['billing_cycle'] ?? '?' }})</span>
                            @else
                                <span class="italic text-zinc-400">desconocido</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 font-semibold text-zinc-800">
                            {{ !empty($plan['charged_usd']) ? 'US$'.number_format((float)$plan['charged_usd'], 2) : '—' }}
                        </td>
                        <td class="px-4 py-3">
                            <form method="POST" action="{{ route('admin.payments.orphans.refunded', $orphan) }}"
                                  onsubmit="return confirm('¿Marcar como reembolsada la orden {{ $orphan->order_number }}?')">
                                @csrf @method('PATCH')
                                <button type="submit" class="rounded-md bg-zinc-100 px-3 py-1.5 text-xs font-medium text-zinc-700 hover:bg-zinc-200">
                                    Marcar reembolsada
                                </button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
@endsection
