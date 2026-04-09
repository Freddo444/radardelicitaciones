@extends('admin.layout')
@section('title', 'Dashboard')

@section('content')
<div class="mb-10 lg:mb-12">
    <h1 class="text-2xl font-semibold tracking-tight text-zinc-900 sm:text-3xl">Dashboard</h1>
    <p class="mt-3 max-w-2xl text-base leading-relaxed text-zinc-600">Vista general de la plataforma.</p>
</div>

{{-- Stat cards --}}
<dl class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 lg:gap-8">
    <div class="relative overflow-hidden rounded-xl bg-white px-6 pt-7 pb-14 shadow-sm ring-1 ring-zinc-900/5 sm:pt-8">
        <dt>
            <div class="absolute rounded-md bg-indigo-500 p-3">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" class="size-6 text-white">
                    <path d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </div>
            <p class="ml-16 truncate text-sm font-medium text-zinc-500">Empresas</p>
        </dt>
        <dd class="ml-16 flex items-baseline pb-6 sm:pb-7">
            <p class="text-3xl font-semibold tracking-tight text-zinc-900">{{ $stats['companies'] }}</p>
            <div class="absolute inset-x-0 bottom-0 border-t border-zinc-100 bg-zinc-50/80 px-6 py-4">
                <div class="text-sm"><a href="{{ route('admin.companies.index') }}" class="font-semibold text-indigo-600 hover:text-indigo-500">Ver todas</a></div>
            </div>
        </dd>
    </div>
    <div class="relative overflow-hidden rounded-xl bg-white px-6 pt-7 pb-14 shadow-sm ring-1 ring-zinc-900/5 sm:pt-8">
        <dt>
            <div class="absolute rounded-md bg-indigo-500 p-3">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" class="size-6 text-white">
                    <path d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </div>
            <p class="ml-16 truncate text-sm font-medium text-zinc-500">Usuarios</p>
        </dt>
        <dd class="ml-16 flex flex-wrap items-baseline gap-x-2 gap-y-1 pb-6 sm:pb-7">
            <p class="text-3xl font-semibold tracking-tight text-zinc-900">{{ $stats['users'] }}</p>
            <p class="text-sm text-zinc-500">+{{ $stats['signups_this_month'] }} este mes</p>
            <div class="absolute inset-x-0 bottom-0 border-t border-zinc-100 bg-zinc-50/80 px-6 py-4">
                <div class="text-sm text-zinc-600">Registros este mes</div>
            </div>
        </dd>
    </div>
    <div class="relative overflow-hidden rounded-xl bg-white px-6 pt-7 pb-14 shadow-sm ring-1 ring-zinc-900/5 sm:pt-8">
        <dt>
            <div class="absolute rounded-md bg-green-500 p-3">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" class="size-6 text-white">
                    <path d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </div>
            <p class="ml-16 truncate text-sm font-medium text-zinc-500">MRR</p>
        </dt>
        <dd class="ml-16 flex items-baseline pb-6 sm:pb-7">
            <p class="text-3xl font-semibold tracking-tight text-zinc-900">${{ number_format($stats['mrr'], 2) }}</p>
            <div class="absolute inset-x-0 bottom-0 border-t border-zinc-100 bg-zinc-50/80 px-6 py-4">
                <div class="text-sm">
                    <span class="font-medium text-green-600">{{ $stats['subscriptions_active'] }} activas</span>
                    <span class="text-gray-400 mx-1">&middot;</span>
                    <span class="text-yellow-600">{{ $stats['subscriptions_pending'] }} pendientes</span>
                </div>
            </div>
        </dd>
    </div>
</dl>

