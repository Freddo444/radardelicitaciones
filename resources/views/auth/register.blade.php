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

<x-site-nav variant="solid" />

<div class="flex min-h-full items-center justify-center px-4 py-12 sm:px-6 lg:px-8"
     x-data="{
        companies: 1,
        users: 2,
        annual: false,
        loadingGateway: null,
        error: null,
        email: '',
        get loading() { return this.loadingGateway !== null; },
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
        validateEmail() {
            if (!this.email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.email)) {
                this.error = 'Ingresa un correo electrónico válido.';
                return false;
            }
            return true;
        },
        extractError(data, fallback) {
            if (data.error) return data.error;
            if (data.errors) {
                const first = Object.values(data.errors)[0];
                return Array.isArray(first) ? first[0] : first;
            }
            return fallback;
        },
        async payAzul() {
            if (!this.validateEmail()) return;
            this.loadingGateway = 'azul';
            this.error = null;
            try {
                const res = await fetch('{{ route('register.create-order-azul') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    },
                    body: JSON.stringify({
                        email: this.email,
                        max_companies: this.companies,
                        max_users: this.users,
                        billing_cycle: this.annual ? 'annual' : 'monthly',
                    }),
                });
                const data = await res.json();
                if (data.checkout_url) {
                    window.location.href = data.checkout_url;
                } else {
                    this.error = this.extractError(data, 'Error al iniciar el pago con Azul.');
                }
            } catch (e) {
                this.error = 'Error de conexión.';
            } finally {
                this.loadingGateway = null;
            }
        },
        async pay() {
            if (!this.validateEmail()) return;
            this.loadingGateway = 'paypal';
            this.error = null;
            try {
                const res = await fetch('{{ route('register.create-order') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    },
                    body: JSON.stringify({
                        email: this.email,
                        max_companies: this.companies,
                        max_users: this.users,
                        billing_cycle: this.annual ? 'annual' : 'monthly',
                    }),
                });
                const data = await res.json();
                if (data.approve_url) {
                    window.location.href = data.approve_url;
                } else {
                    this.error = this.extractError(data, 'Error al crear la orden.');
                }
            } catch (e) {
                this.error = 'Error de conexión.';
            } finally {
                this.loadingGateway = null;
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

        <div>
            <label for="email" class="block text-sm font-medium text-gray-700">Correo electrónico</label>
            <input id="email" type="email" x-model="email" required autocomplete="email"
                   placeholder="tu@empresa.com"
                   class="mt-1 block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-blue-600 sm:text-sm/6"/>
            <p class="mt-1 text-xs text-gray-500">Lo usarás para iniciar sesión. Se pre-llenará en el siguiente paso.</p>
        </div>

        {{-- Payment method cards --}}
        <div class="space-y-3">
            <div class="relative">
                <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-gray-200"></div></div>
                <div class="relative flex justify-center"><span class="bg-white px-3 text-xs font-medium uppercase tracking-widest text-gray-400">Método de pago</span></div>
            </div>

            {{-- PayPal --}}
            <button @click="pay()" :disabled="loading"
                    class="group flex w-full items-center gap-4 rounded-2xl border border-gray-200 bg-white px-4 py-3.5 text-left shadow-sm transition-all duration-150 hover:border-blue-300 hover:shadow-md active:scale-[.99] disabled:cursor-not-allowed disabled:opacity-60 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#003087]">
                <div class="flex h-12 w-14 shrink-0 items-center justify-center rounded-xl bg-[#EBF4FF]">
                    {{-- PayPal PP mark (blue variant) --}}
                    <svg viewBox="0 0 28 18" fill="none" xmlns="http://www.w3.org/2000/svg" class="h-5 w-auto">
                        <path d="M3.5 17L5.7 3h4.8c2.3 0 3.8.6 4.4 1.8.5.9.5 2.2-.1 3.7-.7 2.1-2.2 3.2-4.5 3.2H8.1L7.1 17H3.5z" fill="#003087"/>
                        <path d="M8.9 9.7l.4-2.4h1.9c1 0 1.5.4 1.5 1.2 0 .9-.8 1.4-2.3 1.4H8.9z" fill="#EBF4FF" opacity=".9"/>
                        <path d="M10 17L12.2 3H17c2.3 0 3.8.6 4.4 1.8.5.9.5 2.2-.1 3.7-.7 2.1-2.2 3.2-4.5 3.2h-2.2L13.6 17H10z" fill="#009CDE"/>
                        <path d="M15.4 9.7l.4-2.4h1.9c1 0 1.5.4 1.5 1.2 0 .9-.8 1.4-2.3 1.4h-1.5z" fill="#EBF4FF" opacity=".9"/>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-gray-900" x-text="loadingGateway === 'paypal' ? 'Redirigiendo a PayPal…' : 'PayPal'"></p>
                    <p class="text-xs text-gray-400">Paga con tu cuenta PayPal</p>
                </div>
                <div class="shrink-0 text-gray-300 group-hover:text-gray-500 transition-colors">
                    <svg x-show="loadingGateway !== 'paypal'" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5">
                        <path fill-rule="evenodd" d="M8.22 5.22a.75.75 0 0 1 1.06 0l4.25 4.25a.75.75 0 0 1 0 1.06l-4.25 4.25a.75.75 0 0 1-1.06-1.06L11.94 10 8.22 6.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/>
                    </svg>
                    <svg x-show="loadingGateway === 'paypal'" class="h-5 w-5 animate-spin text-[#003087]" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                </div>
            </button>

            {{-- Azul (card payment) --}}
            @if(config('services.azul.merchant_id') && config('services.azul.auth_key'))
            <button type="button" @click="payAzul()" :disabled="loading"
                    class="group flex w-full items-center gap-4 rounded-2xl border border-gray-200 bg-white px-4 py-3.5 text-left shadow-sm transition-all duration-150 hover:border-blue-300 hover:shadow-md active:scale-[.99] disabled:cursor-not-allowed disabled:opacity-60 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500">
                <div class="flex h-12 w-14 shrink-0 items-center justify-center rounded-xl bg-[#00539B]">
                    <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z"/>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-gray-900" x-text="loadingGateway === 'azul' ? 'Preparando pago…' : 'Tarjeta de crédito / débito'"></p>
                    <p class="text-xs text-gray-400">Visa · Mastercard · procesado por Azul</p>
                </div>
                <div class="shrink-0 text-gray-300 group-hover:text-gray-500 transition-colors">
                    <svg x-show="loadingGateway !== 'azul'" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5">
                        <path fill-rule="evenodd" d="M8.22 5.22a.75.75 0 0 1 1.06 0l4.25 4.25a.75.75 0 0 1 0 1.06l-4.25 4.25a.75.75 0 0 1-1.06-1.06L11.94 10 8.22 6.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/>
                    </svg>
                    <svg x-show="loadingGateway === 'azul'" class="h-5 w-5 animate-spin text-[#00539B]" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                </div>
            </button>
            @endif
        </div>

        <p x-show="error" x-cloak class="rounded-lg bg-red-50 px-4 py-2.5 text-center text-sm text-red-600" x-text="error"></p>

        <p class="text-center text-sm text-gray-500">
            ¿Quieres probar primero?
            <a href="{{ route('register.trial') }}" class="font-medium text-blue-600 hover:text-blue-500">Prueba gratis 14 días</a>
        </p>

        <p class="text-center text-sm text-gray-500">
            ¿Ya tienes cuenta?
            <a href="{{ route('login') }}" class="font-medium text-blue-600 hover:text-blue-500">Inicia sesión</a>
        </p>

    </div>
</div>

<x-umami-track />
<x-tawk-script />
</body>
</html>
