<!DOCTYPE html>
<html lang="es" class="h-full bg-white">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin — @yield('title', 'Dashboard')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="apple-touch-icon" sizes="180x180" href="/images/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/images/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/images/favicon-16x16.png">
    <link rel="shortcut icon" href="/favicon.ico">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full">

{{-- Static sidebar for desktop --}}
<div class="hidden lg:fixed lg:inset-y-0 lg:z-50 lg:flex lg:w-72 lg:flex-col">
    <div class="flex grow flex-col gap-y-5 overflow-y-auto bg-blue-800 px-6 pb-4">
        <div class="flex h-16 shrink-0 items-center">
            <img src="/images/badgeonly.png" alt="Radar de Licitaciones" class="mx-auto h-20 w-auto">
        </div>
        <nav class="flex flex-1 flex-col">
            <ul role="list" class="flex flex-1 flex-col gap-y-7">
                <li>
                    <ul role="list" class="-mx-2 space-y-1">
                        <li>
                            <a href="{{ route('admin.dashboard') }}"
                               class="group flex gap-x-3 rounded-md p-2 text-sm/6 font-semibold {{ request()->routeIs('admin.dashboard') ? 'bg-blue-900 text-white' : 'text-blue-200 hover:bg-blue-900 hover:text-white' }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"
                                     class="size-6 shrink-0 {{ request()->routeIs('admin.dashboard') ? 'text-white' : 'text-blue-200 group-hover:text-white' }}">
                                    <path d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                Dashboard
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.companies.index') }}"
                               class="group flex gap-x-3 rounded-md p-2 text-sm/6 font-semibold {{ request()->routeIs('admin.companies.*') ? 'bg-blue-900 text-white' : 'text-blue-200 hover:bg-blue-900 hover:text-white' }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"
                                     class="size-6 shrink-0 {{ request()->routeIs('admin.companies.*') ? 'text-white' : 'text-blue-200 group-hover:text-white' }}">
                                    <path d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                Empresas
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.subscriptions.index') }}"
                               class="group flex gap-x-3 rounded-md p-2 text-sm/6 font-semibold {{ request()->routeIs('admin.subscriptions.*') ? 'bg-blue-900 text-white' : 'text-blue-200 hover:bg-blue-900 hover:text-white' }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"
                                     class="size-6 shrink-0 {{ request()->routeIs('admin.subscriptions.*') ? 'text-white' : 'text-blue-200 group-hover:text-white' }}">
                                    <path d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                Suscripciones
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.payments.index') }}"
                               class="group flex gap-x-3 rounded-md p-2 text-sm/6 font-semibold {{ request()->routeIs('admin.payments.*') ? 'bg-blue-900 text-white' : 'text-blue-200 hover:bg-blue-900 hover:text-white' }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"
                                     class="size-6 shrink-0 {{ request()->routeIs('admin.payments.*') ? 'text-white' : 'text-blue-200 group-hover:text-white' }}">
                                    <path d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                Pagos
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="mt-auto">
                    <a href="/telescope" target="_blank" class="group -mx-2 flex gap-x-3 rounded-md p-2 text-sm/6 font-semibold text-blue-200 hover:bg-blue-900 hover:text-white">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" class="size-6 shrink-0 text-blue-200 group-hover:text-white">
                            <path d="M6.115 5.19l.319 1.913A6 6 0 008.11 10.36L9.75 12l-.387.775c-.217.433-.132.956.21 1.298l1.348 1.348c.21.21.329.497.329.795v1.089c0 .426.24.815.622 1.006l.153.076c.433.217.956.132 1.298-.21l.723-.723a8.7 8.7 0 002.288-4.042 1.087 1.087 0 00-.358-1.099l-1.33-1.108c-.251-.21-.582-.299-.905-.245l-1.17.195a1.125 1.125 0 01-.98-.314l-.295-.295a1.125 1.125 0 010-1.591l.13-.132a1.125 1.125 0 011.3-.21l.603.302a.809.809 0 001.086-1.086L14.25 7.5l1.256-.837a4.5 4.5 0 001.528-1.732l.146-.292M6.115 5.19A9 9 0 1017.18 4.64M6.115 5.19A8.965 8.965 0 0112 3c1.929 0 3.716.607 5.18 1.64" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        Telescope
                    </a>
                    <a href="{{ route('dashboard') }}" class="group -mx-2 flex gap-x-3 rounded-md p-2 text-sm/6 font-semibold text-blue-200 hover:bg-blue-900 hover:text-white">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" class="size-6 shrink-0 text-blue-200 group-hover:text-white">
                            <path d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        Volver al app
                    </a>
                </li>
            </ul>
        </nav>
    </div>
</div>

