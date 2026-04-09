<!DOCTYPE html>
<html lang="es" class="h-full bg-zinc-100">
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
    <script defer src="https://analytics.radardelicitaciones.com/script.js" data-website-id="3a71e47e-8466-4078-b759-462a63b46135"></script>
</head>
<body class="h-full antialiased">

{{-- Static sidebar for desktop --}}
<div class="hidden lg:fixed lg:inset-y-0 lg:z-50 lg:flex lg:w-80 lg:flex-col">
    <div class="flex grow flex-col gap-y-8 overflow-y-auto border-r border-slate-700/40 bg-slate-900 px-7 pb-8 pt-6">
        <div class="flex shrink-0 flex-col items-center gap-3 border-b border-white/10 pb-8">
            <img src="/images/badgeonly.png" alt="Radar de Licitaciones" class="h-16 w-auto opacity-95">
            <p class="text-center text-xs font-medium uppercase tracking-wider text-slate-400">Panel de administración</p>
        </div>
        <nav class="flex flex-1 flex-col">
            <ul role="list" class="flex flex-1 flex-col gap-y-8">
                <li>
                    <ul role="list" class="space-y-1.5">
                        <li>
                            <a href="{{ route('admin.dashboard') }}"
                               class="group flex gap-x-3 rounded-lg px-3 py-2.5 text-sm font-semibold transition-colors {{ request()->routeIs('admin.dashboard') ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"
                                     class="size-5 shrink-0 opacity-80">
                                    <path d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                Dashboard
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.companies.index') }}"
                               class="group flex gap-x-3 rounded-lg px-3 py-2.5 text-sm font-semibold transition-colors {{ request()->routeIs('admin.companies.*') ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"
                                     class="size-5 shrink-0 opacity-80">
                                    <path d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                Empresas
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.subscriptions.index') }}"
                               class="group flex gap-x-3 rounded-lg px-3 py-2.5 text-sm font-semibold transition-colors {{ request()->routeIs('admin.subscriptions.*') ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"
                                     class="size-5 shrink-0 opacity-80">
                                    <path d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                Suscripciones
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.payments.index') }}"
                               class="group flex gap-x-3 rounded-lg px-3 py-2.5 text-sm font-semibold transition-colors {{ request()->routeIs('admin.payments.*') ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"
                                     class="size-5 shrink-0 opacity-80">
                                    <path d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                Pagos
                            </a>
                        </li>
                    </ul>
                </li>
                <li class="mt-auto space-y-1.5 border-t border-white/10 pt-6">
                    <a href="/telescope" target="_blank" class="group flex gap-x-3 rounded-lg px-3 py-2.5 text-sm font-semibold text-slate-400 transition-colors hover:bg-white/5 hover:text-white">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" class="size-5 shrink-0 opacity-80">
                            <path d="M6.115 5.19l.319 1.913A6 6 0 008.11 10.36L9.75 12l-.387.775c-.217.433-.132.956.21 1.298l1.348 1.348c.21.21.329.497.329.795v1.089c0 .426.24.815.622 1.006l.153.076c.433.217.956.132 1.298-.21l.723-.723a8.7 8.7 0 002.288-4.042 1.087 1.087 0 00-.358-1.099l-1.33-1.108c-.251-.21-.582-.299-.905-.245l-1.17.195a1.125 1.125 0 01-.98-.314l-.295-.295a1.125 1.125 0 010-1.591l.13-.132a1.125 1.125 0 011.3-.21l.603.302a.809.809 0 001.086-1.086L14.25 7.5l1.256-.837a4.5 4.5 0 001.528-1.732l.146-.292M6.115 5.19A9 9 0 1017.18 4.64M6.115 5.19A8.965 8.965 0 0112 3c1.929 0 3.716.607 5.18 1.64" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        Telescope
                    </a>
                    <a href="{{ route('dashboard') }}" class="group flex gap-x-3 rounded-lg px-3 py-2.5 text-sm font-semibold text-slate-400 transition-colors hover:bg-white/5 hover:text-white">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" class="size-5 shrink-0 opacity-80">
                            <path d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        Volver al app
                    </a>
                </li>
            </ul>
        </nav>
    </div>
</div>

