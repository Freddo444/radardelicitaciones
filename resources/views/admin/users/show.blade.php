@extends('admin.layout')
@section('title', $user->name)

@section('content')
<div class="mb-8">
    <a href="{{ route('admin.users.index', request()->only('tipo', 'q')) }}" class="text-sm font-medium text-zinc-500 hover:text-zinc-800">&larr; Usuarios</a>
</div>

<div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
    <div class="max-w-2xl">
        <h1 class="text-2xl font-semibold tracking-tight text-zinc-900 sm:text-3xl">{{ $user->name }}</h1>
        <p class="mt-2 text-base text-zinc-600">{{ $user->email }}</p>
        <p class="mt-1 text-sm text-zinc-500">Registrado {{ $user->created_at->format('d/m/Y H:i') }}</p>
        <p class="mt-1 text-sm text-zinc-500">
            Último acceso:
            @if($user->last_sign_in_at)
                {{ $user->last_sign_in_at->format('d/m/Y H:i') }}
            @else
                <span class="text-zinc-400">—</span>
            @endif
        </p>
    </div>
    <form method="POST" action="{{ route('admin.users.impersonate', $user) }}" class="shrink-0">
        @csrf
        <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">Impersonar</button>
    </form>
</div>

<div class="mt-12">
    <h2 class="text-lg font-semibold tracking-tight text-zinc-900">Suscripcion</h2>
    @if($user->subscription)
    <div class="mt-6 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-zinc-900/5">
        <dl class="divide-y divide-zinc-100">
            <div class="px-6 py-5 sm:grid sm:grid-cols-3 sm:gap-6 lg:px-8">
                <dt class="text-sm font-semibold text-zinc-900">Plan</dt>
                <dd class="mt-1 text-sm text-zinc-600 sm:col-span-2 sm:mt-0">{{ ucfirst($user->subscription->plan) }}</dd>
            </div>
            <div class="px-6 py-5 sm:grid sm:grid-cols-3 sm:gap-6 lg:px-8">
                <dt class="text-sm font-semibold text-zinc-900">Estado</dt>
                <dd class="mt-1 text-sm text-zinc-600 sm:col-span-2 sm:mt-0">{{ ucfirst($user->subscription->status) }}</dd>
            </div>
            <div class="px-6 py-5 sm:grid sm:grid-cols-3 sm:gap-6 lg:px-8">
                <dt class="text-sm font-semibold text-zinc-900">Monto mensual</dt>
                <dd class="mt-1 text-sm text-zinc-600 sm:col-span-2 sm:mt-0">${{ number_format($user->subscription->monthly_amount, 2) }}</dd>
            </div>
            @if($user->subscription->trial_ends_at)
            <div class="px-6 py-5 sm:grid sm:grid-cols-3 sm:gap-6 lg:px-8">
                <dt class="text-sm font-semibold text-zinc-900">Prueba hasta</dt>
                <dd class="mt-1 text-sm text-zinc-600 sm:col-span-2 sm:mt-0">{{ $user->subscription->trial_ends_at->format('d/m/Y') }}</dd>
            </div>
            @endif
        </dl>
    </div>
    @else
    <p class="mt-4 text-sm text-zinc-500">Sin suscripcion.</p>
    @endif
</div>

<div class="mt-14 lg:mt-16">
    <h2 class="text-lg font-semibold tracking-tight text-zinc-900">Empresas</h2>
    @if($allCompanies->isEmpty())
    <p class="mt-4 rounded-xl border border-dashed border-zinc-200 bg-white/80 px-6 py-8 text-sm text-zinc-600">Sin empresas vinculadas. La impersonacion requiere al menos una empresa (actual, miembro o como propietario).</p>
    @else
    <div class="mt-6 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-zinc-900/5">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200">
                <thead class="bg-zinc-50/80">
                    <tr>
                        <th class="py-4 pr-3 pl-5 text-left text-xs font-semibold tracking-wide text-zinc-600 uppercase">Razon social</th>
                        <th class="px-4 py-4 text-left text-xs font-semibold tracking-wide text-zinc-600 uppercase">RNC</th>
                        <th class="py-4 pr-5 pl-3 text-right text-xs font-semibold tracking-wide text-zinc-600 uppercase">Admin</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 bg-white">
                    @foreach($allCompanies as $c)
                    <tr class="hover:bg-zinc-50/50">
                        <td class="py-4 pr-3 pl-5 text-sm">
                            <a href="{{ route('admin.companies.show', $c) }}" class="font-medium text-zinc-900 hover:text-indigo-600">{{ $c->razon_social }}</a>
                        </td>
                        <td class="px-4 py-4 text-sm font-mono text-zinc-600">{{ $c->rnc }}</td>
                        <td class="py-4 pr-5 pl-3 text-right text-sm">
                            <a href="{{ route('admin.companies.show', $c) }}" class="font-semibold text-indigo-600 hover:text-indigo-800">Ver</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
@endsection
