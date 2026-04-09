@extends('admin.layout')
@section('title', 'Pagos')

@section('content')
<div class="mb-10 lg:mb-12">
    <h1 class="text-2xl font-semibold tracking-tight text-zinc-900 sm:text-3xl">Pagos</h1>
    <p class="mt-3 max-w-2xl text-base leading-relaxed text-zinc-600">Historial completo de pagos de la plataforma.</p>
</div>

<form method="GET" class="mb-10 flex flex-wrap items-end gap-4 rounded-xl bg-white p-6 shadow-sm ring-1 ring-zinc-900/5">
    <div>
        <label for="status" class="block text-xs font-semibold tracking-wide text-zinc-600 uppercase">Estado</label>
        <select name="status" id="status" class="mt-2 min-w-40 rounded-lg border-0 py-2.5 pl-3 pr-8 text-sm text-zinc-900 shadow-sm ring-1 ring-inset ring-zinc-300 focus:ring-2 focus:ring-indigo-600">
            <option value="">Todos</option>
            @foreach(['pending', 'completed', 'failed', 'refunded'] as $s)
                <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="gateway" class="block text-xs font-semibold tracking-wide text-zinc-600 uppercase">Gateway</label>
        <select name="gateway" id="gateway" class="mt-2 min-w-40 rounded-lg border-0 py-2.5 pl-3 pr-8 text-sm text-zinc-900 shadow-sm ring-1 ring-inset ring-zinc-300 focus:ring-2 focus:ring-indigo-600">
            <option value="">Todos</option>
            @foreach(['paypal', 'azul', 'bank_transfer'] as $g)
                <option value="{{ $g }}" {{ request('gateway') === $g ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $g)) }}</option>
            @endforeach
        </select>
    </div>
    <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">Filtrar</button>
</form>

<div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-zinc-900/5">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-zinc-200">
                <thead class="bg-zinc-50/80">
                    <tr>
                        <th class="py-4 pr-3 pl-5 text-left text-xs font-semibold tracking-wide text-zinc-600 uppercase">#</th>
                        <th class="px-4 py-4 text-left text-xs font-semibold tracking-wide text-zinc-600 uppercase">Cliente</th>
                        <th class="px-4 py-4 text-left text-xs font-semibold tracking-wide text-zinc-600 uppercase">Monto</th>
                        <th class="px-4 py-4 text-left text-xs font-semibold tracking-wide text-zinc-600 uppercase">Gateway</th>
                        <th class="px-4 py-4 text-left text-xs font-semibold tracking-wide text-zinc-600 uppercase">Estado</th>
                        <th class="px-4 py-4 text-left text-xs font-semibold tracking-wide text-zinc-600 uppercase">Fecha</th>
                        <th class="py-4 pr-5 pl-3 text-right"><span class="sr-only">Acciones</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 bg-white">
                    @forelse($payments as $p)
                    <tr class="hover:bg-zinc-50/50">
                        <td class="py-5 pr-3 pl-5 text-sm font-medium whitespace-nowrap text-zinc-900">{{ $p->id }}</td>
                        <td class="px-4 py-5 text-sm whitespace-nowrap">
                            <span class="font-medium text-zinc-900">{{ $p->subscription?->owner?->name ?? '—' }}</span>
                            <span class="mt-0.5 block text-xs text-zinc-500">{{ $p->subscription?->owner?->email }}</span>
                        </td>
                        <td class="px-4 py-5 text-sm font-semibold whitespace-nowrap text-zinc-900">${{ number_format($p->amount, 2) }} {{ $p->currency }}</td>
                        <td class="px-4 py-5 text-sm whitespace-nowrap text-zinc-600">{{ ucfirst(str_replace('_', ' ', $p->gateway)) }}</td>
                        <td class="px-4 py-5 text-sm whitespace-nowrap">
                            @if($p->status === 'completed')
                            <span class="inline-flex items-center gap-x-1.5 rounded-md bg-green-100 px-2 py-1 text-xs font-medium text-green-700">
                                <svg viewBox="0 0 6 6" aria-hidden="true" class="size-1.5 fill-green-500"><circle r="3" cx="3" cy="3" /></svg>Completado
                            </span>
                            @elseif($p->status === 'pending')
                            <span class="inline-flex items-center gap-x-1.5 rounded-md bg-yellow-100 px-2 py-1 text-xs font-medium text-yellow-800">
                                <svg viewBox="0 0 6 6" aria-hidden="true" class="size-1.5 fill-yellow-500"><circle r="3" cx="3" cy="3" /></svg>Pendiente
                            </span>
                            @elseif($p->status === 'failed')
                            <span class="inline-flex items-center gap-x-1.5 rounded-md bg-red-100 px-2 py-1 text-xs font-medium text-red-700">
                                <svg viewBox="0 0 6 6" aria-hidden="true" class="size-1.5 fill-red-500"><circle r="3" cx="3" cy="3" /></svg>Fallido
                            </span>
                            @else
                            <span class="inline-flex items-center gap-x-1.5 rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-600">
                                <svg viewBox="0 0 6 6" aria-hidden="true" class="size-1.5 fill-gray-400"><circle r="3" cx="3" cy="3" /></svg>{{ ucfirst($p->status) }}
                            </span>
                            @endif
                        </td>
                        <td class="px-4 py-5 text-sm whitespace-nowrap text-zinc-600">{{ $p->created_at->format('d/m/Y H:i') }}</td>
                        <td class="py-5 pr-5 pl-3 text-right text-sm font-medium whitespace-nowrap">
                            @if($p->status === 'pending')
                            <form method="POST" action="{{ route('admin.payments.confirm', $p) }}">
                                @csrf @method('PATCH')
                                <button type="submit" class="font-semibold text-indigo-600 hover:text-indigo-800">Confirmar</button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-16 text-center text-sm text-zinc-500">No se encontraron pagos.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

<div class="mt-8">{{ $payments->links() }}</div>
@endsection
