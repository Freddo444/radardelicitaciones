@extends('admin.layout')
@section('title', 'Empresas')

@section('content')
<div class="mb-10 flex flex-col gap-6 lg:mb-12 lg:flex-row lg:items-end lg:justify-between">
    <div class="max-w-2xl">
        <h1 class="text-2xl font-semibold tracking-tight text-zinc-900 sm:text-3xl">Empresas</h1>
        <p class="mt-3 text-base leading-relaxed text-zinc-600">Todas las empresas registradas en la plataforma.</p>
    </div>
    <div class="shrink-0">
        <form method="GET" class="flex flex-col gap-3 sm:flex-row sm:items-stretch">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Buscar por razon social o RNC..."
                   class="min-w-0 rounded-lg border-0 py-2.5 pl-3 pr-3 text-sm text-zinc-900 shadow-sm ring-1 ring-inset ring-zinc-300 placeholder:text-zinc-400 focus:ring-2 focus:ring-indigo-600 sm:w-80">
            <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">Buscar</button>
        </form>
    </div>
</div>

<div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-zinc-900/5">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-zinc-200">
                <thead class="bg-zinc-50/80">
                    <tr>
                        <th class="py-4 pr-3 pl-5 text-left text-xs font-semibold tracking-wide text-zinc-600 uppercase">Empresa</th>
                        <th class="px-4 py-4 text-left text-xs font-semibold tracking-wide text-zinc-600 uppercase">RNC</th>
                        <th class="px-4 py-4 text-center text-xs font-semibold tracking-wide text-zinc-600 uppercase">Usuarios</th>
                        <th class="px-4 py-4 text-center text-xs font-semibold tracking-wide text-zinc-600 uppercase">Rubros</th>
                        <th class="px-4 py-4 text-center text-xs font-semibold tracking-wide text-zinc-600 uppercase">Ofertas</th>
                        <th class="px-4 py-4 text-left text-xs font-semibold tracking-wide text-zinc-600 uppercase">Creada</th>
                        <th class="py-4 pr-5 pl-3 text-right"><span class="sr-only">Acciones</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 bg-white">
                    @forelse($companies as $company)
                    <tr class="hover:bg-zinc-50/50">
                        <td class="py-5 pr-3 pl-5 text-sm whitespace-nowrap">
                            <a href="{{ route('admin.companies.show', $company) }}" class="font-medium text-zinc-900 hover:text-indigo-600">{{ $company->razon_social }}</a>
                        </td>
                        <td class="px-4 py-5 text-sm font-mono whitespace-nowrap text-zinc-600">{{ $company->rnc }}</td>
                        <td class="px-4 py-5 text-sm whitespace-nowrap text-center text-zinc-600">{{ $company->users_count }}</td>
                        <td class="px-4 py-5 text-sm whitespace-nowrap text-center text-zinc-600">{{ $company->rubros_count }}</td>
                        <td class="px-4 py-5 text-sm whitespace-nowrap text-center text-zinc-600">{{ $company->offers_count }}</td>
                        <td class="px-4 py-5 text-sm whitespace-nowrap text-zinc-600">{{ $company->created_at->format('d/m/Y') }}</td>
                        <td class="py-5 pr-5 pl-3 text-right text-sm font-medium whitespace-nowrap">
                            <form method="POST" action="{{ route('admin.companies.impersonate', $company) }}" class="inline">
                                @csrf
                                <button type="submit" class="font-semibold text-indigo-600 hover:text-indigo-800">Impersonar</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-16 text-center">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true" class="mx-auto size-12 text-zinc-300">
                                <path d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <p class="mt-3 text-sm text-zinc-500">No se encontraron empresas.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

<div class="mt-8">{{ $companies->links() }}</div>
@endsection
