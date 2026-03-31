<!DOCTYPE html>
<html lang="es" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }} — Facturacion</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full">

<div class="min-h-full">
    <div class="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:px-8">

        <h1 class="text-2xl font-bold text-gray-900">Facturacion</h1>
        <p class="mt-1 text-sm text-gray-500">Gestiona tu suscripcion y metodos de pago.</p>

        @if(session('success'))
        <div class="mt-4 rounded-md bg-green-50 p-3 text-sm text-green-700">{{ session('success') }}</div>
        @endif
        @if(session('warning'))
        <div class="mt-4 rounded-md bg-yellow-50 p-3 text-sm text-yellow-800">{{ session('warning') }}</div>
        @endif
        @if(session('info'))
        <div class="mt-4 rounded-md bg-blue-50 p-3 text-sm text-blue-700">{{ session('info') }}</div>
        @endif

        {{-- Subscription status --}}
        <div class="mt-8 rounded-lg bg-white p-6 shadow ring-1 ring-gray-900/5">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Plan {{ ucfirst($subscription->plan) }}</h2>
                    <p class="text-sm text-gray-500">US${{ number_format($subscription->monthly_amount, 2) }}/mes</p>
                </div>
                <span class="inline-flex items-center rounded-full px-3 py-0.5 text-sm font-medium
                    {{ $subscription->isActive() ? 'bg-green-100 text-green-800' : '' }}
                    {{ $subscription->isPending() ? 'bg-yellow-100 text-yellow-800' : '' }}
                    {{ $subscription->isPastDue() ? 'bg-red-100 text-red-800' : '' }}
                    {{ $subscription->status === 'cancelled' ? 'bg-gray-100 text-gray-800' : '' }}
                    {{ $subscription->status === 'suspended' ? 'bg-red-100 text-red-800' : '' }}">
                    {{ match($subscription->status) {
                        'active' => 'Activa',
                        'pending' => 'Pendiente de pago',
                        'past_due' => 'Pago vencido',
                        'cancelled' => 'Cancelada',
                        'suspended' => 'Suspendida',
                        default => $subscription->status,
                    } }}
                </span>
            </div>

            @if($subscription->current_period_end)
            <p class="mt-2 text-sm text-gray-500">
                Periodo actual: {{ $subscription->current_period_start?->format('d/m/Y') }} — {{ $subscription->current_period_end->format('d/m/Y') }}
            </p>
            @endif

            {{-- Usage --}}
            <div class="mt-4 grid grid-cols-2 gap-4">
                <div class="rounded-md bg-gray-50 p-3">
                    <p class="text-xs text-gray-500">Empresas</p>
                    <p class="text-lg font-semibold">{{ $usage['companies'] }} / {{ $usage['max_companies'] }}</p>
                </div>
                <div class="rounded-md bg-gray-50 p-3">
                    <p class="text-xs text-gray-500">Usuarios</p>
                    <p class="text-lg font-semibold">{{ $usage['users'] }} / {{ $usage['max_users'] }}</p>
                </div>
            </div>
        </div>

        {{-- Payment methods --}}
        @if($subscription->isPending() || $subscription->isPastDue())
        <div class="mt-8 rounded-lg bg-white p-6 shadow ring-1 ring-gray-900/5">
            <h2 class="text-lg font-semibold text-gray-900">Realizar pago</h2>
            <p class="mt-1 text-sm text-gray-500">Selecciona un metodo de pago para activar tu suscripcion.</p>

            <div class="mt-6 space-y-4">
                {{-- PayPal --}}
                <div x-data="paypalPayment()" class="rounded-md border border-gray-200 p-4">
                    <h3 class="font-medium text-gray-900">PayPal</h3>
                    <p class="mt-1 text-sm text-gray-500">Paga con tu cuenta PayPal o tarjeta de credito.</p>
                    <button @click="pay()" :disabled="loading"
                            class="mt-3 inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 disabled:opacity-50">
                        <span x-show="!loading">Pagar con PayPal</span>
                        <span x-show="loading">Procesando...</span>
                    </button>
                    <p x-show="error" class="mt-2 text-sm text-red-600" x-text="error"></p>
                </div>

                {{-- Azul --}}
                <div class="rounded-md border border-gray-200 p-4 opacity-60">
                    <h3 class="font-medium text-gray-900">Azul</h3>
                    <p class="mt-1 text-sm text-gray-500">Tarjeta de credito/debito local — proximamente.</p>
                    <button disabled class="mt-3 inline-flex items-center rounded-md bg-gray-300 px-4 py-2 text-sm font-semibold text-gray-500 cursor-not-allowed">
                        Proximamente
                    </button>
                </div>

                {{-- Bank transfer --}}
                <div class="rounded-md border border-gray-200 p-4">
                    <h3 class="font-medium text-gray-900">Transferencia bancaria</h3>
                    <p class="mt-1 text-sm text-gray-500">Realiza una transferencia y sube el comprobante.</p>
                    <a href="{{ route('billing.bank-transfer') }}"
                       class="mt-3 inline-flex items-center rounded-md bg-gray-800 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-gray-700">
                        Ver instrucciones
                    </a>
                </div>
            </div>
        </div>
        @endif

        {{-- Payment history --}}
        @if($payments->isNotEmpty())
        <div class="mt-8 rounded-lg bg-white p-6 shadow ring-1 ring-gray-900/5">
            <h2 class="text-lg font-semibold text-gray-900">Historial de pagos</h2>
            <table class="mt-4 w-full text-sm">
                <thead>
                    <tr class="border-b text-left text-gray-500">
                        <th class="pb-2 font-medium">Fecha</th>
                        <th class="pb-2 font-medium">Monto</th>
                        <th class="pb-2 font-medium">Metodo</th>
                        <th class="pb-2 font-medium">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach($payments as $payment)
                    <tr>
                        <td class="py-2">{{ $payment->paid_at?->format('d/m/Y') ?? '—' }}</td>
                        <td class="py-2">US${{ number_format($payment->amount, 2) }}</td>
                        <td class="py-2">{{ match($payment->gateway) {
                            'paypal' => 'PayPal',
                            'azul' => 'Azul',
                            'bank_transfer' => 'Transferencia',
                            default => $payment->gateway,
                        } }}</td>
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

        {{-- Actions --}}
        <div class="mt-8 flex items-center justify-between">
            @if($subscription->isActive() || $subscription->isPending())
            <a href="{{ $subscription->isActive() ? route('dashboard') : '#' }}"
               class="text-sm font-medium text-blue-600 hover:text-blue-500">
                {{ $subscription->isActive() ? 'Ir al dashboard' : '' }}
            </a>
            @endif

            @if($subscription->isActive())
            <form method="POST" action="{{ route('billing.cancel') }}" onsubmit="return confirm('¿Estas seguro de cancelar tu suscripcion?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="text-sm font-medium text-red-600 hover:text-red-500">
                    Cancelar suscripcion
                </button>
            </form>
            @endif
        </div>

    </div>
</div>

<script>
function paypalPayment() {
    return {
        loading: false,
        error: null,
        async pay() {
            this.loading = true;
            this.error = null;
            try {
                const res = await fetch('{{ route("paypal.create-order") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                });
                const data = await res.json();
                if (data.approve_url) {
                    window.location.href = data.approve_url;
                } else {
                    this.error = data.error || 'Error al crear la orden de PayPal.';
                }
            } catch (e) {
                this.error = 'Error de conexion.';
            } finally {
                this.loading = false;
            }
        }
    }
}
</script>

</body>
</html>
