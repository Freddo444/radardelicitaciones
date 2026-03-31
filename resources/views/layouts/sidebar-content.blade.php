{{-- Brand --}}
<div class="flex h-16 shrink-0 items-center" :class="sidebarCollapsed ? 'justify-center' : 'gap-x-3'">
    <img src="/images/android-chrome-192x192.png" alt="Radar de Licitaciones" class="h-8 w-auto rounded">
    <div class="sidebar-brand-text leading-tight" x-show="!sidebarCollapsed" x-cloak>
        <p class="text-sm font-bold text-white">Radar de Licitaciones</p>
    </div>
</div>

{{-- Collapse toggle (desktop only) --}}
<button @click="sidebarCollapsed = !sidebarCollapsed"
        class="hidden lg:flex w-full items-center justify-center rounded-md py-1.5 text-blue-300 hover:bg-blue-900 hover:text-white transition-colors -mt-3 mb-1">
    <svg class="size-5 transition-transform duration-300" :class="sidebarCollapsed && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" d="m18.75 4.5-7.5 7.5 7.5 7.5m-6-15L5.25 12l7.5 7.5"/>
    </svg>
</button>

<nav class="flex flex-1 flex-col" :class="sidebarCollapsed && 'sidebar-collapsed'">
    <ul role="list" class="flex flex-1 flex-col gap-y-7" :class="sidebarCollapsed && 'gap-y-4'">

        {{-- ── Monitor ───────────────────────────────────────── --}}
        <li>
            <ul role="list" class="-mx-2 space-y-1">
                <li>
                    <a href="{{ route('dashboard') }}" title="Inicio"
                       class="sidebar-link group flex gap-x-3 rounded-md p-2 text-sm/6 font-semibold {{ request()->routeIs('dashboard') ? 'bg-blue-900 text-white' : 'text-blue-200 hover:bg-blue-900 hover:text-white' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"
                             class="size-6 shrink-0 {{ request()->routeIs('dashboard') ? 'text-white' : 'text-blue-200 group-hover:text-white' }}">
                            <path d="m2.25 12 8.954-8.955a1.126 1.126 0 0 1 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span class="sidebar-label">Inicio</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('convocatorias.index') }}" title="Procesos"
                       class="sidebar-link group flex gap-x-3 rounded-md p-2 text-sm/6 font-semibold {{ request()->routeIs('convocatorias.*') ? 'bg-blue-900 text-white' : 'text-blue-200 hover:bg-blue-900 hover:text-white' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"
                             class="size-6 shrink-0 {{ request()->routeIs('convocatorias.*') ? 'text-white' : 'text-blue-200 group-hover:text-white' }}">
                            <path d="M3.75 3v11.25A2.25 2.25 0 0 0 6 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0 1 18 16.5h-2.25m-7.5 0h7.5m-7.5 0-1 3m8.5-3 1 3m0 0 .5 1.5m-.5-1.5h-9.5m0 0-.5 1.5m.75-9 3-3 2.148 2.148A12.061 12.061 0 0 1 16.5 7.605" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span class="sidebar-label">Procesos</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('tablero.index') }}" title="Tablero"
                       class="sidebar-link group flex gap-x-3 rounded-md p-2 text-sm/6 font-semibold {{ request()->routeIs('tablero.*') ? 'bg-blue-900 text-white' : 'text-blue-200 hover:bg-blue-900 hover:text-white' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"
                             class="size-6 shrink-0 {{ request()->routeIs('tablero.*') ? 'text-white' : 'text-blue-200 group-hover:text-white' }}">
                            <path d="M9 4.5v15m6-15v15m-10.875 0h15.75c.621 0 1.125-.504 1.125-1.125V5.625c0-.621-.504-1.125-1.125-1.125H4.125C3.504 4.5 3 5.004 3 5.625v12.75c0 .621.504 1.125 1.125 1.125Z" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span class="sidebar-label">Tablero</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('rubros.index') }}" title="Rubros"
                       class="sidebar-link group flex gap-x-3 rounded-md p-2 text-sm/6 font-semibold {{ request()->routeIs('rubros.*') ? 'bg-blue-900 text-white' : 'text-blue-200 hover:bg-blue-900 hover:text-white' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"
                             class="size-6 shrink-0 {{ request()->routeIs('rubros.*') ? 'text-white' : 'text-blue-200 group-hover:text-white' }}">
                            <path d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M6 6h.008v.008H6V6Z" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span class="sidebar-label">Rubros</span>
                    </a>
                </li>
            </ul>
        </li>

        {{-- ── Inteligencia ──────────────────────────────────── --}}
        <li>
            <div class="sidebar-section-title text-xs/6 font-semibold text-blue-200">Inteligencia</div>
            <ul role="list" class="-mx-2 mt-2 space-y-1" :class="sidebarCollapsed && 'mt-0'">
                <li>
                    <a href="{{ route('inteligencia.adjudicados') }}" title="Adjudicados"
                       class="sidebar-link group flex gap-x-3 rounded-md p-2 text-sm/6 font-semibold {{ request()->routeIs('inteligencia.adjudicados') ? 'bg-blue-900 text-white' : 'text-blue-200 hover:bg-blue-900 hover:text-white' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"
                             class="size-6 shrink-0 {{ request()->routeIs('inteligencia.adjudicados') ? 'text-white' : 'text-blue-200 group-hover:text-white' }}">
                            <path d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span class="sidebar-label">Adjudicados</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('inteligencia.pacc') }}" title="PACC"
                       class="sidebar-link group flex gap-x-3 rounded-md p-2 text-sm/6 font-semibold {{ request()->routeIs('inteligencia.pacc') ? 'bg-blue-900 text-white' : 'text-blue-200 hover:bg-blue-900 hover:text-white' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"
                             class="size-6 shrink-0 {{ request()->routeIs('inteligencia.pacc') ? 'text-white' : 'text-blue-200 group-hover:text-white' }}">
                            <path d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span class="sidebar-label">PACC</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('inteligencia.contratos') }}" title="Contratos"
                       class="sidebar-link group flex gap-x-3 rounded-md p-2 text-sm/6 font-semibold {{ request()->routeIs('inteligencia.contratos') ? 'bg-blue-900 text-white' : 'text-blue-200 hover:bg-blue-900 hover:text-white' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"
                             class="size-6 shrink-0 {{ request()->routeIs('inteligencia.contratos') ? 'text-white' : 'text-blue-200 group-hover:text-white' }}">
                            <path d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span class="sidebar-label">Contratos</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('inteligencia.proveedores') }}" title="Proveedores"
                       class="sidebar-link group flex gap-x-3 rounded-md p-2 text-sm/6 font-semibold {{ request()->routeIs('inteligencia.proveedores') ? 'bg-blue-900 text-white' : 'text-blue-200 hover:bg-blue-900 hover:text-white' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"
                             class="size-6 shrink-0 {{ request()->routeIs('inteligencia.proveedores') ? 'text-white' : 'text-blue-200 group-hover:text-white' }}">
                            <path d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span class="sidebar-label">Proveedores</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('inteligencia.instituciones') }}" title="Instituciones"
                       class="sidebar-link group flex gap-x-3 rounded-md p-2 text-sm/6 font-semibold {{ request()->routeIs('inteligencia.instituciones') ? 'bg-blue-900 text-white' : 'text-blue-200 hover:bg-blue-900 hover:text-white' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"
                             class="size-6 shrink-0 {{ request()->routeIs('inteligencia.instituciones') ? 'text-white' : 'text-blue-200 group-hover:text-white' }}">
                            <path d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0 0 12 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75Z" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span class="sidebar-label">Instituciones</span>
                    </a>
                </li>
            </ul>
        </li>

        {{-- ── Empresa ───────────────────────────────────────── --}}
        <li>
            <div class="sidebar-section-title text-xs/6 font-semibold text-blue-200">Empresa</div>
            <ul role="list" class="-mx-2 mt-2 space-y-1" :class="sidebarCollapsed && 'mt-0'">
                <li>
                    <a href="{{ route('empresa.index') }}" title="Perfil"
                       class="sidebar-link group flex gap-x-3 rounded-md p-2 text-sm/6 font-semibold {{ request()->routeIs('empresa.*') ? 'bg-blue-900 text-white' : 'text-blue-200 hover:bg-blue-900 hover:text-white' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"
                             class="size-6 shrink-0 {{ request()->routeIs('empresa.*') ? 'text-white' : 'text-blue-200 group-hover:text-white' }}">
                            <path d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span class="sidebar-label">Perfil</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('documentos.index') }}" title="Documentos"
                       class="sidebar-link group flex gap-x-3 rounded-md p-2 text-sm/6 font-semibold {{ request()->routeIs('documentos.*') ? 'bg-blue-900 text-white' : 'text-blue-200 hover:bg-blue-900 hover:text-white' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"
                             class="size-6 shrink-0 {{ request()->routeIs('documentos.*') ? 'text-white' : 'text-blue-200 group-hover:text-white' }}">
                            <path d="M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span class="sidebar-label">Documentos</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('personal.index') }}" title="Personal"
                       class="sidebar-link group flex gap-x-3 rounded-md p-2 text-sm/6 font-semibold {{ request()->routeIs('personal.*') ? 'bg-blue-900 text-white' : 'text-blue-200 hover:bg-blue-900 hover:text-white' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"
                             class="size-6 shrink-0 {{ request()->routeIs('personal.*') ? 'text-white' : 'text-blue-200 group-hover:text-white' }}">
                            <path d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span class="sidebar-label">Personal</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('proyectos.index') }}" title="Proyectos"
                       class="sidebar-link group flex gap-x-3 rounded-md p-2 text-sm/6 font-semibold {{ request()->routeIs('proyectos.*') ? 'bg-blue-900 text-white' : 'text-blue-200 hover:bg-blue-900 hover:text-white' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"
                             class="size-6 shrink-0 {{ request()->routeIs('proyectos.*') ? 'text-white' : 'text-blue-200 group-hover:text-white' }}">
                            <path d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 0 0 .75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 0 0-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0 1 12 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 0 1-.673-.38m0 0A2.18 2.18 0 0 1 3 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 0 1 3.413-.387m7.5 0V5.25A2.25 2.25 0 0 0 13.5 3h-3a2.25 2.25 0 0 0-2.25 2.25v.894m7.5 0a48.667 48.667 0 0 0-7.5 0M12 12.75h.008v.008H12v-.008Z" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span class="sidebar-label">Proyectos</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('equipos.index') }}" title="Equipos"
                       class="sidebar-link group flex gap-x-3 rounded-md p-2 text-sm/6 font-semibold {{ request()->routeIs('equipos.*') ? 'bg-blue-900 text-white' : 'text-blue-200 hover:bg-blue-900 hover:text-white' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"
                             class="size-6 shrink-0 {{ request()->routeIs('equipos.*') ? 'text-white' : 'text-blue-200 group-hover:text-white' }}">
                            <path d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l5.654-4.654m5.5-4.236.084-.003a1.125 1.125 0 0 1 .82 1.916l-.253.253c-.34.34-.508.81-.508 1.28v.252c0 .408-.16.8-.444 1.09l-.39.39a1.125 1.125 0 0 1-1.59 0l-1.5-1.5a1.125 1.125 0 0 1 0-1.59l.39-.39c.29-.284.68-.444 1.09-.444h.252c.47 0 .94-.168 1.28-.508l.253-.253c.348-.348.94-.348 1.288 0Z" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span class="sidebar-label">Equipos</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('financiero.index') }}" title="Financiero"
                       class="sidebar-link group flex gap-x-3 rounded-md p-2 text-sm/6 font-semibold {{ request()->routeIs('financiero.*') ? 'bg-blue-900 text-white' : 'text-blue-200 hover:bg-blue-900 hover:text-white' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"
                             class="size-6 shrink-0 {{ request()->routeIs('financiero.*') ? 'text-white' : 'text-blue-200 group-hover:text-white' }}">
                            <path d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span class="sidebar-label">Financiero</span>
                    </a>
                </li>
            </ul>
        </li>

        {{-- ── Ofertas ───────────────────────────────────────── --}}
        <li>
            <div class="sidebar-section-title text-xs/6 font-semibold text-blue-200">Ofertas</div>
            <ul role="list" class="-mx-2 mt-2 space-y-1" :class="sidebarCollapsed && 'mt-0'">
                <li>
                    <a href="{{ route('ofertas.index') }}" title="Preparaciones"
                       class="sidebar-link group flex gap-x-3 rounded-md p-2 text-sm/6 font-semibold {{ request()->routeIs('ofertas.*') ? 'bg-blue-900 text-white' : 'text-blue-200 hover:bg-blue-900 hover:text-white' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"
                             class="size-6 shrink-0 {{ request()->routeIs('ofertas.*') ? 'text-white' : 'text-blue-200 group-hover:text-white' }}">
                            <path d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span class="sidebar-label">Preparaciones</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('documentos-generados.index') }}" title="Docs. Generados"
                       class="sidebar-link group flex gap-x-3 rounded-md p-2 text-sm/6 font-semibold {{ request()->routeIs('documentos-generados.*') || request()->routeIs('prellenado.*') ? 'bg-blue-900 text-white' : 'text-blue-200 hover:bg-blue-900 hover:text-white' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"
                             class="size-6 shrink-0 {{ request()->routeIs('documentos-generados.*') || request()->routeIs('prellenado.*') ? 'text-white' : 'text-blue-200 group-hover:text-white' }}">
                            <path d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span class="sidebar-label">Docs. Generados</span>
                    </a>
                </li>
            </ul>
        </li>

        {{-- ── Empresa activa (V3: company switcher) ────────── --}}
        <li>
            <div class="sidebar-section-title text-xs/6 font-semibold text-blue-200">Empresa activa</div>
            <ul role="list" class="-mx-2 mt-2 space-y-1" :class="sidebarCollapsed && 'mt-0'">
                <li>
                    @php $empresa = currentCompany(); @endphp
                    <a href="{{ route('empresa.index') }}" title="{{ $empresa->razon_social ?? 'Sin configurar' }}"
                       class="sidebar-link group flex gap-x-3 rounded-md p-2 text-sm/6 font-semibold text-blue-200 hover:bg-blue-900 hover:text-white">
                        <span class="flex size-6 shrink-0 items-center justify-center rounded-lg border border-blue-400 bg-blue-700 text-[0.625rem] font-medium text-white">
                            {{ strtoupper(substr($empresa->razon_social ?? 'E', 0, 1)) }}
                        </span>
                        <span class="sidebar-company-name truncate">{{ $empresa->razon_social ?? 'Sin configurar' }}</span>
                    </a>
                </li>
            </ul>
        </li>

        {{-- ── Sistema (bottom) ─────────────────────────────── --}}
        <li class="mt-auto">
            <ul role="list" class="-mx-2 space-y-1">
                <li>
                    <a href="{{ route('logs.index') }}" title="Registros"
                       class="sidebar-link group flex gap-x-3 rounded-md p-2 text-sm/6 font-semibold {{ request()->routeIs('logs.*') ? 'bg-blue-900 text-white' : 'text-blue-200 hover:bg-blue-900 hover:text-white' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"
                             class="size-6 shrink-0 {{ request()->routeIs('logs.*') ? 'text-white' : 'text-blue-200 group-hover:text-white' }}">
                            <path d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75Z" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span class="sidebar-label">Registros</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('company-users.index') }}" title="Usuarios"
                       class="sidebar-link group flex gap-x-3 rounded-md p-2 text-sm/6 font-semibold {{ request()->routeIs('company-users.*') ? 'bg-blue-900 text-white' : 'text-blue-200 hover:bg-blue-900 hover:text-white' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"
                             class="size-6 shrink-0 {{ request()->routeIs('company-users.*') ? 'text-white' : 'text-blue-200 group-hover:text-white' }}">
                            <path d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span class="sidebar-label">Usuarios</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('settings.index') }}" title="Configuración"
                       class="sidebar-link group flex gap-x-3 rounded-md p-2 text-sm/6 font-semibold {{ request()->routeIs('settings.*') ? 'bg-blue-900 text-white' : 'text-blue-200 hover:bg-blue-900 hover:text-white' }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"
                             class="size-6 shrink-0 {{ request()->routeIs('settings.*') ? 'text-white' : 'text-blue-200 group-hover:text-white' }}">
                            <path d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span class="sidebar-label">Configuración</span>
                    </a>
                </li>
            </ul>
        </li>

    </ul>
</nav>
