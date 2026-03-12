<!DOCTYPE html>
<html lang="es" class="h-full bg-white">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }} — @yield('title', 'Monitor')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/@tailwindplus/elements@1" type="module"></script>
</head>
<body class="h-full">

{{-- ── Mobile sidebar (drawer) ─────────────────────────────────────── --}}
<el-dialog>
    <dialog id="sidebar-mobile" class="backdrop:bg-transparent lg:hidden">
        <el-dialog-backdrop class="fixed inset-0 bg-gray-900/80 transition-opacity duration-300 ease-linear data-closed:opacity-0"></el-dialog-backdrop>
        <div tabindex="0" class="fixed inset-0 flex focus:outline-none">
            <el-dialog-panel class="group/dialog-panel relative mr-16 flex w-full max-w-xs flex-1 transform transition duration-300 ease-in-out data-closed:-translate-x-full">
                <div class="absolute top-0 left-full flex w-16 justify-center pt-5 duration-300 ease-in-out group-data-closed/dialog-panel:opacity-0">
                    <button type="button" command="close" commandfor="sidebar-mobile" class="-m-2.5 p-2.5">
                        <span class="sr-only">Cerrar menú</span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" class="size-6 text-white">
                            <path d="M6 18 18 6M6 6l12 12" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                </div>
                <div class="relative flex grow flex-col gap-y-5 overflow-y-auto bg-blue-800 px-6 pb-4">
                    @include('layouts.sidebar-content')
                </div>
            </el-dialog-panel>
        </div>
    </dialog>
</el-dialog>

{{-- ── Desktop sidebar (fixed) ─────────────────────────────────────── --}}
<div class="hidden lg:fixed lg:inset-y-0 lg:z-50 lg:flex lg:w-72 lg:flex-col">
    <div class="relative flex grow flex-col gap-y-5 overflow-y-auto bg-blue-800 px-6 pb-4">
        @include('layouts.sidebar-content')
    </div>
</div>

{{-- ── Main area ────────────────────────────────────────────────────── --}}
<div class="lg:pl-72">

    {{-- Top bar --}}
    <div class="sticky top-0 z-40 flex h-16 shrink-0 items-center gap-x-4 border-b border-gray-200 bg-white px-4 shadow-xs sm:gap-x-6 sm:px-6 lg:px-8">

        {{-- Mobile hamburger --}}
        <button type="button" command="show-modal" commandfor="sidebar-mobile"
                class="-m-2.5 p-2.5 text-gray-700 lg:hidden">
            <span class="sr-only">Abrir menú</span>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" class="size-6">
                <path d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </button>
        <div aria-hidden="true" class="h-6 w-px bg-gray-900/10 lg:hidden"></div>

        <div class="flex flex-1 items-center justify-end gap-x-4 lg:gap-x-6">

            {{-- Last poll status --}}
            <span class="hidden text-xs text-gray-400 sm:block">
                @if($lastPolled = \App\Models\Setting::get('last_polled_at'))
                    Último sondeo: {{ \Carbon\Carbon::parse($lastPolled)->diffForHumans() }}
                @else
                    <span class="text-yellow-500">Sin sondear aún</span>
                @endif
            </span>

            <div aria-hidden="true" class="hidden lg:block lg:h-6 lg:w-px lg:bg-gray-900/10"></div>

            {{-- User dropdown --}}
            <el-dropdown class="relative">
                <button class="flex items-center gap-x-2">
                    <span class="flex size-8 items-center justify-center rounded-full bg-blue-800 text-xs font-semibold text-white">
                        {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                    </span>
                    <span class="hidden lg:flex lg:items-center">
                        <span class="text-sm/6 font-semibold text-gray-900">{{ auth()->user()->name ?? '' }}</span>
                        <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" class="ml-2 size-4 text-gray-400">
                            <path d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" fill-rule="evenodd"/>
                        </svg>
                    </span>
                </button>
                <el-menu anchor="bottom end" popover
                         class="w-36 origin-top-right rounded-md bg-white py-2 shadow-lg outline outline-gray-900/5 transition transition-discrete [--anchor-gap:--spacing(2.5)] data-closed:scale-95 data-closed:opacity-0 data-enter:duration-100 data-enter:ease-out data-leave:duration-75 data-leave:ease-in">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="block w-full px-3 py-1 text-left text-sm/6 text-gray-900 hover:bg-gray-50">
                            Cerrar sesión
                        </button>
                    </form>
                </el-menu>
            </el-dropdown>
        </div>
    </div>

    {{-- Flash toast --}}
    @if(session('success') || session('error'))
    <div id="toast" aria-live="assertive" class="pointer-events-none fixed inset-0 z-50 flex items-end px-4 py-6 sm:items-start sm:p-6">
        <div class="flex w-full flex-col items-center space-y-4 sm:items-end">
            <div class="pointer-events-auto w-full max-w-sm rounded-lg bg-white shadow-lg outline-1 outline-black/5">
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
                            <p class="text-sm font-medium text-gray-900">{{ session('success') ?? session('error') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        const toast = document.getElementById('toast');
        if (toast) setTimeout(() => toast.style.opacity = '0', 5000);
    </script>
    @endif

    {{-- Page content --}}
    <main>
        @yield('content')
    </main>

</div>
</body>
</html>
