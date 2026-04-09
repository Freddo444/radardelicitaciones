@extends('admin.layout')
@section('title', 'Suscripciones')

@section('content')
<div x-data="{ showCreate: false }">
    <div class="mb-10 flex flex-col gap-6 lg:mb-12 lg:flex-row lg:items-end lg:justify-between">
        <div class="max-w-2xl">
            <h1 class="text-2xl font-semibold tracking-tight text-zinc-900 sm:text-3xl">Suscripciones</h1>
            <p class="mt-3 text-base leading-relaxed text-zinc-600">Gestiona todas las suscripciones de la plataforma.</p>
        </div>
        <div class="shrink-0">
            <button @click="showCreate = !showCreate" type="button"
                class="rounded-lg bg-blue-600 px-4 py-2.5 text-center text-sm font-semibold text-white shadow-sm hover:bg-blue-500">
                Crear Trial
            </button>
        </div>
    </div>

    {{-- Create trial panel --}}
    <div x-show="showCreate" x-cloak @click.outside="showCreate = false"
        class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-zinc-900/50 px-4 py-10 backdrop-blur-sm sm:py-16">
        <div class="w-full max-w-lg rounded-2xl bg-white p-8 shadow-2xl ring-1 ring-zinc-900/10" @click.stop>
            <form method="POST" action="{{ route('admin.subscriptions.create-trial') }}">
                @csrf
                <h3 class="text-xl font-semibold tracking-tight text-zinc-900">Crear Trial</h3>
                <p class="mt-2 text-sm leading-relaxed text-zinc-600">Crea una cuenta con trial personalizado. Se envia email con credenciales.</p>

                <div class="mt-8 space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-zinc-700">Nombre</label>
                        <input type="text" name="name" required
                            class="mt-2 block w-full rounded-lg border-0 py-2.5 pl-3 pr-3 text-sm text-zinc-900 shadow-sm ring-1 ring-inset ring-zinc-300 placeholder:text-zinc-400 focus:ring-2 focus:ring-indigo-600">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700">Email</label>
                        <input type="email" name="email" required
                            class="mt-2 block w-full rounded-lg border-0 py-2.5 pl-3 pr-3 text-sm text-zinc-900 shadow-sm ring-1 ring-inset ring-zinc-300 placeholder:text-zinc-400 focus:ring-2 focus:ring-indigo-600">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700">Duracion</label>
                        <div class="mt-2 mb-2 flex flex-wrap gap-2">
                            <button type="button" @click="$refs.ctDuration.value = 30" class="rounded-lg bg-zinc-100 px-3 py-1.5 text-xs font-medium text-zinc-700 hover:bg-zinc-200">1 mes</button>
                            <button type="button" @click="$refs.ctDuration.value = 60" class="rounded-lg bg-zinc-100 px-3 py-1.5 text-xs font-medium text-zinc-700 hover:bg-zinc-200">2 meses</button>
                            <button type="button" @click="$refs.ctDuration.value = 90" class="rounded-lg bg-zinc-100 px-3 py-1.5 text-xs font-medium text-zinc-700 hover:bg-zinc-200">3 meses</button>
                        </div>
                        <input type="number" name="duration" x-ref="ctDuration" value="30" min="1" max="365"
                            class="block w-full rounded-lg border-0 py-2.5 pl-3 pr-3 text-sm text-zinc-900 shadow-sm ring-1 ring-inset ring-zinc-300 focus:ring-2 focus:ring-indigo-600">
                        <p class="mt-1.5 text-xs text-zinc-500">dias</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700">Limite de analisis IA</label>
                        <input type="number" name="parse_limit" value="100" min="0" max="9999"
                            class="mt-2 block w-full rounded-lg border-0 py-2.5 pl-3 pr-3 text-sm text-zinc-900 shadow-sm ring-1 ring-inset ring-zinc-300 focus:ring-2 focus:ring-indigo-600">
                        <p class="mt-1.5 text-xs text-zinc-500">0 = sin acceso IA, 9999 = ilimitado</p>
                    </div>
                </div>

                <div class="mt-10 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <button type="button" @click="showCreate = false"
                        class="rounded-lg bg-white px-4 py-2.5 text-sm font-semibold text-zinc-900 shadow-sm ring-1 ring-inset ring-zinc-300 hover:bg-zinc-50">Cancelar</button>
                    <button type="submit"
                        class="rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-500">Crear y enviar email</button>
                </div>
            </form>
        </div>
    </div>

