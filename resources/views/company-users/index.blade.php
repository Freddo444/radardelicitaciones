@extends('layouts.app')
@section('title', 'Usuarios')

@section('content')
<div class="mx-auto max-w-4xl">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Usuarios de {{ $company->razon_social }}</h1>
    </div>

    {{-- Current members --}}
    <div class="rounded-lg bg-white shadow-sm mb-8">
        <div class="border-b border-gray-200 px-4 py-3">
            <h2 class="text-sm font-semibold text-gray-900">Miembros ({{ $users->count() }})</h2>
        </div>
        <ul class="divide-y divide-gray-100">
            @foreach($users as $user)
            <li class="flex items-center justify-between px-4 py-3">
                <div>
                    <p class="text-sm font-medium text-gray-900">{{ $user->name }}</p>
                    <p class="text-xs text-gray-500">{{ $user->email }}</p>
                </div>
                <div class="flex items-center gap-3">
                    @if($subscription && $user->id === $subscription->user_id)
                        <span class="inline-flex rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700">Propietario</span>
                    @elseif($isOwner)
                        <form method="POST" action="{{ route('company-users.remove', $user->id) }}"
                              onsubmit="return confirm('Remover a {{ $user->name }} de la empresa?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-xs text-red-600 hover:text-red-800">Remover</button>
                        </form>
                    @endif
                </div>
            </li>
            @endforeach
        </ul>
    </div>

    {{-- Invite form (owner only) --}}
    @if($isOwner)
    <div class="rounded-lg bg-white shadow-sm mb-8">
        <div class="border-b border-gray-200 px-4 py-3">
            <h2 class="text-sm font-semibold text-gray-900">Invitar usuario</h2>
        </div>
        <div class="p-4">
            @if($canInvite)
            <form method="POST" action="{{ route('company-users.invite') }}" class="flex gap-3">
                @csrf
                <input type="email" name="email" required placeholder="correo@ejemplo.com"
                       class="flex-1 rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500">
                <button type="submit" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                    Enviar invitacion
                </button>
            </form>
            @if($errors->has('email'))
                <p class="mt-2 text-sm text-red-600">{{ $errors->first('email') }}</p>
            @endif
            @else
            <p class="text-sm text-gray-500">
                Limite de usuarios alcanzado ({{ $subscription->max_users }}).
                <a href="{{ route('settings.index') }}#suscripcion" class="text-blue-600 hover:underline">Actualiza tu plan</a> para agregar mas.
            </p>
            @endif
        </div>
    </div>
    @endif

    {{-- Pending invitations --}}
    @if($isOwner && $pendingInvitations->isNotEmpty())
    <div class="rounded-lg bg-white shadow-sm">
        <div class="border-b border-gray-200 px-4 py-3">
            <h2 class="text-sm font-semibold text-gray-900">Invitaciones pendientes</h2>
        </div>
        <ul class="divide-y divide-gray-100">
            @foreach($pendingInvitations as $inv)
            <li class="flex items-center justify-between px-4 py-3">
                <div>
                    <p class="text-sm text-gray-900">{{ $inv->email }}</p>
                    <p class="text-xs text-gray-500">Vence {{ $inv->expires_at->format('d/m/Y') }}</p>
                </div>
                <form method="POST" action="{{ route('company-users.cancel-invitation', $inv) }}">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-xs text-red-600 hover:text-red-800">Cancelar</button>
                </form>
            </li>
            @endforeach
        </ul>
    </div>
    @endif

    {{-- Usage info --}}
    @if($subscription)
    <div class="mt-6 text-xs text-gray-400">
        Usuarios en la suscripcion: {{ $subscription->userCount() }} / {{ $subscription->max_users }}
    </div>
    @endif
</div>
@endsection