<div class="lg:pl-72">
    {{-- Top bar --}}
    <div class="sticky top-0 z-40 flex h-16 shrink-0 items-center gap-x-4 border-b border-gray-200 bg-white px-4 shadow-xs sm:gap-x-6 sm:px-6 lg:px-8">
        {{-- Mobile sidebar toggle --}}
        <button type="button" onclick="document.getElementById('mobile-sidebar').classList.toggle('hidden')" class="-m-2.5 p-2.5 text-gray-700 hover:text-gray-900 lg:hidden">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" class="size-6">
                <path d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
        </button>
        <div aria-hidden="true" class="h-6 w-px bg-gray-900/10 lg:hidden"></div>
        <div class="flex flex-1 items-center justify-end gap-x-4 lg:gap-x-6">
            <span class="text-sm text-gray-500">{{ auth()->user()->name }}</span>
        </div>
    </div>

    {{-- Impersonation banner --}}
    @if(session()->has('impersonating_company_id'))
    <div class="bg-yellow-50 border-b border-yellow-200 px-4 py-2.5 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-x-2">
                <svg viewBox="0 0 20 20" fill="currentColor" class="size-5 text-yellow-600">
                    <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.345 0-2.189-1.458-1.515-2.625L8.485 2.495ZM10 5a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0v-3.5A.75.75 0 0 1 10 5Zm0 9a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" />
                </svg>
                <p class="text-sm font-medium text-yellow-800">Impersonando empresa #{{ session('impersonating_company_id') }}</p>
            </div>
            <form method="POST" action="{{ route('admin.impersonate.stop') }}">
                @csrf
                <button type="submit" class="rounded-md bg-yellow-100 px-2.5 py-1 text-xs font-semibold text-yellow-800 hover:bg-yellow-200">Detener</button>
            </form>
        </div>
    </div>
    @endif

    {{-- Mobile sidebar --}}
    <div id="mobile-sidebar" class="hidden lg:hidden fixed inset-0 z-50 bg-gray-900/80" onclick="this.classList.add('hidden')">
        <div class="fixed inset-y-0 left-0 w-72 bg-blue-800 px-6 pb-4 pt-5" onclick="event.stopPropagation()">
            <div class="mb-6 flex items-center justify-between">
                <img src="/images/badgeonly.png" alt="Radar de Licitaciones" class="mx-auto h-20 w-auto">
                <button onclick="document.getElementById('mobile-sidebar').classList.add('hidden')" class="text-blue-200">
                    <svg class="size-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M6 18 18 6M6 6l12 12" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
            </div>
            <nav class="space-y-1">
                <a href="{{ route('admin.dashboard') }}" class="flex gap-x-3 rounded-md p-2 text-sm/6 font-semibold {{ request()->routeIs('admin.dashboard') ? 'bg-blue-900 text-white' : 'text-blue-200' }}">Dashboard</a>
                <a href="{{ route('admin.companies.index') }}" class="flex gap-x-3 rounded-md p-2 text-sm/6 font-semibold {{ request()->routeIs('admin.companies.*') ? 'bg-blue-900 text-white' : 'text-blue-200' }}">Empresas</a>
                <a href="{{ route('admin.subscriptions.index') }}" class="flex gap-x-3 rounded-md p-2 text-sm/6 font-semibold {{ request()->routeIs('admin.subscriptions.*') ? 'bg-blue-900 text-white' : 'text-blue-200' }}">Suscripciones</a>
                <a href="{{ route('admin.payments.index') }}" class="flex gap-x-3 rounded-md p-2 text-sm/6 font-semibold {{ request()->routeIs('admin.payments.*') ? 'bg-blue-900 text-white' : 'text-blue-200' }}">Pagos</a>
                <a href="/telescope" target="_blank" class="flex gap-x-3 rounded-md p-2 text-sm/6 font-semibold text-blue-200">Telescope</a>
            </nav>
        </div>
    </div>

    <main class="py-10">
        <div class="px-4 sm:px-6 lg:px-8">
            @if(session('success'))
            <div class="mb-6 rounded-md bg-green-50 p-4">
                <div class="flex">
                    <svg viewBox="0 0 20 20" fill="currentColor" class="size-5 text-green-400"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd" /></svg>
                    <p class="ml-3 text-sm font-medium text-green-800">{{ session('success') }}</p>
                </div>
            </div>
            @endif
            @if(session('info'))
            <div class="mb-6 rounded-md bg-blue-50 p-4">
                <div class="flex">
                    <svg viewBox="0 0 20 20" fill="currentColor" class="size-5 text-blue-400"><path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-7-4a1 1 0 1 1-2 0 1 1 0 0 1 2 0ZM9 9a.75.75 0 0 0 0 1.5h.253a.25.25 0 0 1 .244.304l-.459 2.066A1.75 1.75 0 0 0 10.747 15H11a.75.75 0 0 0 0-1.5h-.253a.25.25 0 0 1-.244-.304l.459-2.066A1.75 1.75 0 0 0 9.253 9H9Z" clip-rule="evenodd" /></svg>
                    <p class="ml-3 text-sm font-medium text-blue-800">{{ session('info') }}</p>
                </div>
            </div>
            @endif
            @if(session('error'))
            <div class="mb-6 rounded-md bg-red-50 p-4">
                <div class="flex">
                    <svg viewBox="0 0 20 20" fill="currentColor" class="size-5 text-red-400"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16ZM8.28 7.22a.75.75 0 0 0-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 1 0 1.06 1.06L10 11.06l1.72 1.72a.75.75 0 1 0 1.06-1.06L11.06 10l1.72-1.72a.75.75 0 0 0-1.06-1.06L10 8.94 8.28 7.22Z" clip-rule="evenodd" /></svg>
                    <p class="ml-3 text-sm font-medium text-red-800">{{ session('error') }}</p>
                </div>
            </div>
            @endif

            @yield('content')
        </div>
    </main>
</div>

</body>
</html>
