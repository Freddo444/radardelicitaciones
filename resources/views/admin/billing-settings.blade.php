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
            <dd class="mt-1 text-xs">
                @if($source === 'auto')
                    <span class="rounded bg-emerald-50 px-1.5 py-0.5 font-medium text-emerald-700">Banco Popular</span>
                @elseif($source === 'manual')
                    <span class="rounded bg-amber-50 px-1.5 py-0.5 font-medium text-amber-700">Valor manual</span>
                @else
                    <span class="rounded bg-zinc-100 px-1.5 py-0.5 font-medium text-zinc-600">Respaldo (.env)</span>
                @endif
            </dd>
        </div>
        <div>
            <dt class="font-medium text-zinc-500">Última lectura del banco</dt>
            <dd class="mt-1 text-zinc-700">
                @if($auto_rate !== null)
                    {{ number_format($auto_rate, 4, '.', ',') }} DOP
                    <span class="text-zinc-400">
                        · {{ $auto_fetched_at ? $auto_fetched_at->diffForHumans() : 'fecha desconocida' }}
                    </span>
                @elseif(! $bpd_configured)
                    <span class="text-zinc-400">Banco Popular no configurado</span>
                @else
                    <span class="text-zinc-400">Todavía sin lecturas</span>
                @endif
            </dd>
        </div>
    </dl>

    @if($auto_is_stale)
        <div class="mb-6 rounded-lg bg-amber-50 p-3 text-sm text-amber-800">
            La última lectura del Banco Popular tiene más de {{ config('services.bpd.max_age_hours') }} horas.
            Se sigue usando porque una tasa real vieja es mejor que una constante, pero conviene revisar
            <code class="rounded bg-amber-100 px-1">storage/logs/billing-usd-rate.log</code>.
        </div>
    @endif

    @if($mode === 'manual')
        <div class="mb-6 rounded-lg bg-amber-50 p-3 text-sm text-amber-800">
            Modo <strong>manual</strong>: se ignora la tasa del banco y se cobra con el valor de abajo.
        </div>
    @endif

    <form method="POST" action="{{ route('admin.billing-settings.update') }}" class="space-y-5">
        @csrf
        @method('PATCH')
        <fieldset>
            <legend class="block text-sm font-medium text-zinc-800">Origen de la tasa</legend>
            <div class="mt-2 space-y-2">
                <label class="flex items-start gap-2 text-sm">
                    <input type="radio" name="mode" value="auto" class="mt-1" @checked(old('mode', $mode) === 'auto')>
                    <span><strong>Automática</strong> — se lee del Banco Popular dos veces al día.
                    <span class="text-zinc-500">Si el banco no responde se usa el valor de abajo.</span></span>
                </label>
                <label class="flex items-start gap-2 text-sm">
                    <input type="radio" name="mode" value="manual" class="mt-1" @checked(old('mode', $mode) === 'manual')>
                    <span><strong>Manual</strong> — se cobra siempre con el valor de abajo.</span>
                </label>
            </div>
            @error('mode')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
        </fieldset>

        <div>
            <label for="usd_dop_rate" class="block text-sm font-medium text-zinc-800">DOP por 1 USD <span class="font-normal text-zinc-500">(manual / respaldo)</span></label>
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
