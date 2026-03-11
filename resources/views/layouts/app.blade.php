<!DOCTYPE html>
<html lang="es" class="h-full bg-gray-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }} — @yield('title', 'Monitor')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/@tailwindplus/elements@1" type="module"></script>
</head>
<body class="h-full">

<div class="min-h-full">
    {{-- Top nav --}}
    <nav class="border-b border-gray-200 bg-white">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex h-16 items-center justify-between">
                <div class="flex items-center gap-x-8">
                    <span class="text-base font-bold tracking-tight text-indigo-600">SECP Monitor</span>
                    <div class="hidden md:flex md:gap-x-6">
                        <a href="{{ route('dashboard') }}"
                           class="text-sm font-semibold {{ request()->routeIs('dashboard') ? 'text-indigo-600' : 'text-gray-700 hover:text-indigo-600' }}">
                            Convocatorias
                        </a>
                        <a href="{{ route('rubros.index') }}"
                           class="text-sm font-semibold {{ request()->routeIs('rubros.*') ? 'text-indigo-600' : 'text-gray-700 hover:text-indigo-600' }}">
                            Rubros
                        </a>
                        <a href="{{ route('logs.index') }}"
                           class="text-sm font-semibold {{ request()->routeIs('logs.*') ? 'text-indigo-600' : 'text-gray-700 hover:text-indigo-600' }}">
                            Registros
                        </a>
                        <a href="{{ route('settings.index') }}"
                           class="text-sm font-semibold {{ request()->routeIs('settings.*') ? 'text-indigo-600' : 'text-gray-700 hover:text-indigo-600' }}">
                            Configuración
                        </a>
                        <a href="{{ route('users.index') }}"
                           class="text-sm font-semibold {{ request()->routeIs('users.*') ? 'text-indigo-600' : 'text-gray-700 hover:text-indigo-600' }}">
                            Usuarios
                        </a>
                    </div>
                </div>
                {{-- Poll status --}}
                <div class="flex items-center gap-x-4 text-xs text-gray-500">
                    @if($lastPolled = \App\Models\Setting::get('last_polled_at'))
                        <span>Último sondeo: {{ \Carbon\Carbon::parse($lastPolled)->diffForHumans() }}</span>
                    @else
                        <span class="text-yellow-600">Sin sondear aún</span>
                    @endif
                    <form method="POST" action="{{ route('poll.manual') }}">
                        @csrf
                        <button type="submit"
                                class="rounded-md bg-indigo-600 px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-indigo-500">
                            Sondear ahora
                        </button>
                    </form>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-xs text-gray-400 hover:text-gray-600">
                            Salir
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    {{-- Flash toast --}}
    @if(session('success') || session('error'))
    <div id="toast" aria-live="assertive" class="pointer-events-none fixed inset-0 z-50 flex items-end px-4 py-6 sm:items-start sm:p-6">
        <div class="flex w-full flex-col items-center space-y-4 sm:items-end">
            <div class="pointer-events-auto w-full max-w-sm rounded-lg bg-white shadow-lg outline-1 outline-black/5 transition-opacity duration-500">
                <div class="p-4">
                    <div class="flex items-start">
                        <div class="shrink-0">
                            @if(session('success'))
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" class="size-6 text-green-400">
                                <path d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            @else
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" class="size-6 text-red-400">
                                <path d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            @endif
                        </div>
                        <div class="ml-3 w-0 flex-1 pt-0.5">
                            <p class="text-sm font-medium text-gray-900">
                                {{ session('success') ?? session('error') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <script>
        const toast = document.getElementById('toast');
        if (toast) setTimeout(() => toast.style.opacity = '0', 5000);
    </script>

    {{-- Page content --}}
    <main>
        @yield('content')
    </main>
</div>

</body>
</html>