<div class="lg:pl-80">
    {{-- Top bar --}}
    <div class="sticky top-0 z-40 flex h-16 shrink-0 items-center gap-x-4 border-b border-zinc-200/80 bg-white/90 px-4 shadow-sm backdrop-blur-md sm:gap-x-6 sm:px-6 lg:px-10">
        <button type="button" onclick="document.getElementById('mobile-sidebar').classList.toggle('hidden')" class="-m-2.5 rounded-lg p-2.5 text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 lg:hidden">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" class="size-6">
                <path d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
        </button>
        <div aria-hidden="true" class="h-6 w-px bg-zinc-200 lg:hidden"></div>
        <div class="flex min-w-0 flex-1 items-center justify-end gap-x-4 lg:gap-x-6">
            <span class="truncate text-sm font-medium text-zinc-600">{{ auth()->user()->name }}</span>
        </div>
    </div>

    {{-- Impersonation banner --}}
    @if(session()->has('impersonating_company_id'))
    <div class="border-b border-amber-200/80 bg-amber-50 px-4 py-4 sm:px-6 lg:px-10">
        <div class="mx-auto flex max-w-7xl flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-x-3">
                <svg viewBox="0 0 20 20" fill="currentColor" class="size-5 shrink-0 text-amber-600">
                    <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.345 0-2.189-1.458-1.515-2.625L8.485 2.495ZM10 5a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0v-3.5A.75.75 0 0 1 10 5Zm0 9a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" />
                </svg>
                <p class="text-sm font-medium text-amber-900">Impersonando empresa #{{ session('impersonating_company_id') }}</p>
            </div>
            <form method="POST" action="{{ route('admin.impersonate.stop') }}">
                @csrf
                <button type="submit" class="rounded-lg bg-amber-100 px-3 py-2 text-xs font-semibold text-amber-900 ring-1 ring-amber-200/80 hover:bg-amber-200/80">Detener</button>
            </form>
        </div>
    </div>
    @endif

    {{-- Mobile sidebar --}}
    <div id="mobile-sidebar" class="fixed inset-0 z-50 hidden bg-zinc-900/60 backdrop-blur-sm lg:hidden" onclick="this.classList.add('hidden')">
        <div class="fixed inset-y-0 left-0 flex w-80 flex-col overflow-y-auto border-r border-slate-700/40 bg-slate-900 px-7 pb-8 pt-6 shadow-xl" onclick="event.stopPropagation()">
            <div class="mb-8 flex items-center justify-between border-b border-white/10 pb-6">
                <img src="/images/badgeonly.png" alt="Radar de Licitaciones" class="h-14 w-auto opacity-95">
                <button type="button" onclick="document.getElementById('mobile-sidebar').classList.add('hidden')" class="rounded-lg p-2 text-slate-400 hover:bg-white/5 hover:text-white">
                    <svg class="size-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M6 18 18 6M6 6l12 12" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
            </div>
            <nav class="space-y-1.5">
                <a href="{{ route('admin.dashboard') }}" class="block rounded-lg px-3 py-2.5 text-sm font-semibold {{ request()->routeIs('admin.dashboard') ? 'bg-white/10 text-white' : 'text-slate-300' }}">Dashboard</a>
                <a href="{{ route('admin.companies.index') }}" class="block rounded-lg px-3 py-2.5 text-sm font-semibold {{ request()->routeIs('admin.companies.*') ? 'bg-white/10 text-white' : 'text-slate-300' }}">Empresas</a>
                <a href="{{ route('admin.subscriptions.index') }}" class="block rounded-lg px-3 py-2.5 text-sm font-semibold {{ request()->routeIs('admin.subscriptions.*') ? 'bg-white/10 text-white' : 'text-slate-300' }}">Suscripciones</a>
                <a href="{{ route('admin.payments.index') }}" class="block rounded-lg px-3 py-2.5 text-sm font-semibold {{ request()->routeIs('admin.payments.*') ? 'bg-white/10 text-white' : 'text-slate-300' }}">Pagos</a>
                <a href="/telescope" target="_blank" class="block rounded-lg px-3 py-2.5 text-sm font-semibold text-slate-400">Telescope</a>
            </nav>
        </div>
    </div>

    <main class="min-h-[calc(100vh-4rem)] py-10 sm:py-12 lg:py-14">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-10">
            @if(session('success'))
            <div class="mb-8 rounded-xl border border-emerald-200/80 bg-emerald-50/90 p-5 shadow-sm">
                <div class="flex gap-4">
                    <svg viewBox="0 0 20 20" fill="currentColor" class="size-5 shrink-0 text-emerald-500"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd" /></svg>
                    <p class="text-sm font-medium leading-relaxed text-emerald-900">{{ session('success') }}</p>
                </div>
            </div>
            @endif
            @if(session('info'))
            <div class="mb-8 rounded-xl border border-sky-200/80 bg-sky-50/90 p-5 shadow-sm">
                <div class="flex gap-4">
                    <svg viewBox="0 0 20 20" fill="currentColor" class="size-5 shrink-0 text-sky-500"><path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-7-4a1 1 0 1 1-2 0 1 1 0 0 1 2 0ZM9 9a.75.75 0 0 0 0 1.5h.253a.25.25 0 0 1 .244.304l-.459 2.066A1.75 1.75 0 0 0 10.747 15H11a.75.75 0 0 0 0-1.5h-.253a.25.25 0 0 1-.244-.304l.459-2.066A1.75 1.75 0 0 0 9.253 9H9Z" clip-rule="evenodd" /></svg>
                    <p class="text-sm font-medium leading-relaxed text-sky-900">{{ session('info') }}</p>
                </div>
            </div>
            @endif
            @if(session('error'))
            <div class="mb-8 rounded-xl border border-red-200/80 bg-red-50/90 p-5 shadow-sm">
                <div class="flex gap-4">
                    <svg viewBox="0 0 20 20" fill="currentColor" class="size-5 shrink-0 text-red-500"><path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16ZM8.28 7.22a.75.75 0 0 0-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 1 0 1.06 1.06L10 11.06l1.72 1.72a.75.75 0 1 0 1.06-1.06L11.06 10l1.72-1.72a.75.75 0 0 0-1.06-1.06L10 8.94 8.28 7.22Z" clip-rule="evenodd" /></svg>
                    <p class="text-sm font-medium leading-relaxed text-red-900">{{ session('error') }}</p>
                </div>
            </div>
            @endif

            @yield('content')
        </div>
    </main>
</div>

</body>
</html>
