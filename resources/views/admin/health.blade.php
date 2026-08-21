@extends('admin.layout')
@section('title', 'Salud del sistema')

@section('content')
<div class="mb-10 lg:mb-12">
    <h1 class="text-2xl font-semibold tracking-tight text-zinc-900 sm:text-3xl">Salud del sistema</h1>
    <p class="mt-3 max-w-2xl text-base leading-relaxed text-zinc-600">Estado operativo de la plataforma de un vistazo.</p>
</div>

{{-- Colas --}}
<section class="mb-14 lg:mb-16">
    <h2 class="text-lg font-semibold tracking-tight text-zinc-900">Colas</h2>
    <p class="mt-2 text-sm leading-relaxed text-zinc-600">Trabajos en cola y fallidos.</p>

    <dl class="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:gap-8">
        <div class="rounded-xl bg-white px-6 py-6 shadow-sm ring-1 ring-zinc-900/5">
            <dt class="text-sm font-medium text-zinc-500">Trabajos pendientes</dt>
            <dd class="mt-2 text-3xl font-semibold tracking-tight {{ $queue['pending'] > 0 ? 'text-amber-600' : 'text-zinc-900' }}">
                @if($queue['hasJobs']){{ number_format($queue['pending']) }}@else<span class="text-base font-medium text-zinc-400">Tabla no disponible</span>@endif
            </dd>
        </div>
        <div class="rounded-xl bg-white px-6 py-6 shadow-sm ring-1 {{ ($queue['failed'] ?? 0) > 0 ? 'ring-red-200' : 'ring-zinc-900/5' }}">
            <dt class="text-sm font-medium text-zinc-500">Trabajos fallidos</dt>
            <dd class="mt-2 text-3xl font-semibold tracking-tight {{ ($queue['failed'] ?? 0) > 0 ? 'text-red-600' : 'text-zinc-900' }}">
                @if($queue['hasFailed']){{ number_format($queue['failed']) }}@else<span class="text-base font-medium text-zinc-400">Tabla no disponible</span>@endif
            </dd>
        </div>
    </dl>

    @if($queue['hasFailed'] && $queue['recentFailed']->isNotEmpty())
    <div class="mt-8 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-zinc-900/5">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200">
                <thead class="bg-zinc-50/80">
                    <tr>
                        <th class="py-4 pr-3 pl-5 text-left text-xs font-semibold tracking-wide text-zinc-600 uppercase">Cola</th>
                        <th class="px-4 py-4 text-left text-xs font-semibold tracking-wide text-zinc-600 uppercase">Falló</th>
                        <th class="px-4 py-4 text-left text-xs font-semibold tracking-wide text-zinc-600 uppercase">Excepción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 bg-white">
                    @foreach($queue['recentFailed'] as $f)
                    <tr class="hover:bg-zinc-50/50">
                        <td class="py-5 pr-3 pl-5 text-sm font-medium whitespace-nowrap text-zinc-900">{{ $f->queue }}</td>
                        <td class="px-4 py-5 text-sm whitespace-nowrap text-zinc-500">{{ \Carbon\Carbon::parse($f->failed_at)->format('d/m H:i') }}</td>
                        <td class="px-4 py-5 text-sm text-zinc-600"><code class="text-xs text-red-700">{{ $f->exception }}</code></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</section>

{{-- Frescura de datos --}}
@php
    $freshnessLabels = ['poll' => 'Último poll (API DGCP)', 'scrape' => 'Último scrape', 'catalog' => 'Catálogo UNSPSC importado'];
@endphp
<section class="mb-14 lg:mb-16">
    <h2 class="text-lg font-semibold tracking-tight text-zinc-900">Frescura de datos</h2>
    <p class="mt-2 text-sm leading-relaxed text-zinc-600">Última actualización de cada fuente de datos.</p>

    <dl class="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-3 lg:gap-8">
        @foreach($freshnessLabels as $key => $label)
        @php $item = $freshness[$key]; @endphp
        <div class="rounded-xl bg-white px-6 py-6 shadow-sm ring-1 {{ $item['stale'] ? 'ring-red-200' : 'ring-zinc-900/5' }}">
            <dt class="flex items-center gap-2 text-sm font-medium text-zinc-500">
                <span class="size-2.5 shrink-0 rounded-full {{ $item['stale'] ? 'bg-red-500' : 'bg-emerald-500' }}"></span>
                {{ $label }}
            </dt>
            <dd class="mt-3">
                @if($item['missing'])
                <p class="text-lg font-semibold text-red-600">Nunca</p>
                @else
                <p class="text-lg font-semibold {{ $item['stale'] ? 'text-red-600' : 'text-emerald-700' }}">{{ $item['ago'] }}</p>
                <p class="mt-1 text-xs text-zinc-400">{{ $item['raw']->format('d/m/Y H:i') }}</p>
                @endif
            </dd>
        </div>
        @endforeach
    </dl>
</section>

