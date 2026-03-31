@extends('layouts.app')
@section('title', 'Seleccionar empresa')

@section('content')
<div class="mx-auto max-w-2xl py-10">
    <h1 class="text-2xl font-bold text-gray-900">Seleccionar empresa</h1>
    <p class="mt-1 text-sm text-gray-500">Elige la empresa con la que deseas trabajar.</p>

    <div class="mt-6 space-y-3">
        @foreach($companies as $company)
            <form method="POST" action="{{ route('companies.switch', $company) }}">
                @csrf
                <button type="submit"
                    class="w-full flex items-center justify-between rounded-lg border px-5 py-4 text-left hover:bg-gray-50 transition
                        {{ session('current_company_id') == $company->id ? 'border-blue-500 bg-blue-50' : 'border-gray-200' }}">
                    <div>
                        <p class="font-semibold text-gray-900">{{ $company->razon_social }}</p>
                        <p class="text-sm text-gray-500">RNC: {{ $company->rnc ?? '—' }}</p>
                    </div>
                    <div class="text-right text-xs text-gray-400">
                        {{ $company->users_count }} {{ $company->users_count === 1 ? 'usuario' : 'usuarios' }}
                    </div>
                </button>
            </form>
        @endforeach
    </div>

    <div class="mt-6">
        <a href="{{ route('companies.create') }}"
           class="inline-flex items-center gap-1 text-sm font-medium text-blue-600 hover:text-blue-800">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Agregar empresa
        </a>
    </div>
</div>
@endsection
