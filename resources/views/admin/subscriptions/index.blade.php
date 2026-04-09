@extends('admin.layout')
@section('title', 'Suscripciones')

@section('content')
<div class="sm:flex sm:items-center" x-data="{ showCreate: false }">
    <div class="sm:flex-auto">
        <h1 class="text-base font-semibold text-gray-900">Suscripciones</h1>
        <p class="mt-2 text-sm text-gray-700">Gestiona todas las suscripciones de la plataforma.</p>
    </div>
    <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none">
        <button @click="showCreate = !showCreate" type="button"
            class="rounded-md bg-blue-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-xs hover:bg-blue-500">
            Crear Trial
        </button>
    </div>

    {{-- Create trial panel --}}
    <div x-show="showCreate" x-cloak @click.outside="showCreate = false"
        class="fixed inset-0 z-50 flex items-start justify-center pt-20 bg-gray-900/50">
        <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl" @click.stop>
            <form method="POST" action="{{ route('admin.subscriptions.create-trial') }}">
                @csrf
                <h3 class="text-lg font-semibold text-gray-900">Crear Trial</h3>
                <p class="mt-1 text-sm text-gray-500">Crea una cuenta con trial personalizado. Se envia email con credenciales.</p>

                <div class="mt-5 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nombre</label>
                        <input type="text" name="name" required
                            class="mt-1 w-full rounded-md border-0 py-1.5 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-indigo-600">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" name="email" required
                            class="mt-1 w-full rounded-md border-0 py-1.5 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-indigo-600">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Duracion</label>
                        <div class="mt-1 flex gap-1.5 mb-1.5">
                            <button type="button" @click="$refs.ctDuration.value = 30" class="rounded bg-gray-100 px-2.5 py-1 text-xs text-gray-700 hover:bg-gray-200">1 mes</button>
                            <button type="button" @click="$refs.ctDuration.value = 60" class="rounded bg-gray-100 px-2.5 py-1 text-xs text-gray-700 hover:bg-gray-200">2 meses</button>
                            <button type="button" @click="$refs.ctDuration.value = 90" class="rounded bg-gray-100 px-2.5 py-1 text-xs text-gray-700 hover:bg-gray-200">3 meses</button>
                        </div>
                        <input type="number" name="duration" x-ref="ctDuration" value="30" min="1" max="365"
                            class="w-full rounded-md border-0 py-1.5 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-indigo-600">
                        <p class="mt-0.5 text-xs text-gray-400">dias</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Limite de analisis IA</label>
                        <input type="number" name="parse_limit" value="100" min="0" max="9999"
                            class="w-full rounded-md border-0 py-1.5 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-indigo-600">
                        <p class="mt-0.5 text-xs text-gray-400">0 = sin acceso IA, 9999 = ilimitado</p>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" @click="showCreate = false"
                        class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-xs ring-1 ring-inset ring-gray-300 hover:bg-gray-50">Cancelar</button>
                    <button type="submit"
                        class="rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-xs hover:bg-blue-500">Crear y enviar email</button>
                </div>
            </form>
        </div>
    </div>
</div>

<form method="GET" class="mt-6 flex flex-wrap items-end gap-3">
    <div>
        <label for="status" class="block text-xs font-medium text-gray-700">Estado</label>
        <select name="status" id="status" class="mt-1 rounded-md border-0 py-1.5 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-indigo-600">
            <option value="">Todos</option>
            @foreach(['pending', 'active', 'trialing', 'past_due', 'cancelled', 'suspended'] as $s)
                <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="q" class="block text-xs font-medium text-gray-700">Buscar</label>
        <input type="text" name="q" id="q" value="{{ request('q') }}" placeholder="Nombre o email..."
               class="mt-1 rounded-md border-0 py-1.5 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-indigo-600 sm:w-64">
    </div>
    <button type="submit" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500">Filtrar</button>
</form>

