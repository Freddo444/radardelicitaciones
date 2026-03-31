<!DOCTYPE html>
<html lang="es" class="h-full bg-white">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }} — @yield('title', 'Monitor')</title>
    <link rel="apple-touch-icon" sizes="180x180" href="/images/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/images/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/images/favicon-16x16.png">
    <link rel="shortcut icon" href="/favicon.ico">
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
                <div x-data="{ sidebarCollapsed: false }" class="relative flex grow flex-col gap-y-5 overflow-y-auto bg-blue-800 px-6 pb-4">
                    @include('layouts.sidebar-content')
                </div>
            </el-dialog-panel>
        </div>
    </dialog>
</el-dialog>

{{-- ── Desktop sidebar + Main (shared Alpine scope) ──────────────── --}}
<div x-data="{ sidebarCollapsed: JSON.parse(localStorage.getItem('sidebarCollapsed') ?? 'false') }"
     x-effect="localStorage.setItem('sidebarCollapsed', JSON.stringify(sidebarCollapsed))">

{{-- Desktop sidebar --}}
<div class="hidden lg:fixed lg:inset-y-0 lg:z-50 lg:flex lg:flex-col transition-all duration-300"
     :class="sidebarCollapsed ? 'lg:w-16' : 'lg:w-72'">
    <div class="relative flex grow flex-col gap-y-5 overflow-y-auto bg-blue-800 pb-4 transition-all duration-300"
         :class="sidebarCollapsed ? 'px-2' : 'px-6'">
        @include('layouts.sidebar-content')
    </div>
</div>

