@extends('layouts.app')
@section('title', 'Suscribirse')

@section('content')
<div class="mx-auto max-w-md px-4 py-10 sm:px-6 lg:px-8"
     x-data="{
        companies: 1,
        users: 2,
        annual: false,
        loading: false,
        error: null,
        get monthlyPrice() {
            return 45 + Math.max(0, this.companies - 1) * 20 + Math.max(0, this.users - 2) * 10;
        },
        get annualPrice() {
            return Math.round(this.monthlyPrice * 12 * 0.8 * 100) / 100;
        },
        get annualSavings() {
            return Math.round(this.monthlyPrice * 12 * 0.2 * 100) / 100;
        },
        get displayPrice() {
            return this.annual ? this.annualPrice : this.monthlyPrice;
        },
        async pay() {
            this.loading = true;
            this.error = null;
            try {
                const res = await fetch('{{ route('billing.subscribe.create') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    },
                    body: JSON.stringify({
                        max_companies: this.companies,
                        max_users: this.users,
                        billing_cycle: this.annual ? 'annual' : 'monthly',
                    }),
                });
                const data = await res.json();
                if (data.approve_url) {
                    window.location.href = data.approve_url;
                } else {
                    this.error = data.error || 'Error al crear la orden.';
                }
            } catch (e) {
                this.error = 'Error de conexion.';
            } finally {
                this.loading = false;
            }
        }
     }">

    <div class="text-center">
        <h1 class="text-2xl font-bold text-gray-900">Activa tu suscripcion</h1>
        <p class="mt-2 text-sm text-gray-500">Elige tu plan para mantener acceso completo a Radar de Licitaciones.</p>
        <div class="mt-3 flex flex-wrap items-center justify-center gap-3">
            <img src="{{ asset('images/payments/visa.png') }}" alt="Visa" class="h-7 w-auto rounded border border-gray-200 bg-white p-1">
            <img src="{{ asset('images/payments/mastercard.png') }}" alt="Mastercard" class="h-8 w-auto rounded border border-gray-200 bg-white p-1">
            <img src="{{ asset('images/payments/verified-by-visa.png') }}" alt="Verified by Visa" class="h-8 w-auto rounded border border-gray-200 bg-white p-1">
            <img src="{{ asset('images/payments/mastercard-id-check.png') }}" alt="Mastercard ID Check" class="h-8 w-auto rounded border border-gray-200 bg-white p-1">
        </div>
    </div>

    @if(session('error'))
    <div class="mt-4 rounded-md bg-red-50 p-3 text-sm text-red-700">{{ session('error') }}</div>
    @endif

    <div class="mt-8 rounded-lg border border-gray-200 bg-gray-50 p-5 space-y-5">
        {{-- Billing cycle toggle --}}
        <div class="flex items-center justify-center gap-3">
            <span class="text-sm font-medium" :class="annual ? 'text-gray-400' : 'text-gray-900'">Mensual</span>
            <button type="button" @click="annual = !annual"
                    class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out"
                    :class="annual ? 'bg-blue-600' : 'bg-gray-200'">
                <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                      :class="annual ? 'translate-x-5' : 'translate-x-0'"></span>
            </button>
            <span class="text-sm font-medium" :class="annual ? 'text-gray-900' : 'text-gray-400'">Anual</span>
            <span x-show="annual" x-cloak class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-semibold text-green-700">-20%</span>
        </div>

        <div>
            <div class="flex items-center justify-between text-sm">
                <label class="font-medium text-gray-700">Empresas</label>
                <span class="font-semibold text-gray-900" x-text="companies"></span>
            </div>
            <input type="range" min="1" max="10" x-model.number="companies"
                   class="mt-1 w-full accent-blue-600 cursor-pointer">
            <div class="flex justify-between text-xs text-gray-400"><span>1</span><span>10</span></div>
        </div>

        <div>
            <div class="flex items-center justify-between text-sm">
                <label class="font-medium text-gray-700">Usuarios</label>
                <span class="font-semibold text-gray-900" x-text="users"></span>
            </div>
            <input type="range" min="2" max="20" x-model.number="users"
                   class="mt-1 w-full accent-blue-600 cursor-pointer">
            <div class="flex justify-between text-xs text-gray-400"><span>2</span><span>20</span></div>
        </div>

        <div class="flex items-center justify-between rounded-md bg-blue-600 px-4 py-3 text-white">
            <span class="text-sm font-medium" x-text="annual ? 'Total anual' : 'Total mensual'"></span>
            <span class="text-lg font-bold">US$<span x-text="displayPrice"></span><span x-text="annual ? '/a&ntilde;o' : '/mes'"></span></span>
        </div>
        <p x-show="annual" x-cloak class="text-center text-xs font-medium text-green-600">
            Ahorras US$<span x-text="annualSavings"></span> al a&ntilde;o
        </p>

        <ul class="text-xs text-gray-500 space-y-1">
            <li>&bull; Base: US$45/mes (1 empresa, 2 usuarios)</li>
            <li x-show="companies > 1">&bull; +US$<span x-text="(companies - 1) * 20"></span>/mes por <span x-text="companies - 1"></span> empresa(s) adicional(es)</li>
            <li x-show="users > 2">&bull; +US$<span x-text="(users - 2) * 10"></span>/mes por <span x-text="users - 2"></span> usuario(s) adicional(es)</li>
            <li>&bull; Moneda de compra de referencia local: <strong>RD$ (DOP)</strong>.</li>
            <li>&bull; El proveedor de pago puede mostrar el equivalente en DOP (RD$) al momento del cobro.</li>
        </ul>
    </div>

    <button @click="pay()" :disabled="loading"
            class="mt-6 flex w-full justify-center rounded-md bg-blue-600 px-3 py-2.5 text-sm/6 font-semibold text-white shadow-xs hover:bg-blue-500 disabled:opacity-50">
        <span x-show="!loading">Pagar con PayPal</span>
        <span x-show="loading">Redirigiendo a PayPal...</span>
    </button>

    <p x-show="error" class="mt-3 text-center text-sm text-red-600" x-text="error"></p>

    <div class="mt-4 text-center">
        <a href="{{ route('billing.bank-transfer') }}" class="text-sm font-medium text-blue-600 hover:text-blue-500">Pagar por transferencia bancaria</a>
    </div>

    <div class="mt-2 text-center">
        <a href="{{ route('billing.index') }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; Volver a facturacion</a>
    </div>

    <p class="mt-4 text-center text-xs text-gray-500">
        Al continuar, aceptas nuestras <a href="{{ route('terms') }}" class="text-blue-600 hover:text-blue-500">condiciones de servicio</a> y
        <a href="{{ route('payment-policies') }}" class="text-blue-600 hover:text-blue-500">políticas de pago y seguridad</a>.
        También puedes consultar nuestra <a href="{{ route('privacy') }}" class="text-blue-600 hover:text-blue-500">política de privacidad</a>.
    </p>

</div>
@endsection
