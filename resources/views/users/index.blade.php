@extends('layouts.app')
@section('title', 'Usuarios')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">

    {{-- Header --}}
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-base font-semibold text-gray-900">Usuarios</h1>
            <p class="mt-1 text-sm text-gray-500">{{ $users->total() }} usuario{{ $users->total() !== 1 ? 's' : '' }} con acceso al sistema.</p>
        </div>
        <div class="mt-4 sm:mt-0">
            <button command="show-modal" commandfor="users-drawer"
                    class="inline-flex items-center gap-x-2 rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-xs hover:bg-blue-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="-ml-0.5 size-5">
                    <path d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z"/>
                </svg>
                Agregar usuario
            </button>
        </div>
    </div>

    {{-- Users table --}}
    <div class="mt-8 flow-root">
        <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                <table class="min-w-full divide-y divide-gray-300">
                    <thead>
                        <tr>
                            <th scope="col" class="py-3.5 pr-3 pl-4 text-left text-sm font-semibold text-gray-900 sm:pl-3">Nombre</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Correo</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Registrado</th>
                            <th scope="col" class="py-3.5 pr-4 pl-3 sm:pr-3"><span class="sr-only">Acciones</span></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white">
                        @foreach($users as $user)
                        <tr class="even:bg-gray-50">
                            <td class="py-4 pr-3 pl-4 text-sm font-medium whitespace-nowrap text-gray-900 sm:pl-3">
                                {{ $user->name }}
                                @if($user->id === auth()->id())
                                    <span class="ml-2 inline-flex items-center rounded-md bg-blue-50 px-1.5 py-0.5 text-xs font-medium text-blue-700">tú</span>
                                @endif
                            </td>
                            <td class="px-3 py-4 text-sm whitespace-nowrap text-gray-500">{{ $user->email }}</td>
                            <td class="px-3 py-4 text-sm whitespace-nowrap text-gray-500">{{ $user->created_at->format('d/m/Y') }}</td>
                            <td class="py-4 pr-4 pl-3 text-right text-sm whitespace-nowrap sm:pr-3">
                                <div class="flex items-center justify-end gap-x-1">
                                    {{-- Change password --}}
                                    <button type="button"
                                            onclick="openPasswordDrawer({{ $user->id }}, '{{ $user->email }}')"
                                            command="show-modal" commandfor="password-drawer"
                                            class="rounded-md p-1 text-gray-400 hover:text-blue-600">
                                        <span class="sr-only">Cambiar contraseña</span>
                                        <svg viewBox="0 0 20 20" fill="currentColor" class="size-4">
                                            <path fill-rule="evenodd" d="M8 7a5 5 0 1 1 3.61 4.804l-1.903 1.903A1 1 0 0 1 9 14H8v1a1 1 0 0 1-1 1H6v1a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1v-2a1 1 0 0 1 .293-.707L7.196 10.39A5.002 5.002 0 0 1 8 7Zm5-3a.75.75 0 0 0 0 1.5A1.5 1.5 0 0 1 14.5 7 .75.75 0 0 0 16 7a3 3 0 0 0-3-3Z" clip-rule="evenodd"/>
                                        </svg>
                                    </button>
                                    {{-- Delete --}}
                                    @if($user->id !== auth()->id())
                                    <form method="POST" action="{{ route('users.destroy', $user) }}"
                                          onsubmit="return confirm('¿Eliminar al usuario {{ $user->email }}?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="rounded-md p-1 text-gray-400 hover:text-red-500">
                                            <span class="sr-only">Eliminar</span>
                                            <svg viewBox="0 0 20 20" fill="currentColor" class="size-4">
                                                <path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 0 0 6 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 1 0 .23 1.482l.149-.022.841 10.518A2.75 2.75 0 0 0 7.596 19h4.807a2.75 2.75 0 0 0 2.742-2.53l.841-10.52.149.023a.75.75 0 0 0 .23-1.482A41.03 41.03 0 0 0 14 4.193V3.75A2.75 2.75 0 0 0 11.25 1h-2.5ZM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4ZM8.58 7.72a.75.75 0 0 0-1.5.06l.3 7.5a.75.75 0 1 0 1.5-.06l-.3-7.5Zm4.34.06a.75.75 0 1 0-1.5-.06l-.3 7.5a.75.75 0 1 0 1.5.06l.3-7.5Z" clip-rule="evenodd"/>
                                            </svg>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Pagination --}}
    <div class="mt-6">
        {{ $users->links('components.pagination') }}
    </div>
</div>