{{-- Notificaciones --}}
<section class="mb-14 lg:mb-16">
    <h2 class="text-lg font-semibold tracking-tight text-zinc-900">Notificaciones (últimas 24h)</h2>
    <p class="mt-2 text-sm leading-relaxed text-zinc-600">Envíos por canal en el último día.</p>

    @if(empty($notifications))
    <div class="mt-6 rounded-xl border border-dashed border-zinc-200 bg-white/60 py-12 text-center">
        <p class="text-sm text-zinc-500">No hay notificaciones en las últimas 24 horas.</p>
    </div>
    @else
    <dl class="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:gap-8">
        @foreach($notifications as $channel => $counts)
        <div class="rounded-xl bg-white px-6 py-6 shadow-sm ring-1 {{ $counts['failed'] > 0 ? 'ring-amber-200' : 'ring-zinc-900/5' }}">
            <dt class="text-sm font-semibold capitalize text-zinc-900">{{ $channel }}</dt>
            <dd class="mt-3 flex items-baseline gap-6">
                <div>
                    <p class="text-2xl font-semibold tracking-tight text-emerald-700">{{ number_format($counts['sent']) }}</p>
                    <p class="text-xs font-medium text-zinc-500">enviadas</p>
                </div>
                <div>
                    <p class="text-2xl font-semibold tracking-tight {{ $counts['failed'] > 0 ? 'text-amber-600' : 'text-zinc-900' }}">{{ number_format($counts['failed']) }}</p>
                    <p class="text-xs font-medium text-zinc-500">fallidas</p>
                </div>
                @if($counts['other'] > 0)
                <div>
                    <p class="text-2xl font-semibold tracking-tight text-zinc-500">{{ number_format($counts['other']) }}</p>
                    <p class="text-xs font-medium text-zinc-500">otras</p>
                </div>
                @endif
            </dd>
        </div>
        @endforeach
    </dl>
    @endif
</section>

{{-- Facturación --}}
@php
    $billingLabels = ['active' => 'Activas', 'trialing' => 'En prueba', 'past_due' => 'Vencidas', 'suspended' => 'Suspendidas', 'cancelled' => 'Canceladas', 'pending' => 'Pendientes'];
@endphp
<section class="mb-14 lg:mb-16">
    <h2 class="text-lg font-semibold tracking-tight text-zinc-900">Facturación</h2>
    <p class="mt-2 text-sm leading-relaxed text-zinc-600">Suscripciones por estado y pagos por confirmar.</p>

    <dl class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
        @foreach($billingLabels as $status => $label)
        @php $flag = in_array($status, ['past_due', 'suspended']) && $billing['byStatus'][$status] > 0; @endphp
        <div class="rounded-xl bg-white px-4 py-5 text-center shadow-sm ring-1 {{ $flag ? 'ring-amber-200' : 'ring-zinc-900/5' }}">
            <dt class="text-xs font-medium tracking-wide text-zinc-500 uppercase">{{ $label }}</dt>
            <dd class="mt-2 text-2xl font-semibold tracking-tight {{ $flag ? 'text-amber-600' : 'text-zinc-900' }}">{{ number_format($billing['byStatus'][$status]) }}</dd>
        </div>
        @endforeach
    </dl>

    @if($billing['pendingTransfers'] > 0)
    <a href="{{ route('admin.payments.index') }}" class="mt-6 flex items-center justify-between gap-4 rounded-xl bg-amber-50 px-6 py-5 shadow-sm ring-1 ring-amber-200 transition-colors hover:bg-amber-100/80">
        <div class="flex items-center gap-3">
            <svg viewBox="0 0 20 20" fill="currentColor" class="size-5 shrink-0 text-amber-600"><path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.345 0-2.189-1.458-1.515-2.625L8.485 2.495ZM10 5a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0v-3.5A.75.75 0 0 1 10 5Zm0 9a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" /></svg>
            <p class="text-sm font-medium text-amber-900">{{ $billing['pendingTransfers'] }} transferencia(s) bancaria(s) por confirmar</p>
        </div>
        <span class="text-sm font-semibold text-amber-700">Revisar &rarr;</span>
    </a>
    @endif
</section>

{{-- Integraciones --}}
<section class="mb-4">
    <h2 class="text-lg font-semibold tracking-tight text-zinc-900">Integraciones</h2>
    <p class="mt-2 text-sm leading-relaxed text-zinc-600">Configuración presente (no se realizan llamadas en vivo).</p>

    <div class="mt-6 flex flex-wrap gap-3">
        @foreach($integrations as $svc)
        <span class="inline-flex items-center gap-2 rounded-lg px-3.5 py-2 text-sm font-medium ring-1 {{ $svc['ok'] ? 'bg-emerald-50 text-emerald-800 ring-emerald-200' : 'bg-zinc-100 text-zinc-500 ring-zinc-200' }}">
            <span class="size-2 rounded-full {{ $svc['ok'] ? 'bg-emerald-500' : 'bg-zinc-400' }}"></span>
            {{ $svc['label'] }}
            @if(!empty($svc['note']))<span class="text-xs font-normal text-zinc-500">· {{ $svc['note'] }}</span>@endif
        </span>
        @endforeach
    </div>
</section>
@endsection
