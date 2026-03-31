@extends('admin.layout')
@section('title', $company->razon_social)

@section('content')
<div class="mb-6">
    <a href="{{ route('admin.companies.index') }}" class="text-sm text-gray-500 hover:text-gray-700">&larr; Empresas</a>
</div>

<div class="sm:flex sm:items-start sm:justify-between">
    <div>
        <h1 class="text-base font-semibold text-gray-900">{{ $company->razon_social }}</h1>
        <p class="mt-1 text-sm text-gray-500">RNC: {{ $company->rnc }} &middot; Creada {{ $company->created_at->format('d/m/Y') }}</p>
    </div>
    <form method="POST" action="{{ route('admin.companies.impersonate', $company) }}" class="mt-4 sm:mt-0">
        @csrf
        <button type="submit" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-xs hover:bg-indigo-500">Impersonar</button>
    </form>
</div>

{{-- Stats --}}
<dl class="mt-8 grid grid-cols-1 gap-5 sm:grid-cols-3">
    <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow-sm ring-1 ring-gray-900/5 sm:p-6">
        <dt class="truncate text-sm font-medium text-gray-500">Usuarios</dt>
        <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900">{{ $company->users_count }}</dd>
    </div>
    <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow-sm ring-1 ring-gray-900/5 sm:p-6">
        <dt class="truncate text-sm font-medium text-gray-500">Rubros activos</dt>
        <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900">{{ $company->rubros_count }}</dd>
    </div>
    <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow-sm ring-1 ring-gray-900/5 sm:p-6">
        <dt class="truncate text-sm font-medium text-gray-500">Ofertas</dt>
        <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900">{{ $company->offers_count }}</dd>
    </div>
</dl>

{{-- Subscription --}}
<div class="mt-10">
    <h2 class="text-base font-semibold text-gray-900">Suscripcion</h2>
    @if($subscription)
    <div class="mt-4 border-t border-gray-100">
        <dl class="divide-y divide-gray-100">
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                <dt class="text-sm/6 font-medium text-gray-900">Plan</dt>
                <dd class="mt-1 text-sm/6 text-gray-700 sm:col-span-2 sm:mt-0">{{ ucfirst($subscription->plan) }}</dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                <dt class="text-sm/6 font-medium text-gray-900">Estado</dt>
                <dd class="mt-1 sm:col-span-2 sm:mt-0">
                    @if($subscription->status === 'active')
                    <span class="inline-flex items-center gap-x-1.5 rounded-md bg-green-100 px-2 py-1 text-xs font-medium text-green-700">
                        <svg viewBox="0 0 6 6" aria-hidden="true" class="size-1.5 fill-green-500"><circle r="3" cx="3" cy="3" /></svg>Activa
                    </span>
                    @elseif($subscription->status === 'pending')
                    <span class="inline-flex items-center gap-x-1.5 rounded-md bg-yellow-100 px-2 py-1 text-xs font-medium text-yellow-800">
                        <svg viewBox="0 0 6 6" aria-hidden="true" class="size-1.5 fill-yellow-500"><circle r="3" cx="3" cy="3" /></svg>Pendiente
                    </span>
                    @else
                    <span class="inline-flex items-center gap-x-1.5 rounded-md bg-red-100 px-2 py-1 text-xs font-medium text-red-700">
                        <svg viewBox="0 0 6 6" aria-hidden="true" class="size-1.5 fill-red-500"><circle r="3" cx="3" cy="3" /></svg>{{ ucfirst($subscription->status) }}
                    </span>
                    @endif
                </dd>
            </div>
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                <dt class="text-sm/6 font-medium text-gray-900">Monto mensual</dt>
                <dd class="mt-1 text-sm/6 text-gray-700 sm:col-span-2 sm:mt-0">${{ number_format($subscription->monthly_amount, 2) }}</dd>
            </div>
            @if($subscription->current_period_end)
            <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                <dt class="text-sm/6 font-medium text-gray-900">Vence</dt>
                <dd class="mt-1 text-sm/6 text-gray-700 sm:col-span-2 sm:mt-0">{{ $subscription->current_period_end->format('d/m/Y') }}</dd>
            </div>
            @endif
        </dl>
    </div>
    @else
    <p class="mt-4 text-sm text-gray-500">Sin suscripcion vinculada.</p>
    @endif
</div>

{{-- Users --}}
<div class="mt-10">
    <h2 class="text-base font-semibold text-gray-900">Usuarios</h2>
    <div class="mt-4 flow-root">
        <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                <table class="min-w-full divide-y divide-gray-300">
                    <thead>
                        <tr>
                            <th class="py-3.5 pr-3 pl-4 text-left text-sm font-semibold text-gray-900 sm:pl-3">Nombre</th>
                            <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Email</th>
                            <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Registrado</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white">
                        @foreach($users as $user)
                        <tr class="even:bg-gray-50">
                            <td class="py-4 pr-3 pl-4 text-sm font-medium whitespace-nowrap text-gray-900 sm:pl-3">{{ $user->name }}</td>
                            <td class="px-3 py-4 text-sm whitespace-nowrap text-gray-500">{{ $user->email }}</td>
                            <td class="px-3 py-4 text-sm whitespace-nowrap text-gray-500">{{ $user->created_at->format('d/m/Y') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Rubros --}}
<div class="mt-10">
    <h2 class="text-base font-semibold text-gray-900">Rubros activos</h2>
    @if($rubros->isEmpty())
    <p class="mt-4 text-sm text-gray-500">Sin rubros configurados.</p>
    @else
    <div class="mt-4 flow-root">
        <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                <table class="min-w-full divide-y divide-gray-300">
                    <thead>
                        <tr>
                            <th class="py-3.5 pr-3 pl-4 text-left text-sm font-semibold text-gray-900 sm:pl-3">Codigo</th>
                            <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Descripcion</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white">
                        @foreach($rubros as $rubro)
                        <tr class="even:bg-gray-50">
                            <td class="py-4 pr-3 pl-4 text-sm font-mono whitespace-nowrap text-gray-900 sm:pl-3">{{ $rubro->code }}</td>
                            <td class="px-3 py-4 text-sm text-gray-500">{{ $rubro->description }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
</div>

{{-- Recent offers --}}
<div class="mt-10">
    <h2 class="text-base font-semibold text-gray-900">Ofertas recientes</h2>
    @if($recentOffers->isEmpty())
    <p class="mt-4 text-sm text-gray-500">Sin ofertas.</p>
    @else
    <div class="mt-4 flow-root">
        <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                <table class="min-w-full divide-y divide-gray-300">
                    <thead>
                        <tr>
                            <th class="py-3.5 pr-3 pl-4 text-left text-sm font-semibold text-gray-900 sm:pl-3">ID</th>
                            <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Licitacion</th>
                            <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Estado</th>
                            <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Creada</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white">
                        @foreach($recentOffers as $offer)
                        <tr class="even:bg-gray-50">
                            <td class="py-4 pr-3 pl-4 text-sm font-medium whitespace-nowrap text-gray-900 sm:pl-3">{{ $offer->id }}</td>
                            <td class="px-3 py-4 text-sm text-gray-500">{{ Str::limit($offer->bid?->title ?? '—', 60) }}</td>
                            <td class="px-3 py-4 text-sm whitespace-nowrap text-gray-500">{{ $offer->status ?? '—' }}</td>
                            <td class="px-3 py-4 text-sm whitespace-nowrap text-gray-500">{{ $offer->created_at->format('d/m/Y') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
