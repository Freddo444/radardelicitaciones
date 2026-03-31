@extends('admin.layout')
@section('title', 'Pagos')

@section('content')
<div class="sm:flex sm:items-center">
    <div class="sm:flex-auto">
        <h1 class="text-base font-semibold text-gray-900">Pagos</h1>
        <p class="mt-2 text-sm text-gray-700">Historial completo de pagos de la plataforma.</p>
    </div>
</div>

<form method="GET" class="mt-6 flex flex-wrap items-end gap-3">
    <div>
        <label for="status" class="block text-xs font-medium text-gray-700">Estado</label>
        <select name="status" id="status" class="mt-1 rounded-md border-0 py-1.5 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-indigo-600">
            <option value="">Todos</option>
            @foreach(['pending', 'completed', 'failed', 'refunded'] as $s)
                <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="gateway" class="block text-xs font-medium text-gray-700">Gateway</label>
        <select name="gateway" id="gateway" class="mt-1 rounded-md border-0 py-1.5 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-indigo-600">
            <option value="">Todos</option>
            @foreach(['paypal', 'azul', 'bank_transfer'] as $g)
                <option value="{{ $g }}" {{ request('gateway') === $g ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $g)) }}</option>
            @endforeach
        </select>
    </div>
    <button type="submit" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500">Filtrar</button>
</form>

<div class="mt-8 flow-root">
    <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
        <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
            <table class="min-w-full divide-y divide-gray-300">
                <thead>
                    <tr>
                        <th class="py-3.5 pr-3 pl-4 text-left text-sm font-semibold text-gray-900 sm:pl-3">#</th>
                        <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Cliente</th>
                        <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Monto</th>
                        <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Gateway</th>
                        <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Estado</th>
                        <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Fecha</th>
                        <th class="py-3.5 pr-4 pl-3 sm:pr-3"><span class="sr-only">Acciones</span></th>
                    </tr>
                </thead>
                <tbody class="bg-white">
                    @forelse($payments as $p)
                    <tr class="even:bg-gray-50">
                        <td class="py-4 pr-3 pl-4 text-sm font-medium whitespace-nowrap text-gray-900 sm:pl-3">{{ $p->id }}</td>
                        <td class="px-3 py-4 text-sm whitespace-nowrap">
                            <span class="font-medium text-gray-900">{{ $p->subscription?->owner?->name ?? '—' }}</span>
                            <span class="block text-xs text-gray-400">{{ $p->subscription?->owner?->email }}</span>
                        </td>
                        <td class="px-3 py-4 text-sm whitespace-nowrap text-gray-900 font-medium">${{ number_format($p->amount, 2) }} {{ $p->currency }}</td>
                        <td class="px-3 py-4 text-sm whitespace-nowrap text-gray-500">{{ ucfirst(str_replace('_', ' ', $p->gateway)) }}</td>
                        <td class="px-3 py-4 text-sm whitespace-nowrap">
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
                        <td class="px-3 py-4 text-sm whitespace-nowrap text-gray-500">{{ $p->created_at->format('d/m/Y H:i') }}</td>
                        <td class="py-4 pr-4 pl-3 text-right text-sm font-medium whitespace-nowrap sm:pr-3">
                            @if($p->status === 'pending')
                            <form method="POST" action="{{ route('admin.payments.confirm', $p) }}">
                                @csrf @method('PATCH')
                                <button type="submit" class="text-indigo-600 hover:text-indigo-900">Confirmar</button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-10 text-center text-sm text-gray-500">No se encontraron pagos.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-6">{{ $payments->links() }}</div>
@endsection