{{-- Add user drawer --}}
<el-dialog>
    <dialog id="users-drawer" aria-labelledby="users-drawer-title"
            class="fixed inset-0 size-auto max-h-none max-w-none overflow-hidden bg-transparent backdrop:bg-transparent">
        <div tabindex="0" class="absolute inset-0 pl-10 focus:outline-none sm:pl-16">
            <el-dialog-panel class="ml-auto block size-full max-w-md transform transition duration-500 ease-in-out data-closed:translate-x-full sm:duration-700">
                <div class="relative flex h-full flex-col divide-y divide-gray-200 bg-white shadow-xl">

                    <div class="flex items-start justify-between px-4 py-6 sm:px-6">
                        <h2 id="users-drawer-title" class="text-base font-semibold text-gray-900">Agregar usuario</h2>
                        <button type="button" command="close" commandfor="users-drawer"
                                class="relative ml-3 rounded-md text-gray-400 hover:text-gray-500 focus-visible:outline-2 focus-visible:outline-blue-600">
                            <span class="sr-only">Cerrar</span>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="size-6">
                                <path d="M6 18 18 6M6 6l12 12" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                    </div>

                    <form id="user-form" method="POST" action="{{ route('users.store') }}"
                          class="flex min-h-0 flex-1 flex-col">
                        @csrf
                        <div class="flex-1 space-y-5 overflow-y-auto px-4 py-6 sm:px-6">
                            <div>
                                <label for="user-name" class="block text-sm font-medium text-gray-900">Nombre completo</label>
                                <input type="text" id="user-name" name="name" required
                                       placeholder="Juan Pérez"
                                       class="mt-1.5 w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600"/>
                            </div>
                            <div>
                                <label for="user-email" class="block text-sm font-medium text-gray-900">Correo electrónico</label>
                                <input type="email" id="user-email" name="email" required
                                       placeholder="juan@empresa.com"
                                       class="mt-1.5 w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600"/>
                            </div>
                            <div>
                                <label for="user-password" class="block text-sm font-medium text-gray-900">Contraseña</label>
                                <input type="password" id="user-password" name="password" required
                                       placeholder="Mínimo 8 caracteres"
                                       class="mt-1.5 w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600"/>
                            </div>
                        </div>

                        <div class="flex shrink-0 justify-end gap-x-3 px-4 py-4">
                            <button type="button" command="close" commandfor="users-drawer"
                                    class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-xs inset-ring inset-ring-gray-300 hover:inset-ring-gray-400">
                                Cancelar
                            </button>
                            <button type="submit"
                                    class="inline-flex justify-center rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-xs hover:bg-blue-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                                Crear usuario
                            </button>
                        </div>
                    </form>

                </div>
            </el-dialog-panel>
        </div>
    </dialog>
</el-dialog>

{{-- Change password drawer --}}
<el-dialog>
    <dialog id="password-drawer" aria-labelledby="password-drawer-title"
            class="fixed inset-0 size-auto max-h-none max-w-none overflow-hidden bg-transparent backdrop:bg-transparent">
        <div tabindex="0" class="absolute inset-0 pl-10 focus:outline-none sm:pl-16">
            <el-dialog-panel class="ml-auto block size-full max-w-md transform transition duration-500 ease-in-out data-closed:translate-x-full sm:duration-700">
                <div class="relative flex h-full flex-col divide-y divide-gray-200 bg-white shadow-xl">

                    <div class="flex items-start justify-between px-4 py-6 sm:px-6">
                        <div>
                            <h2 id="password-drawer-title" class="text-base font-semibold text-gray-900">Cambiar contraseña</h2>
                            <p id="password-drawer-email" class="mt-1 text-sm text-gray-500"></p>
                        </div>
                        <button type="button" command="close" commandfor="password-drawer"
                                class="relative ml-3 rounded-md text-gray-400 hover:text-gray-500 focus-visible:outline-2 focus-visible:outline-blue-600">
                            <span class="sr-only">Cerrar</span>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="size-6">
                                <path d="M6 18 18 6M6 6l12 12" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </button>
                    </div>

                    <form id="password-form" method="POST" action="" class="flex min-h-0 flex-1 flex-col">
                        @csrf @method('PATCH')
                        <div class="flex-1 space-y-5 overflow-y-auto px-4 py-6 sm:px-6">
                            <div>
                                <label for="new-password" class="block text-sm font-medium text-gray-900">Nueva contraseña</label>
                                <input type="password" id="new-password" name="password" required
                                       placeholder="Mínimo 8 caracteres"
                                       class="mt-1.5 w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600"/>
                            </div>
                            <div>
                                <label for="new-password-confirm" class="block text-sm font-medium text-gray-900">Confirmar contraseña</label>
                                <input type="password" id="new-password-confirm" name="password_confirmation" required
                                       placeholder="Repite la contraseña"
                                       class="mt-1.5 w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600"/>
                            </div>
                        </div>

                        <div class="flex shrink-0 justify-end gap-x-3 px-4 py-4">
                            <button type="button" command="close" commandfor="password-drawer"
                                    class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-xs inset-ring inset-ring-gray-300 hover:inset-ring-gray-400">
                                Cancelar
                            </button>
                            <button type="submit"
                                    class="inline-flex justify-center rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-xs hover:bg-blue-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                                Guardar
                            </button>
                        </div>
                    </form>

                </div>
            </el-dialog-panel>
        </div>
    </dialog>
</el-dialog>

<script>
function openPasswordDrawer(userId, userEmail) {
    document.getElementById('password-drawer-email').textContent = userEmail;
    document.getElementById('password-form').action = '/usuarios/' + userId + '/password';
    document.getElementById('new-password').value = '';
    document.getElementById('new-password-confirm').value = '';
}
</script>

@endsection