{{-- ── Main area ────────────────────────────────────────────────────── --}}
<div class="transition-all duration-300" :class="sidebarCollapsed ? 'lg:pl-16' : 'lg:pl-72'">

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

            {{-- Notification bell --}}
            <div x-data="notificationBell()" x-init="init()" class="relative">
                <button @click="toggle()" class="relative -m-1.5 p-1.5 text-gray-400 hover:text-gray-500">
                    <span class="sr-only">Notificaciones</span>
                    <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/>
                    </svg>
                    <span x-show="unreadCount > 0" x-cloak
                          class="absolute -top-1 -right-1 flex size-4 items-center justify-center rounded-full bg-red-500 text-[10px] font-bold text-white"
                          x-text="unreadCount > 9 ? '9+' : unreadCount"></span>
                </button>

                {{-- Backdrop --}}
                <div x-show="open" x-cloak @click="open = false" class="fixed inset-0 z-40"></div>

                {{-- Dropdown panel --}}
                <div x-show="open" x-cloak
                     x-transition:enter="transition ease-out duration-100"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-75"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-95"
                     class="absolute right-0 z-50 mt-2 w-80 origin-top-right rounded-xl bg-white shadow-lg ring-1 ring-gray-900/5">

                    <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3">
                        <h3 class="text-sm font-semibold text-gray-900">Notificaciones</h3>
                        <button x-show="unreadCount > 0" @click.stop="markAllRead()"
                                class="text-xs text-blue-600 hover:text-blue-500">
                            Marcar todas como leídas
                        </button>
                    </div>

                    <div class="max-h-80 overflow-y-auto">
                        <template x-if="loading">
                            <div class="flex items-center justify-center py-8">
                                <svg class="size-5 animate-spin text-gray-400" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                </svg>
                            </div>
                        </template>
                        <template x-if="!loading && notifications.length === 0">
                            <div class="px-4 py-8 text-center text-sm text-gray-500">
                                Sin notificaciones
                            </div>
                        </template>
                        <template x-for="n in notifications" :key="n.id">
                            <a :href="n.bid_id ? '/convocatorias?drawer=' + n.bid_id : '#'"
                               @click="markRead(n)"
                               class="flex gap-3 px-4 py-3 hover:bg-gray-50 border-b border-gray-50 last:border-0"
                               :class="n.read ? '' : 'bg-blue-50/50'">
                                <div class="shrink-0 mt-0.5">
                                    <span class="flex size-2 rounded-full" :class="n.read ? 'bg-transparent' : 'bg-blue-500'"></span>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium text-gray-900 truncate" x-text="n.title"></p>
                                    <p class="text-xs text-gray-500 truncate" x-text="n.body"></p>
                                    <p class="mt-0.5 text-xs text-gray-400" x-text="n.ago"></p>
                                </div>
                                <div class="shrink-0">
                                    <span class="inline-flex items-center rounded-full px-1.5 py-0.5 text-[10px] font-medium"
                                          :class="typeStyle(n.type)"
                                          x-text="typeLabel(n.type)"></span>
                                </div>
                            </a>
                        </template>
                    </div>

                    <div class="border-t border-gray-100 px-4 py-2">
                        <a href="{{ route('logs.index') }}" class="text-xs text-gray-500 hover:text-gray-700">
                            Ver todos los registros &rarr;
                        </a>
                    </div>
                </div>
            </div>

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
                         class="w-44 origin-top-right rounded-md bg-white py-2 shadow-lg outline outline-gray-900/5 transition transition-discrete [--anchor-gap:--spacing(2.5)] data-closed:scale-95 data-closed:opacity-0 data-enter:duration-100 data-enter:ease-out data-leave:duration-75 data-leave:ease-in">
                    <a href="{{ route('empresa.index') }}" class="block px-3 py-1 text-sm/6 text-gray-900 hover:bg-gray-50">Mi perfil</a>
                    <a href="{{ route('settings.index') }}" class="block px-3 py-1 text-sm/6 text-gray-900 hover:bg-gray-50">Configuración</a>
                    <div class="my-1 border-t border-gray-100"></div>
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

    {{-- Breadcrumbs --}}
    @hasSection('breadcrumbs')
        @yield('breadcrumbs')
    @else
        @php
        $__route = request()->route()?->getName();
        $__crumbs = match($__route) {
            // Monitor
            'convocatorias.index' => [['label' => 'Convocatorias']],
            'rubros.index' => [['label' => 'Rubros']],
            'tablero.index' => [['label' => 'Tablero']],
            'poll.progress' => [['label' => 'Sondeo']],

            // Inteligencia
            'inteligencia.adjudicados' => [['label' => 'Inteligencia', 'url' => '#'], ['label' => 'Adjudicados']],
            'inteligencia.pacc' => [['label' => 'Inteligencia', 'url' => '#'], ['label' => 'PACC']],
            'inteligencia.contratos' => [['label' => 'Inteligencia', 'url' => '#'], ['label' => 'Contratos']],
            'inteligencia.proveedores' => [['label' => 'Inteligencia', 'url' => '#'], ['label' => 'Proveedores']],
            'inteligencia.instituciones' => [['label' => 'Inteligencia', 'url' => '#'], ['label' => 'Instituciones']],

            // Empresa
            'empresa.index' => [['label' => 'Empresa']],
            'documentos.index' => [['label' => 'Empresa', 'url' => route('empresa.index')], ['label' => 'Documentos']],
            'documentos.versions' => [['label' => 'Empresa', 'url' => route('empresa.index')], ['label' => 'Documentos', 'url' => route('documentos.index')], ['label' => 'Versiones']],
            'personal.index' => [['label' => 'Empresa', 'url' => route('empresa.index')], ['label' => 'Personal']],
            'personal.show' => [['label' => 'Empresa', 'url' => route('empresa.index')], ['label' => 'Personal', 'url' => route('personal.index')], ['label' => 'Detalle']],
            'proyectos.index' => [['label' => 'Empresa', 'url' => route('empresa.index')], ['label' => 'Proyectos']],
            'proyectos.show' => [['label' => 'Empresa', 'url' => route('empresa.index')], ['label' => 'Proyectos', 'url' => route('proyectos.index')], ['label' => 'Detalle']],
            'equipos.index' => [['label' => 'Empresa', 'url' => route('empresa.index')], ['label' => 'Equipos']],
            'financiero.index' => [['label' => 'Empresa', 'url' => route('empresa.index')], ['label' => 'Financiero']],
            'financiero.create' => [['label' => 'Empresa', 'url' => route('empresa.index')], ['label' => 'Financiero', 'url' => route('financiero.index')], ['label' => 'Nuevo']],
            'financiero.show' => [['label' => 'Empresa', 'url' => route('empresa.index')], ['label' => 'Financiero', 'url' => route('financiero.index')], ['label' => 'Detalle']],

            // Ofertas
            'ofertas.index' => [['label' => 'Preparaciones']],
            'ofertas.create' => [['label' => 'Preparaciones', 'url' => route('ofertas.index')], ['label' => 'Nueva']],
            'ofertas.show' => [['label' => 'Preparaciones', 'url' => route('ofertas.index')], ['label' => 'Detalle']],
            'documentos-generados.index' => [['label' => 'Docs. Generados']],
            'documentos-generados.show' => [['label' => 'Docs. Generados', 'url' => route('documentos-generados.index')], ['label' => 'Detalle']],
            'prellenado.show' => [['label' => 'Docs. Generados', 'url' => route('documentos-generados.index')], ['label' => 'Prellenado']],
            'formularios.index' => [['label' => 'Formularios']],

            // Sistema
            'settings.index' => [['label' => 'Configuración']],
            'company-users.index' => [['label' => 'Usuarios']],
            'logs.index' => [['label' => 'Registros']],

            default => [],
        };
        @endphp
        <x-breadcrumbs :crumbs="$__crumbs" />
    @endif

    {{-- Page content --}}
    <main>
        @yield('content')
    </main>

