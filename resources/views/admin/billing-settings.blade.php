@extends('admin.layout')
@section('title', 'Tipo de cambio USD/DOP')

@section('content')
<div class="mb-10 lg:mb-12">
    <h1 class="text-2xl font-semibold tracking-tight text-zinc-900 sm:text-3xl">Tipo de cambio (facturación)</h1>
    <p class="mt-3 max-w-2xl text-base leading-relaxed text-zinc-600">
        Se usa al cobrar suscripciones con <strong>Azul</strong>: el monto en USD del plan se convierte a pesos dominicanos con este valor antes de enviarlo a la Página de Pagos.
    </p>
</div>

<div class="max-w-xl rounded-xl bg-white p-8 shadow-sm ring-1 ring-zinc-900/5">
    <dl class="mb-6 grid gap-4 text-sm sm:grid-cols-2">
        <div>
            <dt class="font-medium text-zinc-500">Vigente ahora</dt>
            <dd class="mt-1 text-lg font-semibold text-zinc-900">1 USD = {{ number_format($effective_rate, 2, '.', ',') }} DOP</dd>
        </div>
        <div>
            <dt class="font-medium text-zinc-500">Respaldo (.env)</dt>
            <dd class="mt-1 text-zinc-700">{{ number_format($config_fallback, 2, '.', ',') }} <span class="text-zinc-400">(si no hay valor en base de datos)</span></dd>
        </div>
    </dl>

    <form method="POST" action="{{ route('admin.billing-settings.update') }}" class="space-y-5">
        @csrf
        @method('PATCH')
        <div>
            <label for="usd_dop_rate" class="block text-sm font-medium text-zinc-800">DOP por 1 USD</label>
            <input type="text" inputmode="decimal" name="usd_dop_rate" id="usd_dop_rate" required
                   value="{{ old('usd_dop_rate', $stored_rate ?? (string) $effective_rate) }}"
                   class="mt-2 block w-full rounded-lg border border-zinc-300 px-3 py-2 text-zinc-900 shadow-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 sm:text-sm"
                   placeholder="62">
            @error('usd_dop_rate')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
        <div class="flex gap-3">
            <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                Guardar
            </button>
            <a href="{{ route('admin.dashboard') }}" class="rounded-lg px-4 py-2 text-sm font-semibold text-zinc-700 ring-1 ring-zinc-300 hover:bg-zinc-50">Cancelar</a>
        </div>
    </form>
</div>
@endsection
