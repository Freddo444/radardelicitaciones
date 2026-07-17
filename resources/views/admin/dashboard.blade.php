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
                                <th class="px-4 py-4 text-left text-xs font-semibold tracking-wide text-zinc-600 uppercase">Comprobante</th>
                                <th class="py-4 pr-5 pl-3 text-right"><span class="sr-only">Confirmar</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 bg-white">
                            @foreach($pendingTransfers as $p)
                            <tr class="hover:bg-zinc-50/50">
                                <td class="py-5 pr-3 pl-5 text-sm font-medium whitespace-nowrap text-zinc-900">{{ $p->id }}</td>
                                <td class="px-4 py-5 text-sm whitespace-nowrap text-zinc-600">
                                    <span class="block">{{ $p->subscription?->owner?->name ?? '—' }}</span>
                                    <span class="block text-xs text-zinc-400">{{ $p->subscription?->owner?->email }}</span>
                                </td>
                                <td class="px-4 py-5 text-sm font-semibold whitespace-nowrap text-zinc-900">${{ number_format($p->amount, 2) }}</td>
                                <td class="px-4 py-5 text-sm whitespace-nowrap">
                                    @if($p->receipt_path)
                                    <a href="{{ route('admin.payments.voucher', $p) }}" target="_blank" rel="noopener"
                                       class="inline-flex items-center gap-x-1 font-medium text-indigo-600 hover:text-indigo-800">
                                        <svg viewBox="0 0 20 20" fill="currentColor" class="size-4"><path fill-rule="evenodd" d="M4.5 2A1.5 1.5 0 0 0 3 3.5v13A1.5 1.5 0 0 0 4.5 18h11a1.5 1.5 0 0 0 1.5-1.5V7.621a1.5 1.5 0 0 0-.44-1.06l-4.12-4.122A1.5 1.5 0 0 0 10.88 2H4.5Zm5.75 3.75a.75.75 0 0 0-1.5 0v3.19l-1.72-1.72a.75.75 0 0 0-1.06 1.06l3 3a.75.75 0 0 0 1.06 0l3-3a.75.75 0 1 0-1.06-1.06l-1.72 1.72V5.75Z" clip-rule="evenodd"/></svg>
                                        Ver
                                    </a>
                                    @else
                                    <span class="text-xs text-zinc-400">—</span>
                                    @endif
                                </td>
                                <td class="py-5 pr-5 pl-3 text-right text-sm font-medium whitespace-nowrap">
                                    <form method="POST" action="{{ route('admin.payments.confirm', $p) }}"
                                          onsubmit="return confirm('¿Confirmar el pago y activar la cuenta del cliente? Se le enviará un correo.');">
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

{{-- System & Queue Health --}}
@php
    $sh = $systemHealth;
    $statusConfig = match($sh['status']) {
        'critical' => ['bg' => 'bg-red-500',    'ring' => 'ring-red-200',    'dot' => 'bg-red-500',    'text' => 'text-red-700',    'badge' => 'bg-red-100 text-red-700',    'label' => 'Atención requerida'],
        'warning'  => ['bg' => 'bg-amber-400',  'ring' => 'ring-amber-200',  'dot' => 'bg-amber-400',  'text' => 'text-amber-700',  'badge' => 'bg-amber-100 text-amber-700','label' => 'Advertencia'],
        default    => ['bg' => 'bg-emerald-500', 'ring' => 'ring-emerald-200','dot' => 'bg-emerald-500','text' => 'text-emerald-700', 'badge' => 'bg-emerald-100 text-emerald-700','label' => 'Operativo'],
    };
    $fmtAge = function(?int $min): string {
        if ($min === null) return 'Nunca';
        if ($min < 1)   return 'Ahora mismo';
        if ($min < 60)  return "hace {$min}m";
        $h = intdiv($min, 60); $m = $min % 60;
        return $m > 0 ? "hace {$h}h {$m}m" : "hace {$h}h";
    };
    $pollAgeClass = match(true) {
        $sh['pollAgeMin'] === null                   => 'text-zinc-400',
        $sh['pollAgeMin'] > 360                      => 'text-red-600 font-semibold',
        $sh['pollAgeMin'] > 120                      => 'text-amber-600 font-semibold',
        default                                      => 'text-emerald-600 font-semibold',
    };
    $scrapeAgeClass = match(true) {
        $sh['scrapeAgeMin'] === null                 => 'text-zinc-400',
        $sh['scrapeAgeMin'] > 720                    => 'text-red-600 font-semibold',
        $sh['scrapeAgeMin'] > 240                    => 'text-amber-600 font-semibold',
        default                                      => 'text-emerald-600 font-semibold',
    };