<form method="GET" class="mb-10 flex flex-wrap items-end gap-4 rounded-xl bg-white p-6 shadow-sm ring-1 ring-zinc-900/5">
    <div>
        <label for="status" class="block text-xs font-semibold tracking-wide text-zinc-600 uppercase">Estado</label>
        <select name="status" id="status" class="mt-2 min-w-[11rem] rounded-lg border-0 py-2.5 pl-3 pr-8 text-sm text-zinc-900 shadow-sm ring-1 ring-inset ring-zinc-300 focus:ring-2 focus:ring-indigo-600">
            <option value="">Todos</option>
            @foreach(['pending', 'active', 'trialing', 'past_due', 'cancelled', 'suspended'] as $s)
                <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
            @endforeach
        </select>
    </div>
    <div class="min-w-0 flex-1 sm:min-w-[16rem] sm:max-w-md">
        <label for="q" class="block text-xs font-semibold tracking-wide text-zinc-600 uppercase">Buscar</label>
        <input type="text" name="q" id="q" value="{{ request('q') }}" placeholder="Nombre o email..."
               class="mt-2 w-full rounded-lg border-0 py-2.5 pl-3 pr-3 text-sm text-zinc-900 shadow-sm ring-1 ring-inset ring-zinc-300 placeholder:text-zinc-400 focus:ring-2 focus:ring-indigo-600">
    </div>
    <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">Filtrar</button>
</form>

