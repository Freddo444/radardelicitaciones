@extends('admin.layout')
@section('title', $company->razon_social)

@section('content')
<div class="mb-8">
    <a href="{{ route('admin.companies.index') }}" class="text-sm font-medium text-zinc-500 hover:text-zinc-800">&larr; Volver a empresas</a>
</div>

<div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
    <div class="max-w-3xl">
        <h1 class="text-2xl font-semibold tracking-tight text-zinc-900 sm:text-3xl">{{ $company->razon_social }}</h1>
        <p class="mt-3 text-base leading-relaxed text-zinc-600">RNC: {{ $company->rnc }} &middot; Creada {{ $company->created_at->format('d/m/Y') }}</p>
    </div>
    <form method="POST" action="{{ route('admin.companies.impersonate', $company) }}" class="shrink-0">
        @csrf
        <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">Impersonar</button>
    </form>
</div>

{{-- Stats --}}
<dl class="mt-12 grid grid-cols-1 gap-6 sm:grid-cols-3 lg:gap-8">
    <div class="overflow-hidden rounded-xl bg-white px-6 py-7 shadow-sm ring-1 ring-zinc-900/5">
        <dt class="text-xs font-semibold tracking-wide text-zinc-500 uppercase">Usuarios</dt>
        <dd class="mt-3 text-3xl font-semibold tracking-tight text-zinc-900">{{ $company->users_count }}</dd>
    </div>
    <div class="overflow-hidden rounded-xl bg-white px-6 py-7 shadow-sm ring-1 ring-zinc-900/5">
        <dt class="text-xs font-semibold tracking-wide text-zinc-500 uppercase">Rubros activos</dt>
        <dd class="mt-3 text-3xl font-semibold tracking-tight text-zinc-900">{{ $company->rubros_count }}</dd>
    </div>
    <div class="overflow-hidden rounded-xl bg-white px-6 py-7 shadow-sm ring-1 ring-zinc-900/5">
        <dt class="text-xs font-semibold tracking-wide text-zinc-500 uppercase">Ofertas</dt>
        <dd class="mt-3 text-3xl font-semibold tracking-tight text-zinc-900">{{ $company->offers_count }}</dd>
    </div>
</dl>

{{-- Subscription --}}
<div class="mt-14 lg:mt-16">
    <h2 class="text-lg font-semibold tracking-tight text-zinc-900">Suscripcion</h2>
    @if($subscription)
    <div class="mt-6 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-zinc-900/5">
        <dl class="divide-y divide-zinc-100">
            <div class="px-6 py-6 sm:grid sm:grid-cols-3 sm:gap-6 lg:px-8">
                <dt class="text-sm font-semibold text-zinc-900">Plan</dt>
                <dd class="mt-2 text-sm leading-relaxed text-zinc-600 sm:col-span-2 sm:mt-0">{{ ucfirst($subscription->plan) }}</dd>
            </div>
            <div class="px-6 py-6 sm:grid sm:grid-cols-3 sm:gap-6 lg:px-8">
                <dt class="text-sm font-semibold text-zinc-900">Estado</dt>
                <dd class="mt-2 sm:col-span-2 sm:mt-0">
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
            <div class="px-6 py-6 sm:grid sm:grid-cols-3 sm:gap-6 lg:px-8">
                <dt class="text-sm font-semibold text-zinc-900">Monto mensual</dt>
                <dd class="mt-2 text-sm leading-relaxed text-zinc-600 sm:col-span-2 sm:mt-0">${{ number_format($subscription->monthly_amount, 2) }}</dd>
            </div>
            @if($subscription->current_period_end)
            <div class="px-6 py-6 sm:grid sm:grid-cols-3 sm:gap-6 lg:px-8">
                <dt class="text-sm font-semibold text-zinc-900">Vence</dt>
                <dd class="mt-2 text-sm leading-relaxed text-zinc-600 sm:col-span-2 sm:mt-0">{{ $subscription->current_period_end->format('d/m/Y') }}</dd>
            </div>
            @endif
        </dl>
    </div>
    @else
    <p class="mt-4 rounded-xl border border-dashed border-zinc-200 bg-white/80 px-6 py-8 text-sm text-zinc-600">Sin suscripcion vinculada.</p>
    @endif
</div>

