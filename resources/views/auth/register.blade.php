<!DOCTYPE html>
<html lang="es" class="h-full bg-white">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }} — Registro</title>
    <link rel="apple-touch-icon" sizes="180x180" href="/images/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/images/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/images/favicon-16x16.png">
    <link rel="shortcut icon" href="/favicon.ico">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <x-umami-script />
</head>
<body class="h-full">

<div class="flex min-h-full items-center justify-center px-4 py-12 sm:px-6 lg:px-8"
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
                const res = await fetch('{{ route('register.create-order') }}', {
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
                this.error = 'Error de conexión.';
            } finally {
                this.loading = false;
            }
        }
     }">
    <div class="w-full max-w-md space-y-8">

        <div>
            <img src="/images/LOGO.png" alt="Radar de Licitaciones" class="mx-auto -mb-4 w-72 object-contain">
            <p class="text-center text-sm text-gray-500">Paso 1 de 2 — Elige tu plan y paga</p>
        </div>

        @if(session('error'))
        <div class="rounded-md bg-red-50 p-3 text-sm text-red-700">{{ session('error') }}</div>
        @endif

        {{-- Plan configurator --}}
        <div class="rounded-lg border border-gray-200 bg-gray-50 p-5 space-y-5">
            {{-- Billing cycle toggle --}}
            <div class="flex items-center justify-center gap-3">
                <span class="text-sm font-medium" :class="annual ? 'text-gray-400' : 'text-gray-900'">Mensual</span>
                <button type="button" @click="annual = !annual"
                        class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out"
                        :class="annual ? 'bg-blue-600' : 'bg-gray-200'">
                    <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                          :class="annual ? 'translate-x-5' : 'translate-x-0'"></span>
                </button>
                <span class="text-sm font-medium" :class="annual ? 'text-gray-900' : 'text-gray-400'">Anual</span>
                <span x-show="annual" x-cloak class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-semibold text-green-700">-20%</span>
            </div>

            <div>
                <div class="flex items-center justify-between text-sm">
                    <label for="max_companies" class="font-medium text-gray-700">Empresas</label>
                    <span class="font-semibold text-gray-900" x-text="companies"></span>
                </div>
                <input id="max_companies" type="range" min="1" max="10" x-model.number="companies"
                       class="mt-1 w-full accent-blue-600 cursor-pointer">
                <div class="flex justify-between text-xs text-gray-400">
                    <span>1</span>
                    <span>10</span>
                </div>
            </div>

            <div>
                <div class="flex items-center justify-between text-sm">
                    <label for="max_users" class="font-medium text-gray-700">Usuarios</label>
                    <span class="font-semibold text-gray-900" x-text="users"></span>
                </div>
                <input id="max_users" type="range" min="2" max="20" x-model.number="users"
                       class="mt-1 w-full accent-blue-600 cursor-pointer">
                <div class="flex justify-between text-xs text-gray-400">
                    <span>2</span>
                    <span>20</span>
                </div>
            </div>

            <div class="flex items-center justify-between rounded-md bg-blue-600 px-4 py-3 text-white">
                <span class="text-sm font-medium" x-text="annual ? 'Total anual' : 'Total mensual'"></span>
                <span class="text-lg font-bold">US$<span x-text="displayPrice"></span><span x-text="annual ? '/año' : '/mes'"></span></span>
            </div>
            <p x-show="annual" x-cloak class="text-center text-xs font-medium text-green-600">
                Ahorras US$<span x-text="annualSavings"></span> al año
            </p>

            <ul class="text-xs text-gray-500 space-y-1">
                <li>• Base: US$45/mes (1 empresa, 2 usuarios)</li>
                <li x-show="companies > 1">• +US$<span x-text="(companies - 1) * 20"></span>/mes por <span x-text="companies - 1"></span> empresa(s) adicional(es)</li>
                <li x-show="users > 2">• +US$<span x-text="(users - 2) * 10"></span>/mes por <span x-text="users - 2"></span> usuario(s) adicional(es)</li>
            </ul>
        </div>

        <button @click="pay()" :disabled="loading"
                class="flex w-full justify-center rounded-md bg-blue-600 px-3 py-2.5 text-sm/6 font-semibold text-white shadow-xs hover:bg-blue-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 disabled:opacity-50">
            <span x-show="!loading">Pagar con PayPal</span>
            <span x-show="loading">Redirigiendo a PayPal...</span>
        </button>

        <p x-show="error" class="text-center text-sm text-red-600" x-text="error"></p>

        <p class="text-center text-sm text-gray-500">
            ¿Quieres probar primero?
            <a href="{{ route('register.trial') }}" class="font-medium text-blue-600 hover:text-blue-500">Prueba gratis 7 días</a>
        </p>

        <p class="text-center text-sm text-gray-500">
            ¿Ya tienes cuenta?
            <a href="{{ route('login') }}" class="font-medium text-blue-600 hover:text-blue-500">Inicia sesión</a>
        </p>

    </div>
</div>

<x-umami-track />
</body>
</html>