<div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-zinc-900/5">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-zinc-200">
                <thead class="bg-zinc-50/80">
                    <tr>
                        <th class="py-4 pr-3 pl-5 text-left text-xs font-semibold tracking-wide text-zinc-600 uppercase">Usuario</th>
                        <th class="px-4 py-4 text-left text-xs font-semibold tracking-wide text-zinc-600 uppercase">Plan</th>
                        <th class="px-4 py-4 text-left text-xs font-semibold tracking-wide text-zinc-600 uppercase">Monto</th>
                        <th class="px-4 py-4 text-left text-xs font-semibold tracking-wide text-zinc-600 uppercase">Estado</th>
                        <th class="px-4 py-4 text-left text-xs font-semibold tracking-wide text-zinc-600 uppercase">Vence</th>
                        <th class="min-w-[14rem] py-4 pr-5 pl-3 text-left text-xs font-semibold tracking-wide text-zinc-600 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 bg-white">
                    @forelse($subscriptions as $sub)
                    <tr class="hover:bg-zinc-50/50">
                        <td class="py-5 pr-3 pl-5 align-top text-sm whitespace-nowrap">
                            <span class="font-medium text-zinc-900">{{ $sub->owner?->name ?? '—' }}</span>
                            <span class="mt-0.5 block text-xs text-zinc-500">{{ $sub->owner?->email }}</span>
                        </td>
                        <td class="px-4 py-5 align-top text-sm whitespace-nowrap text-zinc-600">{{ ucfirst($sub->plan) }}</td>
                        <td class="px-4 py-5 align-top text-sm font-semibold whitespace-nowrap text-zinc-900">${{ number_format($sub->monthly_amount, 2) }}</td>
                        <td class="px-4 py-5 align-top text-sm whitespace-nowrap">
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
                        <td class="px-4 py-5 align-top text-sm whitespace-nowrap text-zinc-600">
                            @if($sub->status === 'trialing' && $sub->trial_ends_at)
                                {{ $sub->trial_ends_at->format('d/m/Y') }}
                            @else
                                {{ $sub->current_period_end?->format('d/m/Y') ?? '—' }}
                            @endif
                        </td>
                        <td class="py-5 pr-5 pl-3 align-top text-sm">
                            <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
                                {{-- Status change --}}
                                <form method="POST" action="{{ route('admin.subscriptions.update-status', $sub) }}" class="flex flex-col gap-2 sm:flex-row sm:items-center">
                                    @csrf @method('PATCH')
                                    <select name="status" class="min-w-[8.5rem] rounded-lg border-0 py-2 pl-2.5 pr-8 text-xs font-medium text-zinc-900 shadow-sm ring-1 ring-inset ring-zinc-300 focus:ring-2 focus:ring-indigo-600">
                                        @foreach(['pending', 'active', 'trialing', 'past_due', 'cancelled', 'suspended'] as $s)
                                            <option value="{{ $s }}" {{ $sub->status === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="rounded-lg bg-white px-3 py-2 text-xs font-semibold text-zinc-900 shadow-sm ring-1 ring-inset ring-zinc-300 hover:bg-zinc-50">Aplicar</button>
                                </form>

                                {{-- Grant trial --}}
                                <div x-data="{ open: false }" class="relative shrink-0">
                                    <button @click="open = !open" type="button"
                                        class="rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white shadow-sm hover:bg-blue-500">
                                        Trial
                                    </button>
                                    <div x-show="open" @click.outside="open = false" x-cloak
                                        class="absolute right-0 z-20 mt-2 w-80 rounded-xl bg-white p-5 shadow-xl ring-1 ring-zinc-900/10 sm:left-0 sm:right-auto">
                                        <form method="POST" action="{{ route('admin.subscriptions.grant-trial', $sub) }}">
                                            @csrf
                                            <h4 class="mb-4 text-sm font-semibold text-zinc-900">Otorgar Trial</h4>
                                            <div class="space-y-4">
                                                <div>
                                                    <label class="mb-2 block text-xs font-medium text-zinc-700">Duracion</label>
                                                    <div class="mb-2 flex flex-wrap gap-2">
                                                        <button type="button" @click="$refs.duration.value = 30" class="rounded-lg bg-zinc-100 px-2.5 py-1 text-xs font-medium text-zinc-700 hover:bg-zinc-200">1 mes</button>
                                                        <button type="button" @click="$refs.duration.value = 60" class="rounded-lg bg-zinc-100 px-2.5 py-1 text-xs font-medium text-zinc-700 hover:bg-zinc-200">2 meses</button>
                                                        <button type="button" @click="$refs.duration.value = 90" class="rounded-lg bg-zinc-100 px-2.5 py-1 text-xs font-medium text-zinc-700 hover:bg-zinc-200">3 meses</button>
                                                    </div>
                                                    <input type="number" name="duration" x-ref="duration" value="30" min="1" max="365"
                                                        class="w-full rounded-lg border-0 py-2 pl-3 pr-3 text-sm text-zinc-900 shadow-sm ring-1 ring-inset ring-zinc-300 focus:ring-2 focus:ring-indigo-600">
                                                    <p class="mt-1 text-xs text-zinc-500">dias</p>
                                                </div>
                                                <div>
                                                    <label class="mb-2 block text-xs font-medium text-zinc-700">Limite de analisis IA</label>
                                                    <input type="number" name="parse_limit" value="100" min="0" max="9999"
                                                        class="w-full rounded-lg border-0 py-2 pl-3 pr-3 text-sm text-zinc-900 shadow-sm ring-1 ring-inset ring-zinc-300 focus:ring-2 focus:ring-indigo-600">
                                                    <p class="mt-1 text-xs text-zinc-500">0 = sin acceso IA, 9999 = ilimitado</p>
                                                </div>
                                            </div>
                                            <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                                                <button type="button" @click="open = false" class="rounded-lg bg-white px-3 py-2 text-xs font-semibold text-zinc-900 shadow-sm ring-1 ring-inset ring-zinc-300 hover:bg-zinc-50">Cancelar</button>
                                                <button type="submit" class="rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white shadow-sm hover:bg-blue-500">Otorgar</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-16 text-center text-sm text-zinc-500">No se encontraron suscripciones.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

<div class="mt-8">{{ $subscriptions->links() }}</div>
</div>
@endsection
