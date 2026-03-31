@extends('admin.layout')
@section('title', 'Empresas')

@section('content')
<div class="sm:flex sm:items-center">
    <div class="sm:flex-auto">
        <h1 class="text-base font-semibold text-gray-900">Empresas</h1>
        <p class="mt-2 text-sm text-gray-700">Todas las empresas registradas en la plataforma.</p>
    </div>
    <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none">
        <form method="GET" class="flex gap-2">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Buscar por razon social o RNC..."
                   class="rounded-md border-0 py-1.5 text-sm text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-indigo-600 sm:w-72">
            <button type="submit" class="rounded-md bg-indigo-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-xs hover:bg-indigo-500">Buscar</button>
        </form>
    </div>
</div>

<div class="mt-8 flow-root">
    <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
        <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
            <table class="min-w-full divide-y divide-gray-300">
                <thead>
                    <tr>
                        <th class="py-3.5 pr-3 pl-4 text-left text-sm font-semibold text-gray-900 sm:pl-3">Empresa</th>
                        <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">RNC</th>
                        <th class="px-3 py-3.5 text-center text-sm font-semibold text-gray-900">Usuarios</th>
                        <th class="px-3 py-3.5 text-center text-sm font-semibold text-gray-900">Rubros</th>
                        <th class="px-3 py-3.5 text-center text-sm font-semibold text-gray-900">Ofertas</th>
                        <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Creada</th>
                        <th class="py-3.5 pr-4 pl-3 sm:pr-3"><span class="sr-only">Acciones</span></th>
                    </tr>
                </thead>
                <tbody class="bg-white">
                    @forelse($companies as $company)
                    <tr class="even:bg-gray-50">
                        <td class="py-4 pr-3 pl-4 text-sm whitespace-nowrap sm:pl-3">
                            <a href="{{ route('admin.companies.show', $company) }}" class="font-medium text-gray-900 hover:text-indigo-600">{{ $company->razon_social }}</a>
                        </td>
                        <td class="px-3 py-4 text-sm whitespace-nowrap text-gray-500 font-mono">{{ $company->rnc }}</td>
                        <td class="px-3 py-4 text-sm whitespace-nowrap text-gray-500 text-center">{{ $company->users_count }}</td>
                        <td class="px-3 py-4 text-sm whitespace-nowrap text-gray-500 text-center">{{ $company->rubros_count }}</td>
                        <td class="px-3 py-4 text-sm whitespace-nowrap text-gray-500 text-center">{{ $company->offers_count }}</td>
                        <td class="px-3 py-4 text-sm whitespace-nowrap text-gray-500">{{ $company->created_at->format('d/m/Y') }}</td>
                        <td class="py-4 pr-4 pl-3 text-right text-sm font-medium whitespace-nowrap sm:pr-3">
                            <form method="POST" action="{{ route('admin.companies.impersonate', $company) }}" class="inline">
                                @csrf
                                <button type="submit" class="text-indigo-600 hover:text-indigo-900">Impersonar</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-10 text-center">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true" class="mx-auto size-12 text-gray-300">
                                <path d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <p class="mt-2 text-sm text-gray-500">No se encontraron empresas.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-6">{{ $companies->links() }}</div>
@endsection