{{-- Users --}}
<div class="mt-14 lg:mt-16">
    <h2 class="text-lg font-semibold tracking-tight text-zinc-900">Usuarios</h2>
    <div class="mt-6 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-zinc-900/5">
        <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200">
                    <thead class="bg-zinc-50/80">
                        <tr>
                            <th class="py-4 pr-3 pl-5 text-left text-xs font-semibold tracking-wide text-zinc-600 uppercase">Nombre</th>
                            <th class="px-4 py-4 text-left text-xs font-semibold tracking-wide text-zinc-600 uppercase">Email</th>
                            <th class="px-4 py-4 text-left text-xs font-semibold tracking-wide text-zinc-600 uppercase">Registrado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 bg-white">
                        @foreach($users as $user)
                        <tr class="hover:bg-zinc-50/50">
                            <td class="py-5 pr-3 pl-5 text-sm font-medium whitespace-nowrap text-zinc-900">{{ $user->name }}</td>
                            <td class="px-4 py-5 text-sm whitespace-nowrap text-zinc-600">{{ $user->email }}</td>
                            <td class="px-4 py-5 text-sm whitespace-nowrap text-zinc-600">{{ $user->created_at->format('d/m/Y') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
        </div>
    </div>
</div>

{{-- Rubros --}}
<div class="mt-14 lg:mt-16">
    <h2 class="text-lg font-semibold tracking-tight text-zinc-900">Rubros activos</h2>
    @if($rubros->isEmpty())
    <p class="mt-4 rounded-xl border border-dashed border-zinc-200 bg-white/80 px-6 py-8 text-sm text-zinc-600">Sin rubros configurados.</p>
    @else
    <div class="mt-6 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-zinc-900/5">
        <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200">
                    <thead class="bg-zinc-50/80">
                        <tr>
                            <th class="py-4 pr-3 pl-5 text-left text-xs font-semibold tracking-wide text-zinc-600 uppercase">Codigo</th>
                            <th class="px-4 py-4 text-left text-xs font-semibold tracking-wide text-zinc-600 uppercase">Descripcion</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 bg-white">
                        @foreach($rubros as $rubro)
                        <tr class="hover:bg-zinc-50/50">
                            <td class="py-5 pr-3 pl-5 text-sm font-mono whitespace-nowrap text-zinc-900">{{ $rubro->code }}</td>
                            <td class="px-4 py-5 text-sm leading-relaxed text-zinc-600">{{ $rubro->description }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
        </div>
    </div>
    @endif
</div>

{{-- Recent offers --}}
<div class="mt-14 lg:mt-16 pb-4">
    <h2 class="text-lg font-semibold tracking-tight text-zinc-900">Ofertas recientes</h2>
    @if($recentOffers->isEmpty())
    <p class="mt-4 rounded-xl border border-dashed border-zinc-200 bg-white/80 px-6 py-8 text-sm text-zinc-600">Sin ofertas.</p>
    @else
    <div class="mt-6 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-zinc-900/5">
        <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200">
                    <thead class="bg-zinc-50/80">
                        <tr>
                            <th class="py-4 pr-3 pl-5 text-left text-xs font-semibold tracking-wide text-zinc-600 uppercase">ID</th>
                            <th class="px-4 py-4 text-left text-xs font-semibold tracking-wide text-zinc-600 uppercase">Licitacion</th>
                            <th class="px-4 py-4 text-left text-xs font-semibold tracking-wide text-zinc-600 uppercase">Estado</th>
                            <th class="px-4 py-4 text-left text-xs font-semibold tracking-wide text-zinc-600 uppercase">Creada</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 bg-white">
                        @foreach($recentOffers as $offer)
                        <tr class="hover:bg-zinc-50/50">
                            <td class="py-5 pr-3 pl-5 text-sm font-medium whitespace-nowrap text-zinc-900">{{ $offer->id }}</td>
                            <td class="px-4 py-5 text-sm leading-relaxed text-zinc-600">{{ Str::limit($offer->bid?->title ?? '—', 60) }}</td>
                            <td class="px-4 py-5 text-sm whitespace-nowrap text-zinc-600">{{ $offer->status ?? '—' }}</td>
                            <td class="px-4 py-5 text-sm whitespace-nowrap text-zinc-600">{{ $offer->created_at->format('d/m/Y') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
        </div>
    </div>
    @endif
</div>
@endsection