</div>
</div>{{-- /Alpine sidebar scope --}}

<script>
function notificationBell() {
    return {
        open: false,
        loading: false,
        unreadCount: 0,
        notifications: [],
        pollInterval: null,

        init() {
            this.fetchCount();
            this.pollInterval = setInterval(() => this.fetchCount(), 60000);
        },

        async fetchCount() {
            try {
                const r = await fetch('/notifications/unread-count', {
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
                });
                if (r.ok) {
                    const d = await r.json();
                    this.unreadCount = d.count;
                }
            } catch (e) {}
        },

        async toggle() {
            this.open = !this.open;
            if (this.open) {
                this.loading = true;
                try {
                    const r = await fetch('/notifications/recent', {
                        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
                    });
                    if (r.ok) {
                        const d = await r.json();
                        this.notifications = d.notifications;
                    }
                } catch (e) {}
                this.loading = false;
            }
        },

        async markAllRead() {
            try {
                const r = await fetch('/notifications/mark-all-read', {
                    method: 'PATCH',
                    headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
                });
                if (r.ok) {
                    this.unreadCount = 0;
                    this.notifications = this.notifications.map(n => ({ ...n, read: true }));
                }
            } catch (e) {
                console.error('markAllRead failed:', e);
            }
        },

        async markRead(n) {
            if (n.read) return;
            try {
                await fetch(`/notifications/${n.id}/read`, {
                    method: 'PATCH',
                    headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
                });
                n.read = true;
                this.unreadCount = Math.max(0, this.unreadCount - 1);
            } catch (e) {}
        },

        typeLabel(type) {
            return { new_match: 'Nueva', status_changed: 'Cambio', deadline_approaching: 'Plazo', document_added: 'Doc' }[type] || type;
        },

        typeStyle(type) {
            return {
                new_match: 'bg-green-50 text-green-700',
                status_changed: 'bg-yellow-50 text-yellow-700',
                deadline_approaching: 'bg-red-50 text-red-700',
                document_added: 'bg-blue-50 text-blue-700',
            }[type] || 'bg-gray-50 text-gray-700';
        }
    };
}
</script>
</body>
</html>