<div class="mt-14 grid grid-cols-1 gap-10 lg:mt-16 lg:grid-cols-2 lg:gap-12">
    {{-- Pending bank transfers --}}
    <div>
        <div class="sm:flex sm:items-center">
            <div class="sm:flex-auto">
                <h2 class="text-lg font-semibold tracking-tight text-zinc-900">Transferencias pendientes</h2>
                <p class="mt-2 text-sm leading-relaxed text-zinc-600">Pagos bancarios esperando confirmacion.</p>
            </div>
        </div>
        @if($pendingTransfers->isEmpty())
        <div class="mt-8 rounded-xl border border-dashed border-zinc-200 bg-white/60 py-16 text-center">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true" class="mx-auto size-12 text-zinc-300">
                <path d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
            <p class="mt-3 text-sm text-zinc-500">No hay transferencias pendientes.</p>
        </div>
        @else
        <div class="mt-8 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-zinc-900/5">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200">
                        <thead class="bg-zinc-50/80">
                            <tr>
                                <th class="py-4 pr-3 pl-5 text-left text-xs font-semibold tracking-wide text-zinc-600 uppercase">#</th>
                                <th class="px-4 py-4 text-left text-xs font-semibold tracking-wide text-zinc-600 uppercase">Cliente</th>
                                <th class="px-4 py-4 text-left text-xs font-semibold tracking-wide text-zinc-600 uppercase">Monto</th>
                                <th class="py-4 pr-5 pl-3 text-right"><span class="sr-only">Confirmar</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 bg-white">
                            @foreach($pendingTransfers as $p)
                            <tr class="hover:bg-zinc-50/50">
                                <td class="py-5 pr-3 pl-5 text-sm font-medium whitespace-nowrap text-zinc-900">{{ $p->id }}</td>
                                <td class="px-4 py-5 text-sm whitespace-nowrap text-zinc-600">{{ $p->subscription?->owner?->name ?? '—' }}</td>
                                <td class="px-4 py-5 text-sm font-semibold whitespace-nowrap text-zinc-900">${{ number_format($p->amount, 2) }}</td>
                                <td class="py-5 pr-5 pl-3 text-right text-sm font-medium whitespace-nowrap">
                                    <form method="POST" action="{{ route('admin.payments.confirm', $p) }}">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="font-semibold text-indigo-600 hover:text-indigo-800">Confirmar</button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
            </div>
        </div>
        @endif
    </div>

    {{-- Recent payments --}}
    <div>
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="text-lg font-semibold tracking-tight text-zinc-900">Pagos recientes</h2>
                <p class="mt-2 text-sm leading-relaxed text-zinc-600">Ultimos 10 pagos registrados.</p>
            </div>
            <div>
                <a href="{{ route('admin.payments.index') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-500">Ver todos</a>
            </div>
        </div>
        @if($recentPayments->isEmpty())
        <div class="mt-8 rounded-xl border border-dashed border-zinc-200 bg-white/60 py-16 text-center">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true" class="mx-auto size-12 text-zinc-300">
                <path d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
            <p class="mt-3 text-sm text-zinc-500">No hay pagos registrados.</p>
        </div>
        @else
        <div class="mt-8 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-zinc-900/5">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200">
                        <thead class="bg-zinc-50/80">
                            <tr>
                                <th class="py-4 pr-3 pl-5 text-left text-xs font-semibold tracking-wide text-zinc-600 uppercase">Cliente</th>
                                <th class="px-4 py-4 text-left text-xs font-semibold tracking-wide text-zinc-600 uppercase">Monto</th>
                                <th class="px-4 py-4 text-left text-xs font-semibold tracking-wide text-zinc-600 uppercase">Estado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 bg-white">
                            @foreach($recentPayments as $p)
                            <tr class="hover:bg-zinc-50/50">
                                <td class="py-5 pr-3 pl-5 text-sm whitespace-nowrap">
                                    <span class="font-medium text-zinc-900">{{ $p->subscription?->owner?->name ?? '—' }}</span>
                                    <span class="mt-0.5 block text-xs text-zinc-400">{{ $p->gateway }}</span>
                                </td>
                                <td class="px-4 py-5 text-sm font-medium whitespace-nowrap text-zinc-900">${{ number_format($p->amount, 2) }}</td>
                                <td class="px-4 py-5 text-sm whitespace-nowrap">
                                    @if($p->status === 'completed')
                                    <span class="inline-flex items-center gap-x-1.5 rounded-md bg-green-100 px-2 py-1 text-xs font-medium text-green-700">
                                        <svg viewBox="0 0 6 6" aria-hidden="true" class="size-1.5 fill-green-500"><circle r="3" cx="3" cy="3" /></svg>Completado
                                    </span>
                                    @elseif($p->status === 'pending')
                                    <span class="inline-flex items-center gap-x-1.5 rounded-md bg-yellow-100 px-2 py-1 text-xs font-medium text-yellow-800">
                                        <svg viewBox="0 0 6 6" aria-hidden="true" class="size-1.5 fill-yellow-500"><circle r="3" cx="3" cy="3" /></svg>Pendiente
                                    </span>
                                    @else
                                    <span class="inline-flex items-center gap-x-1.5 rounded-md bg-red-100 px-2 py-1 text-xs font-medium text-red-700">
                                        <svg viewBox="0 0 6 6" aria-hidden="true" class="size-1.5 fill-red-500"><circle r="3" cx="3" cy="3" /></svg>{{ ucfirst($p->status) }}
                                    </span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
            </div>
        </div>
        @endif
    </div>
</div>

{{-- Poll health --}}
<div class="mt-14 lg:mt-16">
    <h2 class="text-lg font-semibold tracking-tight text-zinc-900">Estado del polling</h2>
    <div class="mt-6 rounded-xl bg-white p-8 shadow-sm ring-1 ring-zinc-900/5">
        <dl class="grid grid-cols-1 gap-8 sm:grid-cols-2 sm:gap-10">
            <div>
                <dt class="text-xs font-semibold tracking-wide text-zinc-500 uppercase">Ultimo sondeo</dt>
                <dd class="mt-2 text-base font-semibold text-zinc-900">{{ $pollHealth['last_polled_at'] ?? 'Nunca' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold tracking-wide text-zinc-500 uppercase">Estado</dt>
                <dd class="mt-2">
                    @if($pollHealth['poll_status'] === 'running')
                    <span class="inline-flex items-center gap-x-1.5 rounded-md bg-blue-100 px-2 py-1 text-xs font-medium text-blue-700">
                        <svg viewBox="0 0 6 6" aria-hidden="true" class="size-1.5 fill-blue-500"><circle r="3" cx="3" cy="3" /></svg>Ejecutando
                    </span>
                    @elseif($pollHealth['poll_status'] === 'idle')
                    <span class="inline-flex items-center gap-x-1.5 rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-600">
                        <svg viewBox="0 0 6 6" aria-hidden="true" class="size-1.5 fill-gray-400"><circle r="3" cx="3" cy="3" /></svg>Inactivo
                    </span>
                    @else
                    <span class="inline-flex items-center gap-x-1.5 rounded-md bg-red-100 px-2 py-1 text-xs font-medium text-red-700">
                        <svg viewBox="0 0 6 6" aria-hidden="true" class="size-1.5 fill-red-500"><circle r="3" cx="3" cy="3" /></svg>{{ $pollHealth['poll_status'] }}
                    </span>
                    @endif
                </dd>
            </div>
        </dl>
    </div>
</div>
@endsection