@endphp
<div class="mt-14 lg:mt-16">
    <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 {{ $statusConfig['ring'] }}">

        {{-- Header bar --}}
        <div class="flex items-center justify-between border-b border-zinc-100 px-6 py-4">
            <div class="flex items-center gap-3">
                <div class="flex size-9 shrink-0 items-center justify-center rounded-lg {{ $statusConfig['bg'] }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="size-5 text-white" aria-hidden="true">
                        <path d="M21 10.5h.375c.621 0 1.125.504 1.125 1.125v2.25c0 .621-.504 1.125-1.125 1.125H21M3.75 18h15A2.25 2.25 0 0 0 21 15.75v-6a2.25 2.25 0 0 0-2.25-2.25h-15A2.25 2.25 0 0 0 1.5 9.75v6A2.25 2.25 0 0 0 3.75 18Z" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-zinc-900">Sistema &amp; Cola</p>
                    <p class="text-xs text-zinc-500">Estado operativo en tiempo real</p>
                </div>
            </div>
            <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold {{ $statusConfig['badge'] }}">
                <span class="relative flex size-2">
                    @if($sh['status'] === 'ok')
                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full {{ $statusConfig['dot'] }} opacity-50"></span>
                    @endif
                    <span class="relative inline-flex size-2 rounded-full {{ $statusConfig['dot'] }}"></span>
                </span>
                {{ $statusConfig['label'] }}
            </span>
        </div>

        {{-- Metric grid --}}
        <div class="grid grid-cols-2 divide-x divide-y divide-zinc-100 sm:grid-cols-3 lg:grid-cols-5">

            {{-- Last poll --}}
            <div class="flex flex-col gap-1 px-5 py-5">
                <p class="text-xs font-medium tracking-wide text-zinc-400 uppercase">Último poll</p>
                <p class="mt-1 text-xl font-bold leading-tight {{ $pollAgeClass }}">
                    {{ $fmtAge($sh['pollAgeMin']) }}
                </p>
                @if($sh['lastPolledAt'])
                <p class="text-xs text-zinc-400">{{ \Carbon\Carbon::parse($sh['lastPolledAt'])->format('d/m H:i') }}</p>
                @endif
            </div>

            {{-- Poll status --}}
            <div class="flex flex-col gap-1 px-5 py-5">
                <p class="text-xs font-medium tracking-wide text-zinc-400 uppercase">Estado poll</p>
                <div class="mt-1">
                    @if($sh['pollStuck'])
                    <span class="inline-flex items-center gap-1.5 rounded-md bg-red-100 px-2.5 py-1 text-xs font-semibold text-red-700">
                        <span class="size-1.5 rounded-full bg-red-500"></span>Atascado
                    </span>
                    @elseif($sh['pollStatus'] === 'running')
                    <span class="inline-flex items-center gap-1.5 rounded-md bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-700">
                        <span class="relative flex size-1.5"><span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-blue-400 opacity-75"></span><span class="relative size-1.5 rounded-full bg-blue-500"></span></span>Ejecutando
                    </span>
                    @else
                    <span class="inline-flex items-center gap-1.5 rounded-md bg-zinc-100 px-2.5 py-1 text-xs font-semibold text-zinc-500">
                        <span class="size-1.5 rounded-full bg-zinc-400"></span>Inactivo
                    </span>
                    @endif
                </div>
            </div>

            {{-- Last scrape --}}
            <div class="flex flex-col gap-1 px-5 py-5">
                <p class="text-xs font-medium tracking-wide text-zinc-400 uppercase">Último scrape</p>
                <p class="mt-1 text-xl font-bold leading-tight {{ $scrapeAgeClass }}">
                    {{ $fmtAge($sh['scrapeAgeMin']) }}
                </p>
                @if($sh['lastScrapedAt'])
                <p class="text-xs text-zinc-400">{{ \Carbon\Carbon::parse($sh['lastScrapedAt'])->format('d/m H:i') }}</p>
                @endif
            </div>

            {{-- Pending jobs --}}
            <div class="flex flex-col gap-1 px-5 py-5">
                <p class="text-xs font-medium tracking-wide text-zinc-400 uppercase">Cola pendiente</p>
                <p class="mt-1 text-3xl font-bold leading-tight {{ $sh['pendingJobs'] > 0 ? 'text-amber-600' : 'text-zinc-900' }}">
                    {{ number_format($sh['pendingJobs']) }}
                </p>
                @if($sh['workerDown'])
                <p class="text-xs font-medium text-red-600">Worker posiblemente caído</p>
                @elseif($sh['pendingJobs'] > 0 && $sh['oldestJobTs'])
                @php $ageMin = (int) round((now()->timestamp - $sh['oldestJobTs']) / 60); @endphp
                <p class="text-xs text-zinc-400">más antiguo: {{ $ageMin }}m</p>
                @endif
            </div>

            {{-- Failed jobs --}}
            <div class="flex flex-col gap-1 px-5 py-5">
                <p class="text-xs font-medium tracking-wide text-zinc-400 uppercase">Jobs fallidos</p>
                <p class="mt-1 text-3xl font-bold leading-tight {{ $sh['failedJobs'] > 0 ? ($sh['failedJobs'] >= 10 ? 'text-red-600' : 'text-amber-600') : 'text-zinc-900' }}">
                    {{ number_format($sh['failedJobs']) }}
                </p>
                @if($sh['failedJobs'] > 0)
                <div class="mt-0.5 flex flex-wrap gap-1">
                    @foreach($sh['failedByQueue']->take(3) as $q)
                    <span class="rounded bg-zinc-100 px-1.5 py-0.5 text-xs text-zinc-500">{{ $q->queue }}: {{ $q->count }}</span>
                    @endforeach
                </div>
                @endif
            </div>

        </div>

        {{-- Footer hint when there are failures --}}
        @if($sh['failedJobs'] > 0 || $sh['workerDown'] || $sh['pollStuck'])
        <div class="border-t border-zinc-100 bg-zinc-50/70 px-6 py-3">
            <p class="text-xs text-zinc-500">
                @if($sh['workerDown'])
                    <span class="font-semibold text-red-600">Worker posiblemente caído</span> — verificar con <code class="rounded bg-zinc-200 px-1 py-0.5">php artisan queue:work</code> o el proceso supervisor.
                @elseif($sh['pollStuck'])
                    <span class="font-semibold text-red-600">Poll atascado</span> — ejecutar <code class="rounded bg-zinc-200 px-1 py-0.5">php artisan secp:poll --reset</code> para liberar.
                @elseif($sh['failedJobs'] > 0)
                    Jobs fallidos presentes — revisar con <code class="rounded bg-zinc-200 px-1 py-0.5">php artisan queue:failed</code> y reintentar o limpiar con <code class="rounded bg-zinc-200 px-1 py-0.5">queue:flush</code>.
                @endif
            </p>
        </div>
        @endif

    </div>
</div>
@endsection
