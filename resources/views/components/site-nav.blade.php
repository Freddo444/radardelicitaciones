@props(['variant' => 'transparent'])

@php
    // Variants:
    //   'transparent' — original marketing nav: fixed, transparent over hero,
    //                   solidifies on scroll. Uses @yield overrides for per-page
    //                   tweaks (navBg, navLink, logoText).
    //   'solid'       — for auth pages: always solid white, sits at top of
    //                   document flow (not fixed). Visually matches the scrolled
    //                   state of the transparent variant.
    $isSolid = $variant === 'solid';
@endphp

@if($isSolid)
<nav x-data="{ open: false }"
     x-effect="document.body.classList.toggle('overflow-hidden', open)"
     class="border-b border-gray-100 bg-white">
    <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
        <a href="/" class="flex items-center gap-2">
            <img src="/images/LOGO.png" alt="Radar de Licitaciones" class="h-9 w-auto rounded">
            <span class="font-display hidden text-lg font-bold text-gray-900 sm:inline">Radar de Licitaciones</span>
        </a>

        <div class="hidden items-center gap-8 md:flex">
            <a href="/" class="text-sm font-medium text-gray-600 transition-colors hover:text-gray-900">Inicio</a>
            <a href="/precios" class="text-sm font-medium text-gray-600 transition-colors hover:text-gray-900">Precios</a>
            <a href="/#contacto" class="text-sm font-medium text-gray-600 transition-colors hover:text-gray-900">Contacto</a>
            <a href="{{ route('login') }}" class="text-sm font-medium text-gray-600 transition-colors hover:text-gray-900">Iniciar sesión</a>
            <a href="{{ route('register.trial') }}"
               class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-md shadow-indigo-600/25 transition hover:bg-indigo-500">
                Prueba gratis
            </a>
        </div>

        <button @click="open = !open" class="-m-2 p-2 text-gray-700 md:hidden">
            <svg class="size-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path x-show="!open" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path x-show="open" x-cloak d="M6 18 18 6M6 6l12 12" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </button>
    </div>

    <div x-show="open" x-cloak x-transition.opacity @click.away="open = false" class="border-t bg-white md:hidden">
        <div class="space-y-2 px-4 py-4">
            <a @click="open = false" href="/" class="block rounded-lg px-3 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50">Inicio</a>
            <a @click="open = false" href="/precios" class="block rounded-lg px-3 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50">Precios</a>
            <a @click="open = false" href="/#contacto" class="block rounded-lg px-3 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50">Contacto</a>
            <a @click="open = false" href="{{ route('login') }}" class="block rounded-lg px-3 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50">Iniciar sesión</a>
            <a @click="open = false" href="{{ route('register.trial') }}" class="block rounded-lg bg-indigo-600 px-3 py-3 text-center text-sm font-semibold text-white shadow-md shadow-indigo-600/20">Prueba gratis</a>
        </div>
    </div>
</nav>
@else
<nav x-data="{ scrolled: false, open: false }"
     x-effect="document.body.classList.toggle('overflow-hidden', open)"
     @scroll.window="scrolled = window.scrollY > 50"
     class="fixed inset-x-0 top-0 z-50 transition-all duration-300"
     :class="scrolled ? 'bg-white/95 backdrop-blur-md shadow-sm' : '@yield('navBg', 'bg-transparent')'">
    <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
        <a href="/" class="flex items-center gap-2">
            <img src="/images/LOGO.png" alt="Radar de Licitaciones" class="h-9 w-auto rounded">
            <span class="font-display hidden text-lg font-bold sm:inline"
                  :class="scrolled ? 'text-gray-900' : '@yield('logoText', 'text-white')'">Radar de Licitaciones</span>
        </a>

        <div class="hidden items-center gap-8 md:flex">
            <a href="/" class="text-sm font-medium transition-colors"
               :class="scrolled ? 'text-gray-600 hover:text-gray-900' : '@yield('navLink', 'text-blue-100 hover:text-white')'">Inicio</a>
            <a href="/precios" class="text-sm font-medium transition-colors"
               :class="scrolled ? 'text-gray-600 hover:text-gray-900' : '@yield('navLink', 'text-blue-100 hover:text-white')'">Precios</a>
            <a href="/#contacto" class="text-sm font-medium transition-colors"
               :class="scrolled ? 'text-gray-600 hover:text-gray-900' : '@yield('navLink', 'text-blue-100 hover:text-white')'">Contacto</a>
            <a href="/login" class="text-sm font-medium transition-colors"
               :class="scrolled ? 'text-gray-600 hover:text-gray-900' : '@yield('navLink', 'text-blue-100 hover:text-white')'">Iniciar sesión</a>
            <a href="{{ route('register.trial') }}"
               class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-md shadow-indigo-600/25 transition hover:bg-indigo-500">
                Prueba gratis
            </a>
        </div>

        <button @click="open = !open" class="-m-2 p-2 md:hidden"
                :class="scrolled ? 'text-gray-700' : '@yield('logoText', 'text-white')'">
            <svg class="size-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path x-show="!open" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path x-show="open" x-cloak d="M6 18 18 6M6 6l12 12" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </button>
    </div>

    <div x-show="open" x-cloak x-transition.opacity @click.away="open = false" class="border-t bg-white shadow-lg md:hidden">
        <div class="space-y-2 px-4 py-4">
            <a @click="open = false" href="/" class="block rounded-lg px-3 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50">Inicio</a>
            <a @click="open = false" href="/precios" class="block rounded-lg px-3 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50">Precios</a>
            <a @click="open = false" href="/#contacto" class="block rounded-lg px-3 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50">Contacto</a>
            <a @click="open = false" href="/login" class="block rounded-lg px-3 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50">Iniciar sesión</a>
            <a @click="open = false" href="{{ route('register.trial') }}" class="block rounded-lg bg-indigo-600 px-3 py-3 text-center text-sm font-semibold text-white shadow-md shadow-indigo-600/20">Prueba gratis</a>
        </div>
    </div>
</nav>
@endif