<div class="mt-8 flow-root">
    <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
        <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
            <table class="min-w-full divide-y divide-gray-300">
                <thead>
                    <tr>
                        <th class="py-3.5 pr-3 pl-4 text-left text-sm font-semibold text-gray-900 sm:pl-3">Usuario</th>
                        <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Plan</th>
                        <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Monto</th>
                        <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Estado</th>
                        <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Vence</th>
                        <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white">
                    @forelse($subscriptions as $sub)
                    <tr class="even:bg-gray-50">
                        <td class="py-4 pr-3 pl-4 text-sm whitespace-nowrap sm:pl-3">
                            <span class="font-medium text-gray-900">{{ $sub->owner?->name ?? '—' }}</span>
                            <span class="block text-xs text-gray-400">{{ $sub->owner?->email }}</span>
                        </td>
                        <td class="px-3 py-4 text-sm whitespace-nowrap text-gray-500">{{ ucfirst($sub->plan) }}</td>
                        <td class="px-3 py-4 text-sm whitespace-nowrap text-gray-900 font-medium">${{ number_format($sub->monthly_amount, 2) }}</td>
                        <td class="px-3 py-4 text-sm whitespace-nowrap">
                            @if($sub->status === 'active')
                            <span class="inline-flex items-center gap-x-1.5 rounded-md bg-green-100 px-2 py-1 text-xs font-medium text-green-700">
                                <svg viewBox="0 0 6 6" aria-hidden="true" class="size-1.5 fill-green-500"><circle r="3" cx="3" cy="3" /></svg>Activa
                            </span>
                            @elseif($sub->status === 'trialing')
                            <span class="inline-flex items-center gap-x-1.5 rounded-md bg-blue-100 px-2 py-1 text-xs font-medium text-blue-700">
                                <svg viewBox="0 0 6 6" aria-hidden="true" class="size-1.5 fill-blue-500"><circle r="3" cx="3" cy="3" /></svg>Trial
                                @if($sub->trial_ends_at)
                                    <span class="text-blue-500">({{ $sub->trial_ends_at->diffForHumans() }})</span>
                                @endif
                            </span>
                            @elseif($sub->status === 'pending')
                            <span class="inline-flex items-center gap-x-1.5 rounded-md bg-yellow-100 px-2 py-1 text-xs font-medium text-yellow-800">
                                <svg viewBox="0 0 6 6" aria-hidden="true" class="size-1.5 fill-yellow-500"><circle r="3" cx="3" cy="3" /></svg>Pendiente
                            </span>
                            @elseif($sub->status === 'cancelled')
                            <span class="inline-flex items-center gap-x-1.5 rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-600">
                                <svg viewBox="0 0 6 6" aria-hidden="true" class="size-1.5 fill-gray-400"><circle r="3" cx="3" cy="3" /></svg>Cancelada
                            </span>
                            @else
                            <span class="inline-flex items-center gap-x-1.5 rounded-md bg-red-100 px-2 py-1 text-xs font-medium text-red-700">
                                <svg viewBox="0 0 6 6" aria-hidden="true" class="size-1.5 fill-red-500"><circle r="3" cx="3" cy="3" /></svg>{{ ucfirst($sub->status) }}
                            </span>
                            @endif
                        </td>
                        <td class="px-3 py-4 text-sm whitespace-nowrap text-gray-500">
                            @if($sub->status === 'trialing' && $sub->trial_ends_at)
                                {{ $sub->trial_ends_at->format('d/m/Y') }}
                            @else
                                {{ $sub->current_period_end?->format('d/m/Y') ?? '—' }}
                            @endif
                        </td>
                        <td class="px-3 py-4 text-sm whitespace-nowrap">
                            <div class="flex items-center gap-2">
                                {{-- Status change --}}
                                <form method="POST" action="{{ route('admin.subscriptions.update-status', $sub) }}" class="flex items-center gap-1.5">
                                    @csrf @method('PATCH')
                                    <select name="status" class="rounded-md border-0 py-1 text-xs text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-indigo-600">
                                        @foreach(['pending', 'active', 'trialing', 'past_due', 'cancelled', 'suspended'] as $s)
                                            <option value="{{ $s }}" {{ $sub->status === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="rounded-md bg-white px-2 py-1 text-xs font-semibold text-gray-900 shadow-xs ring-1 ring-inset ring-gray-300 hover:bg-gray-50">Aplicar</button>
                                </form>

                                {{-- Grant trial --}}
                                <div x-data="{ open: false }" class="relative">
                                    <button @click="open = !open" type="button"
                                        class="rounded-md bg-blue-600 px-2 py-1 text-xs font-semibold text-white shadow-xs hover:bg-blue-500">
                                        Trial
                                    </button>
                                    <div x-show="open" @click.outside="open = false" x-cloak
                                        class="absolute right-0 z-10 mt-2 w-72 rounded-lg bg-white p-4 shadow-lg ring-1 ring-gray-900/10">
                                        <form method="POST" action="{{ route('admin.subscriptions.grant-trial', $sub) }}">
                                            @csrf
                                            <h4 class="text-sm font-semibold text-gray-900 mb-3">Otorgar Trial</h4>
                                            <div class="space-y-3">
                                                <div>
                                                    <label class="block text-xs font-medium text-gray-700 mb-1">Duracion</label>
                                                    <div class="flex gap-1.5 mb-1.5">
                                                        <button type="button" @click="$refs.duration.value = 30" class="rounded bg-gray-100 px-2 py-1 text-xs text-gray-700 hover:bg-gray-200">1 mes</button>
                                                        <button type="button" @click="$refs.duration.value = 60" class="rounded bg-gray-100 px-2 py-1 text-xs text-gray-700 hover:bg-gray-200">2 meses</button>
                                                        <button type="button" @click="$refs.duration.value = 90" class="rounded bg-gray-100 px-2 py-1 text-xs text-gray-700 hover:bg-gray-200">3 meses</button>
                                                    </div>
                                                    <input type="number" name="duration" x-ref="duration" value="30" min="1" max="365"
                                                        class="w-full rounded-md border-0 py-1.5 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-indigo-600">
                                                    <p class="mt-0.5 text-xs text-gray-400">dias</p>
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-medium text-gray-700 mb-1">Limite de analisis IA</label>
                                                    <input type="number" name="parse_limit" value="100" min="0" max="9999"
                                                        class="w-full rounded-md border-0 py-1.5 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-indigo-600">
                                                    <p class="mt-0.5 text-xs text-gray-400">0 = sin acceso IA, 9999 = ilimitado</p>
                                                </div>
                                            </div>
                                            <div class="mt-4 flex justify-end gap-2">
                                                <button type="button" @click="open = false" class="rounded-md bg-white px-2.5 py-1.5 text-xs font-semibold text-gray-900 shadow-xs ring-1 ring-inset ring-gray-300 hover:bg-gray-50">Cancelar</button>
                                                <button type="submit" class="rounded-md bg-blue-600 px-2.5 py-1.5 text-xs font-semibold text-white shadow-xs hover:bg-blue-500">Otorgar</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-10 text-center text-sm text-gray-500">No se encontraron suscripciones.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-6">{{ $subscriptions->links() }}</div>
@endsection
