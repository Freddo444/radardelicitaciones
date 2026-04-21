@extends('admin.layout')
@section('title', 'Usuarios')

@section('content')
<div class="mb-10 flex flex-col gap-6 lg:mb-12 lg:flex-row lg:items-end lg:justify-between">
    <div class="max-w-2xl">
        <h1 class="text-2xl font-semibold tracking-tight text-zinc-900 sm:text-3xl">Usuarios</h1>
        <p class="mt-3 text-base leading-relaxed text-zinc-600">Titulares de suscripcion (pagadas o pendientes) y cuentas en periodo de prueba. Impersona la empresa en contexto de ese usuario.</p>
    </div>
    <div class="shrink-0">
        <form method="GET" class="flex flex-col gap-3 sm:flex-row sm:items-end sm:flex-wrap">
            <input type="hidden" name="tipo" value="{{ $tipo }}">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Nombre o correo..."
                   class="min-w-0 rounded-lg border-0 py-2.5 pl-3 pr-3 text-sm text-zinc-900 shadow-sm ring-1 ring-inset ring-zinc-300 placeholder:text-zinc-400 focus:ring-2 focus:ring-indigo-600 sm:w-72">
            <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">Buscar</button>
        </form>
    </div>
</div>

<div class="mb-8 flex flex-wrap gap-2">
    <a href="{{ route('admin.users.index', array_merge(request()->except('page'), ['tipo' => 'titulares'])) }}"
       class="rounded-lg px-4 py-2 text-sm font-semibold shadow-sm ring-1 ring-inset {{ $tipo === 'titulares' ? 'bg-slate-900 text-white ring-slate-900' : 'bg-white text-zinc-700 ring-zinc-300 hover:bg-zinc-50' }}">
        Titulares
    </a>
    <a href="{{ route('admin.users.index', array_merge(request()->except('page'), ['tipo' => 'prueba'])) }}"
       class="rounded-lg px-4 py-2 text-sm font-semibold shadow-sm ring-1 ring-inset {{ $tipo === 'prueba' ? 'bg-slate-900 text-white ring-slate-900' : 'bg-white text-zinc-700 ring-zinc-300 hover:bg-zinc-50' }}">
        En prueba
    </a>
</div>

<div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-zinc-900/5">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-zinc-200">
                <thead class="bg-zinc-50/80">
                    <tr>
                        <th class="py-4 pr-3 pl-5 text-left text-xs font-semibold tracking-wide text-zinc-600 uppercase">Usuario</th>
                        <th class="px-4 py-4 text-left text-xs font-semibold tracking-wide text-zinc-600 uppercase">Suscripcion</th>
                        <th class="px-4 py-4 text-center text-xs font-semibold tracking-wide text-zinc-600 uppercase">Empresas</th>
                        <th class="px-4 py-4 text-left text-xs font-semibold tracking-wide text-zinc-600 uppercase">Registro</th>
                        <th class="px-4 py-4 text-left text-xs font-semibold tracking-wide text-zinc-600 uppercase">Último acceso</th>
                        <th class="py-4 pr-5 pl-3 text-right"><span class="sr-only">Acciones</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 bg-white">
                    @forelse($users as $u)
                    <tr class="hover:bg-zinc-50/50">
                        <td class="py-5 pr-3 pl-5 text-sm whitespace-nowrap">
                            <a href="{{ route('admin.users.show', $u) }}" class="font-medium text-zinc-900 hover:text-indigo-600">{{ $u->name }}</a>
                            <span class="mt-0.5 block text-xs text-zinc-500">{{ $u->email }}</span>
                        </td>
                        <td class="px-4 py-5 text-sm whitespace-nowrap text-zinc-600">
                            @if($u->subscription)
                                <span class="font-medium text-zinc-800">{{ ucfirst($u->subscription->plan) }}</span>
                                <span class="mt-0.5 block text-xs text-zinc-500">{{ ucfirst($u->subscription->status) }}</span>
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-5 text-sm whitespace-nowrap text-center text-zinc-600">{{ $u->companies_count }}</td>
                        <td class="px-4 py-5 text-sm whitespace-nowrap text-zinc-600">{{ $u->created_at->format('d/m/Y') }}</td>
                        <td class="px-4 py-5 text-sm whitespace-nowrap text-zinc-600">
                            @if($u->last_sign_in_at)
                                {{ $u->last_sign_in_at->format('d/m/Y H:i') }}
                            @else
                                <span class="text-zinc-400">—</span>
                            @endif
                        </td>
                        <td class="py-5 pr-5 pl-3 text-right text-sm font-medium whitespace-nowrap">
                            <form method="POST" action="{{ route('admin.users.impersonate', $u) }}" class="inline">
                                @csrf
                                <button type="submit" class="font-semibold text-indigo-600 hover:text-indigo-800">Impersonar</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-16 text-center text-sm text-zinc-500">No se encontraron usuarios.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

<div class="mt-8">{{ $users->links() }}</div>
@endsection
