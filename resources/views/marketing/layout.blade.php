<!DOCTYPE html>
<html lang="es" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Radar de Licitaciones — Monitoreo de licitaciones públicas RD')</title>
    <meta name="description" content="@yield('description', 'Monitoreo en tiempo real de licitaciones de la DGCP, análisis de pliegos con IA y herramientas para preparar ofertas ganadoras.')">

    {{-- Open Graph --}}
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="@yield('title', 'Radar de Licitaciones — Monitoreo de licitaciones públicas RD')">
    <meta property="og:description" content="@yield('description', 'Monitoreo en tiempo real de licitaciones de la DGCP, análisis de pliegos con IA y herramientas para preparar ofertas ganadoras.')">
    <meta property="og:image" content="{{ asset('images/og-image.png') }}">
    <meta property="og:locale" content="es_DO">
    <meta property="og:site_name" content="Radar de Licitaciones">

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('title', 'Radar de Licitaciones')">
    <meta name="twitter:description" content="@yield('description', 'Monitoreo en tiempo real de licitaciones de la DGCP.')">
    <meta name="twitter:image" content="{{ asset('images/og-image.png') }}">

    <link rel="apple-touch-icon" sizes="180x180" href="/images/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/images/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/images/favicon-16x16.png">
    <link rel="shortcut icon" href="/favicon.ico">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=DM+Sans:ital,wght@0,400;0,500;0,600;1,400&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
    <style>
        :root {
            --radar-dark: #020617;
            --radar-ink: #0f172a;
            --radar-accent: #4f46e5;
            --radar-mint: #10b981;
        }
        body { font-family: 'DM Sans', system-ui, sans-serif; }
        .font-display { font-family: 'Sora', system-ui, sans-serif; }

        .marketing-hero-glow {
            background: radial-gradient(ellipse 90% 60% at 50% -30%, rgba(79, 70, 229, 0.35), transparent 55%),
                radial-gradient(ellipse 50% 40% at 100% 20%, rgba(16, 185, 129, 0.12), transparent 50%);
        }
        .marketing-noise {
            opacity: 0.035;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.85' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
        }

        /* Radar sweep animation */
        @keyframes sweep {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .radar-sweep {
            animation: sweep 4s linear infinite;
        }

        /* Live pulse dot */
        @keyframes pulse-ring {
            0% { transform: scale(1); opacity: 1; }
            100% { transform: scale(2.2); opacity: 0; }
        }
        .live-dot::after {
            content: '';
            position: absolute;
            inset: -2px;
            border-radius: 9999px;
            border: 2px solid #10b981;
            animation: pulse-ring 2s ease-out infinite;
        }

        /* Floating notification cards */
        @keyframes float-1 {
            0%, 100% { opacity: 0; transform: translateY(10px); }
            15%, 85% { opacity: 1; transform: translateY(0); }
        }
        @keyframes float-2 {
            0%, 100% { opacity: 0; transform: translateY(10px); }
            15%, 85% { opacity: 1; transform: translateY(0); }
        }
        .float-card-1 { animation: float-1 5s ease-in-out infinite; }
        .float-card-2 { animation: float-2 5s ease-in-out 2.5s infinite; opacity: 0; }
        .float-card-3 { animation: float-1 6s ease-in-out 1.2s infinite; opacity: 0; }

        /* Scroll reveal */
        [data-animate] {
            opacity: 0;
            transform: translateY(24px);
            transition: opacity 0.7s cubic-bezier(.22,.61,.36,1), transform 0.7s cubic-bezier(.22,.61,.36,1);
        }
        [data-animate].is-visible {
            opacity: 1;
            transform: translateY(0);
        }
        [data-animate][data-delay="1"] { transition-delay: 0.1s; }
        [data-animate][data-delay="2"] { transition-delay: 0.2s; }
        [data-animate][data-delay="3"] { transition-delay: 0.3s; }
        [data-animate][data-delay="4"] { transition-delay: 0.4s; }

        .text-gradient {
            background: linear-gradient(120deg, #a5b4fc 0%, #34d399 45%, #6ee7b7 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
    </style>
    <x-umami-script />
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-white text-zinc-700 antialiased overflow-x-hidden selection:bg-indigo-100 selection:text-indigo-950">
@php
    $supportEmail = config('services.support.email', 'info@radardelicitaciones.com');
    $supportPhone = config('services.support.phone', '');
    $addressLine = config('services.support.address_line', '');
    $city = config('services.support.city', 'Santo Domingo');
    $country = config('services.support.country', 'República Dominicana');
@endphp

{{-- Navbar --}}
<nav x-data="{ scrolled: false, open: false }"
     x-effect="document.body.classList.toggle('overflow-hidden', open)"
     @scroll.window="scrolled = window.scrollY > 50"
     class="fixed inset-x-0 top-0 z-50 transition-all duration-300"
     :class="scrolled ? 'bg-white/95 backdrop-blur-md shadow-sm' : '@yield('navBg', 'bg-transparent')'">
    <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
        <a href="/" class="flex items-center gap-2">
            <img src="/images/LOGO.png" alt="Radar de Licitaciones" class="h-9 w-auto rounded">
            <span class="font-display text-lg font-bold hidden sm:inline"
                  :class="scrolled ? 'text-gray-900' : '@yield('logoText', 'text-white')'">Radar de Licitaciones</span>
        </a>

        <div class="hidden md:flex items-center gap-8">
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

        {{-- Mobile hamburger --}}
        <button @click="open = !open" class="md:hidden -m-2 p-2"
                :class="scrolled ? 'text-gray-700' : '@yield('logoText', 'text-white')'">
            <svg class="size-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path x-show="!open" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" stroke-linecap="round" stroke-linejoin="round"/>
                <path x-show="open" x-cloak d="M6 18 18 6M6 6l12 12" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </button>
    </div>

    {{-- Mobile menu --}}
    <div x-show="open" x-cloak x-transition.opacity @click.away="open = false" class="md:hidden bg-white border-t shadow-lg">
        <div class="px-4 py-4 space-y-2">
            <a @click="open = false" href="/" class="block rounded-lg px-3 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50">Inicio</a>
            <a @click="open = false" href="/precios" class="block rounded-lg px-3 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50">Precios</a>
            <a @click="open = false" href="/#contacto" class="block rounded-lg px-3 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50">Contacto</a>
            <a @click="open = false" href="/login" class="block rounded-lg px-3 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50">Iniciar sesión</a>
            <a @click="open = false" href="{{ route('register.trial') }}" class="block rounded-lg bg-indigo-600 px-3 py-3 text-center text-sm font-semibold text-white shadow-md shadow-indigo-600/20">Prueba gratis</a>
        </div>
    </div>
</nav>

@yield('content')

{{-- Footer --}}
<footer class="relative z-10 bg-zinc-950 text-zinc-400">
    <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 gap-8 md:grid-cols-3">
            <div>
                <img src="/images/LOGO.png" alt="Radar de Licitaciones" class="h-10 w-auto rounded">
                <p class="mt-4 text-sm leading-6">Monitoreo inteligente de licitaciones públicas de la República Dominicana.</p>
            </div>
            <div>
                <h4 class="font-display text-sm font-semibold text-white">Plataforma</h4>
                <ul class="mt-4 space-y-1 text-sm">
                    <li><a href="/precios" class="inline-block py-1 hover:text-white transition-colors">Precios</a></li>
                    <li><a href="/registro" class="inline-block py-1 hover:text-white transition-colors">Crear cuenta</a></li>
                    <li><a href="/login" class="inline-block py-1 hover:text-white transition-colors">Iniciar sesión</a></li>
                    <li><a href="/terminos" class="inline-block py-1 hover:text-white transition-colors">Términos de servicio</a></li>
                    <li><a href="/privacidad" class="inline-block py-1 hover:text-white transition-colors">Política de privacidad</a></li>
                    <li><a href="/politicas-pago-seguridad" class="inline-block py-1 hover:text-white transition-colors">Políticas de pago y seguridad</a></li>
                </ul>
            </div>
            <div>
                <h4 class="font-display text-sm font-semibold text-white">Contacto</h4>
                <ul class="mt-4 space-y-1 text-sm">
                    <li><a href="/#contacto" class="inline-block py-1 hover:text-white transition-colors">Formulario de contacto</a></li>
                    <li><a href="mailto:{{ $supportEmail }}" class="inline-block py-1 hover:text-white transition-colors">{{ $supportEmail }}</a></li>
                    <li><a href="tel:{{ preg_replace('/\s+/', '', $supportPhone) }}" class="inline-block py-1 hover:text-white transition-colors">{{ $supportPhone }}</a></li>
                    <li>{{ trim(($addressLine ? $addressLine.', ' : '').$city.', '.$country, ', ') }}</li>
                </ul>
            </div>
        </div>
        <div class="mt-8 flex flex-wrap items-center gap-3 text-[11px] text-zinc-300">
            <img src="{{ asset('images/payments/visa.png') }}" alt="Visa" class="h-5 w-auto rounded bg-white p-0.5">
            <img src="{{ asset('images/payments/mastercard.png') }}" alt="Mastercard" class="h-6 w-auto rounded bg-white p-0.5">
            <img src="{{ asset('images/payments/verified-by-visa.png') }}" alt="Verified by Visa" class="h-7 w-auto rounded bg-white p-0.5">
            <img src="{{ asset('images/payments/mastercard-id-check.png') }}" alt="Mastercard ID Check" class="h-7 w-auto rounded bg-white p-0.5">
        </div>
        <div class="mt-12 border-t border-zinc-800/80 pt-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs">
            <span>&copy; {{ date('Y') }} Radar de Licitaciones. Todos los derechos reservados.</span>
            <div class="flex gap-6">
                <a href="/terminos" class="py-2 hover:text-white transition-colors">Términos de servicio</a>
                <a href="/privacidad" class="py-2 hover:text-white transition-colors">Política de privacidad</a>
                <a href="/politicas-pago-seguridad" class="py-2 hover:text-white transition-colors">Pago y seguridad</a>
            </div>
        </div>
    </div>
</footer>

{{-- WhatsApp floating button (hidden when Tawk.to is active) --}}
@unless(config('services.tawkto.widget_url'))
<a href="https://wa.me/{{ config('services.whatsapp.number', '18095551234') }}?text={{ urlencode('Hola, me interesa Radar de Licitaciones.') }}"
   target="_blank" rel="noopener"
   class="fixed bottom-4 right-4 z-40 flex h-12 w-12 items-center justify-center rounded-full bg-[#25D366] text-white shadow-lg transition-transform hover:scale-110 sm:bottom-6 sm:right-6 sm:h-14 sm:w-14"
   aria-label="Contáctenos por WhatsApp">
    <svg class="size-6 sm:size-7" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z"/></svg>
</a>
@endunless

<x-tawk-script />

<script>
document.addEventListener('DOMContentLoaded', () => {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(e => {
            if (e.isIntersecting) { e.target.classList.add('is-visible'); observer.unobserve(e.target); }
        });
    }, { threshold: 0.08, rootMargin: '0px 0px -40px 0px' });
    document.querySelectorAll('[data-animate]').forEach(el => observer.observe(el));
});
</script>
<x-umami-track />
</body>
</html>
