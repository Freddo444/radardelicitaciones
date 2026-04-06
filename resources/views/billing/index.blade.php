@extends('layouts.app')
@section('title', 'Suscripción')

@section('content')
<div class="mx-auto max-w-4xl px-4 py-10 sm:px-6 lg:px-8">

    <h1 class="text-base/7 font-semibold text-gray-900">Suscripci&oacute;n</h1>
    <p class="mt-1 text-sm/6 text-gray-500">Gestiona tu plan, usuarios y empresas.</p>

    @if(session('success'))
    <div class="mt-4 rounded-md bg-green-50 p-3 text-sm text-green-700">{{ session('success') }}</div>
    @endif
    @if(session('warning'))
    <div class="mt-4 rounded-md bg-yellow-50 p-3 text-sm text-yellow-800">{{ session('warning') }}</div>
    @endif

    @if($subscription)
    {{-- Plan status card --}}
    <div class="mt-6 rounded-lg bg-white p-6 shadow ring-1 ring-gray-900/5">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Plan {{ ucfirst($subscription->plan) }}</h3>
                @if($subscription->status === 'trialing')
                <p class="text-sm text-gray-500">Prueba gratuita</p>
                @else
                <p class="text-sm text-gray-500">
                    US${{ number_format($subscription->monthly_amount, 2) }}/mes
                    @if($subscription->billing_cycle === 'annual')
                        <span class="text-xs text-green-600">(facturado anualmente con 20% dto.)</span>
                    @endif
                </p>
                @endif
            </div>
            <span class="inline-flex items-center rounded-full px-3 py-0.5 text-sm font-medium
                {{ $subscription->isActive() && $subscription->status !== 'trialing' ? 'bg-green-100 text-green-800' : '' }}
                {{ $subscription->status === 'trialing' ? 'bg-blue-100 text-blue-800' : '' }}
                {{ $subscription->isPending() ? 'bg-yellow-100 text-yellow-800' : '' }}
                {{ $subscription->isPastDue() ? 'bg-red-100 text-red-800' : '' }}
                {{ $subscription->status === 'cancelled' ? 'bg-gray-100 text-gray-800' : '' }}
                {{ $subscription->status === 'suspended' ? 'bg-red-100 text-red-800' : '' }}">
                {{ match($subscription->status) {
                    'active' => 'Activa',
                    'trialing' => 'Prueba gratuita',
                    'pending' => 'Pendiente de pago',
                    'past_due' => 'Pago vencido',
                    'cancelled' => 'Cancelada',
                    'suspended' => 'Suspendida',
                    default => $subscription->status,
                } }}
            </span>
        </div>

        @if($subscription->status === 'trialing')
        <div class="mt-4 rounded-md bg-blue-50 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-blue-900">D&iacute;as restantes: {{ $usage['trial_days_left'] ?? 0 }}</p>
                    <p class="mt-1 text-sm text-blue-700">An&aacute;lisis con IA: {{ $usage['trial_parses_used'] ?? 0 }} / {{ $usage['trial_parses_limit'] ?? 0 }}</p>
                </div>
                <a href="{{ route('register.show') }}"
                   class="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500">
                    Suscr&iacute;bete ahora
                </a>
            </div>
            <p class="mt-2 text-xs text-blue-600">Desde US$45/mes &mdash; acceso completo, an&aacute;lisis ilimitados.</p>
        </div>
        @endif

        @if($subscription->current_period_end && $subscription->status !== 'trialing')
        <p class="mt-3 text-sm text-gray-500">
            Periodo actual: {{ $subscription->current_period_start?->format('d/m/Y') }} &mdash; {{ $subscription->current_period_end->format('d/m/Y') }}
        </p>
        @endif
    </div>

    {{-- Usage + Upgrade cards --}}
    @if($usage)
    <div class="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-2">
        {{-- Companies usage --}}
        <div class="rounded-lg bg-white p-6 shadow ring-1 ring-gray-900/5" x-data="{ showConfirm: false, preview: null, loading: false }">
            <div class="flex items-center justify-between">
                <p class="text-sm font-medium text-gray-500">Empresas</p>
                <p class="text-2xl font-bold text-gray-900">{{ $usage['companies'] }} <span class="text-sm font-normal text-gray-400">/ {{ $usage['max_companies'] }}</span></p>
            </div>
            <div class="mt-3 h-2 w-full rounded-full bg-gray-100">
                <div class="h-2 rounded-full {{ $usage['companies'] >= $usage['max_companies'] ? 'bg-red-500' : 'bg-blue-500' }}"
                     style="width: {{ $usage['max_companies'] > 0 ? min(100, ($usage['companies'] / $usage['max_companies']) * 100) : 0 }}%"></div>
            </div>

            @if($isOwner && $subscription->isActive() && $subscription->status !== 'trialing')
            <div class="mt-4">
                <button type="button"
                        @click="if (!preview) { loading = true; fetch('{{ route('billing.preview-addon') }}?type=company').then(r => r.json()).then(d => { preview = d; loading = false; showConfirm = true; }).catch(() => loading = false); } else { showConfirm = true; }"
                        class="inline-flex items-center gap-1.5 rounded-md bg-white px-3 py-1.5 text-sm font-semibold text-gray-900 shadow-xs ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    <span x-text="loading ? 'Calculando...' : 'Agregar empresa'"></span>
                    <span class="text-xs text-gray-400">+US${{ number_format(\App\Services\SubscriptionService::EXTRA_COMPANY_PRICE, 0) }}/mes</span>
                </button>

                <div x-show="showConfirm" x-cloak class="mt-3 rounded-md border border-blue-200 bg-blue-50 p-4">
                    <p class="text-sm font-medium text-blue-900">Confirmar compra</p>
                    <dl class="mt-2 space-y-1 text-sm text-blue-800">
                        <div class="flex justify-between">
                            <dt>Cobro prorrateado (hoy)</dt>
                            <dd class="font-semibold" x-text="'US$' + (preview?.prorated_amount ?? 0).toFixed(2)"></dd>
                        </div>
                        <div class="flex justify-between">
                            <dt>Pr&oacute;ximo ciclo (<span x-text="preview?.billing_cycle === 'annual' ? 'anual' : 'mensual'"></span>)</dt>
                            <dd class="font-semibold" x-text="'US$' + (preview?.new_recurring ?? 0).toFixed(2)"></dd>
                        </div>
                    </dl>
                    <div class="mt-3 flex gap-2">
                        <form method="POST" action="{{ route('billing.purchase-addon') }}">
                            @csrf
                            <input type="hidden" name="type" value="company">
                            <button type="submit" class="rounded-md bg-blue-600 px-3 py-1.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-500">
                                Confirmar &mdash; cobrar <span x-text="'US$' + (preview?.prorated_amount ?? 0).toFixed(2)"></span>
                            </button>
                        </form>
                        <button type="button" @click="showConfirm = false" class="rounded-md px-3 py-1.5 text-sm font-semibold text-gray-700 hover:bg-gray-100">
                            Cancelar
                        </button>
                    </div>
                </div>
            </div>
            @endif
        </div>

        {{-- Users usage --}}
        <div class="rounded-lg bg-white p-6 shadow ring-1 ring-gray-900/5" x-data="{ showConfirm: false, preview: null, loading: false }">
            <div class="flex items-center justify-between">
                <p class="text-sm font-medium text-gray-500">Usuarios</p>
                <p class="text-2xl font-bold text-gray-900">{{ $usage['users'] }} <span class="text-sm font-normal text-gray-400">/ {{ $usage['max_users'] }}</span></p>
            </div>
            <div class="mt-3 h-2 w-full rounded-full bg-gray-100">
                <div class="h-2 rounded-full {{ $usage['users'] >= $usage['max_users'] ? 'bg-red-500' : 'bg-blue-500' }}"
                     style="width: {{ $usage['max_users'] > 0 ? min(100, ($usage['users'] / $usage['max_users']) * 100) : 0 }}%"></div>
            </div>

            @if($isOwner && $subscription->isActive() && $subscription->status !== 'trialing')
            <div class="mt-4">
                <button type="button"
                        @click="if (!preview) { loading = true; fetch('{{ route('billing.preview-addon') }}?type=user').then(r => r.json()).then(d => { preview = d; loading = false; showConfirm = true; }).catch(() => loading = false); } else { showConfirm = true; }"
                        class="inline-flex items-center gap-1.5 rounded-md bg-white px-3 py-1.5 text-sm font-semibold text-gray-900 shadow-xs ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                    <span x-text="loading ? 'Calculando...' : 'Agregar usuario'"></span>
                    <span class="text-xs text-gray-400">+US${{ number_format(\App\Services\SubscriptionService::EXTRA_USER_PRICE, 0) }}/mes</span>
                </button>

                <div x-show="showConfirm" x-cloak class="mt-3 rounded-md border border-blue-200 bg-blue-50 p-4">
                    <p class="text-sm font-medium text-blue-900">Confirmar compra</p>
                    <dl class="mt-2 space-y-1 text-sm text-blue-800">
                        <div class="flex justify-between">
                            <dt>Cobro prorrateado (hoy)</dt>
                            <dd class="font-semibold" x-text="'US$' + (preview?.prorated_amount ?? 0).toFixed(2)"></dd>
                        </div>
                        <div class="flex justify-between">
                            <dt>Pr&oacute;ximo ciclo (<span x-text="preview?.billing_cycle === 'annual' ? 'anual' : 'mensual'"></span>)</dt>
                            <dd class="font-semibold" x-text="'US$' + (preview?.new_recurring ?? 0).toFixed(2)"></dd>
                        </div>
                    </dl>
                    <div class="mt-3 flex gap-2">
                        <form method="POST" action="{{ route('billing.purchase-addon') }}">
                            @csrf
                            <input type="hidden" name="type" value="user">
                            <button type="submit" class="rounded-md bg-blue-600 px-3 py-1.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-500">
                                Confirmar &mdash; cobrar <span x-text="'US$' + (preview?.prorated_amount ?? 0).toFixed(2)"></span>
                            </button>
                        </form>
                        <button type="button" @click="showConfirm = false" class="rounded-md px-3 py-1.5 text-sm font-semibold text-gray-700 hover:bg-gray-100">
                            Cancelar
                        </button>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
    @endif

    {{-- Payment method --}}
    @if($subscription->gateway_subscription_id && $subscription->payment_gateway === 'paypal')
    <div class="mt-6 rounded-lg bg-white p-6 shadow ring-1 ring-gray-900/5">
        <h3 class="text-sm font-semibold text-gray-900">M&eacute;todo de pago</h3>
        <div class="mt-2 flex items-center gap-3">
            <span class="inline-flex items-center rounded-full bg-blue-100 px-3 py-1 text-sm font-medium text-blue-800">PayPal</span>
            <span class="text-sm text-gray-500">Cobro autom&aacute;tico {{ $subscription->billing_cycle === 'annual' ? 'anual' : 'mensual' }}</span>
        </div>
    </div>
    @endif

    {{-- Payment history --}}
    @if($payments->isNotEmpty())
    <div class="mt-6 rounded-lg bg-white p-6 shadow ring-1 ring-gray-900/5">
        <h3 class="text-sm font-semibold text-gray-900">Historial de pagos</h3>
        <table class="mt-4 w-full text-sm">
            <thead>
                <tr class="border-b text-left text-gray-500">
                    <th class="pb-2 font-medium">Fecha</th>
                    <th class="pb-2 font-medium">Monto</th>
                    <th class="pb-2 font-medium">M&eacute;todo</th>
                    <th class="pb-2 font-medium">Nota</th>
                    <th class="pb-2 font-medium">Estado</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @foreach($payments as $payment)
                <tr>
                    <td class="py-2">{{ $payment->paid_at?->format('d/m/Y') ?? '&mdash;' }}</td>
                    <td class="py-2">US${{ number_format($payment->amount, 2) }}</td>
                    <td class="py-2">{{ match($payment->gateway) {
                        'paypal' => 'PayPal',
                        'azul' => 'Azul',
                        'bank_transfer' => 'Transferencia',
                        default => $payment->gateway,
                    } }}</td>
                    <td class="py-2 text-xs text-gray-500">{{ $payment->notes ?? '&mdash;' }}</td>
                    <td class="py-2">
                        <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium
                            {{ $payment->status === 'completed' ? 'bg-green-100 text-green-800' : '' }}
                            {{ $payment->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                            {{ $payment->status === 'failed' ? 'bg-red-100 text-red-800' : '' }}">
                            {{ match($payment->status) {
                                'completed' => 'Completado',
                                'pending' => 'Pendiente',
                                'failed' => 'Fallido',
                                'refunded' => 'Reembolsado',
                                default => $payment->status,
                            } }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- Cancel subscription --}}
    @if($isOwner && $subscription->isActive() && $subscription->status !== 'trialing')
    <div class="mt-6 rounded-lg bg-white p-6 shadow ring-1 ring-gray-900/5" x-data="{ confirmCancel: false }">
        <h3 class="text-sm font-semibold text-gray-900">Cancelar suscripci&oacute;n</h3>
        <p class="mt-1 text-sm text-gray-500">Al cancelar, mantendr&aacute;s acceso hasta el {{ $subscription->current_period_end?->format('d/m/Y') }}.</p>
        <div class="mt-3">
            <button type="button" @click="confirmCancel = true" x-show="!confirmCancel"
                    class="text-sm font-medium text-red-600 hover:text-red-500">
                Cancelar suscripci&oacute;n
            </button>
            <div x-show="confirmCancel" x-cloak class="rounded-md border border-red-200 bg-red-50 p-4">
                <p class="text-sm text-red-800">&iquest;Est&aacute;s seguro? Se cancelar&aacute; el cobro autom&aacute;tico y perder&aacute;s acceso al finalizar tu periodo actual.</p>
                <div class="mt-3 flex gap-2">
                    <form method="POST" action="{{ route('billing.cancel') }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="rounded-md bg-red-600 px-3 py-1.5 text-sm font-semibold text-white shadow-sm hover:bg-red-500">
                            S&iacute;, cancelar suscripci&oacute;n
                        </button>
                    </form>
                    <button type="button" @click="confirmCancel = false" class="rounded-md px-3 py-1.5 text-sm font-semibold text-gray-700 hover:bg-gray-100">
                        No, mantener
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Trial expired / pending CTA --}}
    @if($subscription->trialExpired() || $subscription->isPending() || $subscription->isPastDue())
    <div class="mt-6 rounded-lg border-2 border-blue-200 bg-blue-50 p-6">
        <h3 class="text-lg font-semibold text-gray-900">
            {{ $subscription->trialExpired() ? 'Tu prueba gratuita ha expirado' : 'Activa tu suscripción' }}
        </h3>
        <p class="mt-1 text-sm text-gray-600">Suscr&iacute;bete para mantener acceso completo.</p>
        <a href="{{ route('register.show') }}"
           class="mt-3 inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500">
            Suscr&iacute;bete ahora &mdash; US$45/mes
        </a>
    </div>
    @endif

    @else
    <div class="mt-6 rounded-lg bg-white p-6 shadow ring-1 ring-gray-900/5">
        <p class="text-sm text-gray-500">No tienes una suscripci&oacute;n activa.</p>
        <a href="{{ route('register.show') }}"
           class="mt-3 inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500">
            Suscr&iacute;bete ahora
        </a>
    </div>
    @endif

</div>
@endsection
