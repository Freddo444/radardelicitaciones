@extends('admin.layout')
@section('title', 'Lista de novedades')

@section('content')
<div class="mb-10 flex flex-col gap-6 lg:mb-12 lg:flex-row lg:items-end lg:justify-between">
    <div class="max-w-2xl">
        <h1 class="text-2xl font-semibold tracking-tight text-zinc-900 sm:text-3xl">Lista de novedades</h1>
        <p class="mt-3 text-base leading-relaxed text-zinc-600">Usuarios que aceptaron recibir correos informativos de Radar. Puedes exportar correos para tu herramienta de envío o quitar contactos de la lista.</p>
    </div>
    <div class="flex shrink-0 flex-col gap-3 sm:flex-row sm:items-end">
        <form method="GET" class="flex flex-col gap-3 sm:flex-row sm:items-end">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Nombre o correo..."
                   class="min-w-0 rounded-lg border-0 py-2.5 pl-3 pr-3 text-sm text-zinc-900 shadow-sm ring-1 ring-inset ring-zinc-300 placeholder:text-zinc-400 focus:ring-2 focus:ring-indigo-600 sm:w-72">
            <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">Buscar</button>
        </form>
        <a href="{{ route('admin.newsletter.export', request()->only('q')) }}"
           class="rounded-lg bg-white px-4 py-2.5 text-center text-sm font-semibold text-zinc-800 shadow-sm ring-1 ring-inset ring-zinc-300 hover:bg-zinc-50">
            Descargar CSV
        </a>
    </div>
</div>

<div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-zinc-900/5">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-zinc-200">
            <thead class="bg-zinc-50/80">
                <tr>
                    <th class="py-4 pr-3 pl-5 text-left text-xs font-semibold tracking-wide text-zinc-600 uppercase">Usuario</th>
                    <th class="px-4 py-4 text-left text-xs font-semibold tracking-wide text-zinc-600 uppercase">Consentimiento</th>
                    <th class="py-4 pr-5 pl-3 text-right"><span class="sr-only">Acciones</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 bg-white">
                @forelse($subscribers as $u)
                <tr class="hover:bg-zinc-50/50">
                    <td class="py-5 pr-3 pl-5 text-sm whitespace-nowrap">
                        <a href="{{ route('admin.users.show', $u) }}" class="font-medium text-zinc-900 hover:text-indigo-600">{{ $u->name }}</a>
                        <span class="mt-0.5 block text-xs text-zinc-500">{{ $u->email }}</span>
                    </td>
                    <td class="px-4 py-5 text-sm whitespace-nowrap text-zinc-600">
                        @if($u->newsletter_consented_at)
                            {{ $u->newsletter_consented_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }}
                        @else
                            <span class="text-zinc-400">—</span>
                        @endif
                    </td>
                    <td class="py-5 pr-5 pl-3 text-right text-sm font-medium whitespace-nowrap">
                        <form method="POST" action="{{ route('admin.newsletter.update', $u) }}" class="inline" onsubmit="return confirm('¿Quitar a este usuario de la lista de novedades?');">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="subscribed" value="0">
                            <button type="submit" class="font-semibold text-red-600 hover:text-red-800">Quitar</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="3" class="py-16 text-center text-sm text-zinc-500">No hay suscriptores con los filtros actuales.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($subscribers->hasPages())
<div class="mt-8">
    {{ $subscribers->links() }}
</div>
@endif
@endsection
