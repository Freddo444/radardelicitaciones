<!DOCTYPE html>
<html lang="es" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }} — Transferencia bancaria</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <x-umami-script />
</head>
<body class="h-full">

<div class="min-h-full">
    <div class="mx-auto max-w-2xl px-4 py-12 sm:px-6 lg:px-8">

        <a href="{{ route('billing.index') }}" class="text-sm text-blue-600 hover:text-blue-500">&larr; Volver a facturacion</a>

        <h1 class="mt-4 text-2xl font-bold text-gray-900">Transferencia bancaria</h1>
        <p class="mt-1 text-sm text-gray-500">Realiza la transferencia y sube el comprobante.</p>

        @if(session('success'))
        <div class="mt-4 rounded-md bg-green-50 p-3 text-sm text-green-700">{{ session('success') }}</div>
        @endif
        @if(session('error'))
        <div class="mt-4 rounded-md bg-red-50 p-3 text-sm text-red-700">{{ session('error') }}</div>
        @endif

        <div class="mt-6 rounded-lg bg-white p-6 shadow ring-1 ring-gray-900/5">
            <h2 class="font-semibold text-gray-900">Datos bancarios</h2>
            <dl class="mt-4 space-y-3 text-sm">
                <div class="flex justify-between">
                    <dt class="text-gray-500">Banco</dt>
                    <dd class="font-medium text-gray-900">{{ $bank['name'] }}</dd>
                </div>
                <div class="flex items-center justify-between">
                    <dt class="text-gray-500">Cuenta</dt>
                    <dd class="font-mono text-base font-bold text-gray-900 select-all">{{ $bank['account_number'] }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Tipo</dt>
                    <dd class="font-medium text-gray-900">{{ $bank['account_type'] }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Titular</dt>
                    <dd class="font-medium text-gray-900">{{ $bank['holder'] }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">RNC</dt>
                    <dd class="font-medium text-gray-900">{{ $bank['rnc'] }}</dd>
                </div>
                <div class="flex items-baseline justify-between border-t pt-3">
                    <dt class="font-medium text-gray-900">Monto a transferir</dt>
                    <dd class="text-right">
                        <span class="block text-lg font-bold text-gray-900">RD${{ number_format($amountDop, 2) }}</span>
                        <span class="block text-xs text-gray-500">US${{ number_format($subscription->monthly_amount, 2) }} · tasa 1 USD = {{ number_format($rate, 2) }} DOP</span>
                    </dd>
                </div>
            </dl>
            <p class="mt-4 rounded-md bg-blue-50 p-3 text-xs text-blue-800">
                Transfiere el monto en pesos (RD$) a la cuenta indicada y sube el comprobante abajo. Tu suscripción se activa cuando confirmamos el pago.
            </p>
        </div>

        <div class="mt-6 rounded-lg bg-white p-6 shadow ring-1 ring-gray-900/5">
            <h2 class="font-semibold text-gray-900">Subir comprobante</h2>

            <form method="POST" action="{{ route('billing.bank-transfer.upload') }}" enctype="multipart/form-data" class="mt-4 space-y-4">
                @csrf

                <div>
                    <label for="receipt" class="block text-sm font-medium text-gray-700">Comprobante (PDF, JPG, PNG)</label>
                    <input id="receipt" type="file" name="receipt" required accept=".pdf,.jpg,.jpeg,.png"
                           class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:rounded-md file:border-0 file:bg-blue-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-blue-700 hover:file:bg-blue-100"/>
                    @error('receipt') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="amount_transferred" class="block text-sm font-medium text-gray-700">Monto transferido (RD$)</label>
                    <input id="amount_transferred" type="number" step="0.01" min="0" name="amount_transferred"
                           value="{{ old('amount_transferred', $amountDop) }}"
                           class="mt-1 block w-full rounded-md bg-white px-3 py-1.5 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-blue-600"/>
                    @error('amount_transferred') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="notes" class="block text-sm font-medium text-gray-700">Notas (opcional)</label>
                    <input id="notes" type="text" name="notes" maxlength="500"
                           class="mt-1 block w-full rounded-md bg-white px-3 py-1.5 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-blue-600"/>
                </div>

                <button type="submit" class="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500">
                    Enviar comprobante
                </button>
            </form>
        </div>

    </div>
</div>

<x-umami-track />
</body>
</html>
