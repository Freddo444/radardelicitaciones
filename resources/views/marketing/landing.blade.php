@extends('marketing.layout')

@section('title', 'Radar de Licitaciones — Monitoreo inteligente de licitaciones públicas RD')
@section('navBg', 'bg-transparent')
@section('logoText', 'text-white')
@section('navLink', 'text-blue-100 hover:text-white')

@section('content')

{{-- ═══ HERO ═══ --}}
<section class="relative min-h-[90vh] flex items-center overflow-hidden bg-slate-950">
    {{-- Background grid dots --}}
    <div class="absolute inset-0 opacity-[0.03]"
         style="background-image: radial-gradient(circle, #fff 1px, transparent 1px); background-size: 32px 32px;"></div>
    {{-- Gradient overlay --}}
    <div class="absolute inset-0 bg-gradient-to-br from-blue-950/80 via-slate-950 to-slate-950"></div>

    <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-32 lg:py-40">
        <div class="grid grid-cols-1 items-center gap-12 lg:grid-cols-2 lg:gap-16">
            {{-- Left: Copy --}}
            <div>
                <div class="inline-flex items-center gap-2 rounded-full border border-emerald-500/20 bg-emerald-500/10 px-3 py-1 text-xs font-medium text-emerald-400">
                    <span class="relative flex size-2">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex size-2 rounded-full bg-emerald-500"></span>
                    </span>
                    Monitoreando licitaciones en tiempo real
                </div>

                <h1 class="font-display mt-6 text-4xl font-extrabold tracking-tight text-white sm:text-5xl lg:text-6xl">
                    Gane más<br>
                    <span class="text-gradient">licitaciones públicas</span>
                </h1>

                <p class="mt-6 max-w-lg text-lg leading-8 text-slate-300">
                    Monitoreo automático del portal DGCP, análisis de pliegos con inteligencia artificial y herramientas completas para preparar ofertas ganadoras.
                </p>

                <div class="mt-10 flex flex-wrap gap-4">
                    <a href="/registro"
                       class="rounded-lg bg-emerald-500 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-emerald-500/25 transition hover:bg-emerald-400 hover:shadow-emerald-500/40">
                        Comenzar ahora
                    </a>
                    <a href="/precios"
                       class="rounded-lg border border-slate-600 px-6 py-3 text-sm font-semibold text-slate-200 transition hover:border-slate-400 hover:text-white">
                        Ver precios
                    </a>
                </div>

                <p class="mt-8 text-xs text-slate-500">
                    Cobertura del 100% de las licitaciones publicadas en Compras Dominicana
                </p>
            </div>

            {{-- Right: Radar visualization --}}
            <div class="relative mx-auto hidden lg:block" aria-hidden="true">
                <div class="relative h-[420px] w-[420px]">
                    {{-- Concentric circles --}}
                    <div class="absolute inset-0 rounded-full border border-white/[0.04]"></div>
                    <div class="absolute inset-10 rounded-full border border-white/[0.06]"></div>
                    <div class="absolute inset-20 rounded-full border border-white/[0.08]"></div>
                    <div class="absolute inset-[120px] rounded-full border border-white/[0.10]"></div>

                    {{-- Cross lines --}}
                    <div class="absolute inset-0 flex items-center"><div class="w-full h-px bg-white/[0.04]"></div></div>
                    <div class="absolute inset-0 flex justify-center"><div class="h-full w-px bg-white/[0.04]"></div></div>

                    {{-- Sweep --}}
                    <div class="absolute inset-0 rounded-full overflow-hidden">
                        <div class="radar-sweep absolute inset-0 origin-center">
                            <div class="absolute top-0 left-1/2 w-1/2 h-1/2 origin-bottom-left"
                                 style="background: conic-gradient(from -90deg at 0% 100%, transparent 0deg, rgba(16,185,129,0.25) 30deg, transparent 55deg)">
                            </div>
                        </div>
                    </div>

                    {{-- Center dot --}}
                    <div class="absolute inset-0 flex items-center justify-center">
                        <div class="relative h-3 w-3 rounded-full bg-emerald-500 shadow-lg shadow-emerald-500/50 live-dot"></div>
                    </div>

                    {{-- Blips on the radar --}}
                    <div class="absolute top-[25%] left-[62%] h-2 w-2 rounded-full bg-emerald-400/60 shadow-sm shadow-emerald-400/40"></div>
                    <div class="absolute top-[58%] left-[28%] h-1.5 w-1.5 rounded-full bg-emerald-400/40"></div>
                    <div class="absolute top-[35%] left-[35%] h-2 w-2 rounded-full bg-emerald-400/50 shadow-sm shadow-emerald-400/30"></div>

                    {{-- Floating notification cards --}}
                    <div class="float-card-1 absolute -top-2 -right-6">
                        <div class="rounded-xl border border-white/10 bg-white/[0.07] p-3 backdrop-blur-md shadow-xl">
                            <div class="flex items-center gap-2 text-xs font-semibold text-emerald-400">
                                <span class="flex h-1.5 w-1.5 rounded-full bg-emerald-400"></span> Nueva licitación
                            </div>
                            <p class="mt-1 text-xs text-white/80 max-w-[180px]">Construcción puente peatonal — Santiago</p>
                            <p class="mt-0.5 text-[10px] text-slate-500">DGCP-CCC-CP-2026-0023</p>
                        </div>
                    </div>

                    <div class="float-card-2 absolute bottom-8 -left-10">
                        <div class="rounded-xl border border-white/10 bg-white/[0.07] p-3 backdrop-blur-md shadow-xl">
                            <div class="flex items-center gap-2 text-xs font-semibold text-blue-400">
                                <span class="flex h-1.5 w-1.5 rounded-full bg-blue-400"></span> Pliego analizado
                            </div>
                            <p class="mt-1 text-xs text-white/80 max-w-[180px]">12 requisitos identificados</p>
                            <p class="mt-0.5 text-[10px] text-slate-500">Análisis completado en 8s</p>
                        </div>
                    </div>

                    <div class="float-card-3 absolute bottom-[35%] -right-12">
                        <div class="rounded-xl border border-white/10 bg-white/[0.07] p-3 backdrop-blur-md shadow-xl">
                            <div class="flex items-center gap-2 text-xs font-semibold text-amber-400">
                                <span class="flex h-1.5 w-1.5 rounded-full bg-amber-400"></span> Cierre próximo
                            </div>
                            <p class="mt-1 text-xs text-white/80 max-w-[180px]">Suministro mobiliario escolar</p>
                            <p class="mt-0.5 text-[10px] text-slate-500">Vence en 3 días</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ═══ STATS BAR ═══ --}}
<section class="relative z-10 -mt-8">
    <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
        <div data-animate class="grid grid-cols-1 gap-px overflow-hidden rounded-2xl bg-gray-200 shadow-lg sm:grid-cols-3">
            <div class="bg-white px-6 py-8 text-center">
                <p class="font-display text-3xl font-bold text-gray-900">500+</p>
                <p class="mt-1 text-sm text-gray-500">Licitaciones nuevas cada mes</p>
            </div>
            <div class="bg-white px-6 py-8 text-center">
                <p class="font-display text-3xl font-bold text-gray-900">200+</p>
                <p class="mt-1 text-sm text-gray-500">Instituciones monitoreadas</p>
            </div>
            <div class="bg-white px-6 py-8 text-center">
                <p class="font-display text-3xl font-bold text-gray-900">1,000+</p>
                <p class="mt-1 text-sm text-gray-500">Rubros UNSPSC disponibles</p>
            </div>
        </div>
    </div>
</section>

{{-- ═══ FEATURES ═══ --}}
<section class="py-24 sm:py-32 bg-slate-50/50">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-2xl text-center" data-animate>
            <p class="font-display text-sm font-semibold text-emerald-600">Todo lo que necesita</p>
            <h2 class="font-display mt-2 text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">
                De la publicación a la oferta, en un solo lugar
            </h2>
            <p class="mt-4 text-lg text-gray-600">
                Herramientas diseñadas para empresas que licitan con el Estado dominicano.
            </p>
        </div>

        <div class="mx-auto mt-16 grid max-w-5xl grid-cols-1 gap-6 sm:grid-cols-2">
            {{-- Feature 1 --}}
            <div data-animate data-delay="1"
                 class="group relative rounded-2xl bg-white p-8 shadow-sm ring-1 ring-gray-200 transition-all hover:shadow-md hover:ring-emerald-200">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-800">
                        <svg class="size-5 text-white" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.348 14.652a3.75 3.75 0 0 1 0-5.304m5.304 0a3.75 3.75 0 0 1 0 5.304m-7.425 2.121a6.75 6.75 0 0 1 0-9.546m9.546 0a6.75 6.75 0 0 1 0 9.546M5.106 18.894c-3.808-3.807-3.808-9.98 0-13.788m13.788 0c3.808 3.807 3.808 9.98 0 13.788M12 12h.008v.008H12V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/>
                        </svg>
                    </div>
                    <h3 class="font-display text-lg font-semibold text-gray-900">Monitoreo 24/7</h3>
                    <span class="relative flex h-2 w-2 live-dot">
                        <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
                    </span>
                </div>
                <p class="mt-4 text-sm leading-6 text-gray-600">
                    Vigilamos el portal de la DGCP las 24 horas. Le avisamos por correo electrónico y Telegram cuando aparezcan licitaciones que coincidan con sus rubros.
                </p>
            </div>

            {{-- Feature 2 --}}
            <div data-animate data-delay="2"
                 class="group relative rounded-2xl bg-white p-8 shadow-sm ring-1 ring-gray-200 transition-all hover:shadow-md hover:ring-emerald-200">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-800">
                        <svg class="size-5 text-white" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456Z"/>
                        </svg>
                    </div>
                    <h3 class="font-display text-lg font-semibold text-gray-900">Análisis de pliegos con IA</h3>
                </div>
                <p class="mt-4 text-sm leading-6 text-gray-600">
                    La inteligencia artificial lee los documentos del pliego en segundos. Identifica requisitos, montos, plazos y condiciones para que usted decida rápido si participar.
                </p>
            </div>

            {{-- Feature 3 --}}
            <div data-animate data-delay="3"
                 class="group relative rounded-2xl bg-white p-8 shadow-sm ring-1 ring-gray-200 transition-all hover:shadow-md hover:ring-emerald-200">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-800">
                        <svg class="size-5 text-white" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
                        </svg>
                    </div>
                    <h3 class="font-display text-lg font-semibold text-gray-900">Espacio para ofertas</h3>
                </div>
                <p class="mt-4 text-sm leading-6 text-gray-600">
                    Organice toda su documentación: personal clave, equipos, proyectos ejecutados, estados financieros y documentos legales. Todo listo cuando necesite armar una oferta.
                </p>
            </div>

            {{-- Feature 4 --}}
            <div data-animate data-delay="4"
                 class="group relative rounded-2xl bg-white p-8 shadow-sm ring-1 ring-gray-200 transition-all hover:shadow-md hover:ring-emerald-200">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-800">
                        <svg class="size-5 text-white" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 0 0-1.883 2.542l.857 6a2.25 2.25 0 0 0 2.227 1.932H19.05a2.25 2.25 0 0 0 2.227-1.932l.857-6a2.25 2.25 0 0 0-1.883-2.542m-16.5 0V6A2.25 2.25 0 0 1 6 3.75h3.879a1.5 1.5 0 0 1 1.06.44l2.122 2.12a1.5 1.5 0 0 0 1.06.44H18A2.25 2.25 0 0 1 20.25 9v.776"/>
                        </svg>
                    </div>
                    <h3 class="font-display text-lg font-semibold text-gray-900">Formularios pre-llenados</h3>
                </div>
                <p class="mt-4 text-sm leading-6 text-gray-600">
                    Los formularios RPE y documentos estándar se completan automáticamente con los datos de su empresa. Menos errores, menos tiempo, más ofertas presentadas.
                </p>
            </div>
        </div>
    </div>
</section>

{{-- ═══ HOW IT WORKS ═══ --}}
<section class="py-24 sm:py-32">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-2xl text-center" data-animate>
            <p class="font-display text-sm font-semibold text-emerald-600">Fácil de empezar</p>
            <h2 class="font-display mt-2 text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">
                Tres pasos para no perder oportunidades
            </h2>
        </div>

        <div class="mx-auto mt-16 max-w-3xl">
            <div class="relative space-y-12 pl-10 sm:pl-16">
                {{-- Vertical line --}}
                <div class="absolute left-[18px] sm:left-[26px] top-2 bottom-2 w-px bg-gradient-to-b from-blue-800 via-emerald-500 to-emerald-300"></div>

                <div data-animate data-delay="1" class="relative">
                    <div class="absolute -left-10 sm:-left-16 flex h-9 w-9 sm:h-[52px] sm:w-[52px] items-center justify-center rounded-full bg-blue-800 font-display text-sm sm:text-lg font-bold text-white shadow-lg">1</div>
                    <h3 class="font-display text-lg font-semibold text-gray-900">Registre su empresa</h3>
                    <p class="mt-2 text-sm leading-6 text-gray-600">Cree su cuenta e ingrese los datos básicos: RNC, razón social y contacto. En menos de 5 minutos estará configurado.</p>
                </div>

                <div data-animate data-delay="2" class="relative">
                    <div class="absolute -left-10 sm:-left-16 flex h-9 w-9 sm:h-[52px] sm:w-[52px] items-center justify-center rounded-full bg-emerald-600 font-display text-sm sm:text-lg font-bold text-white shadow-lg">2</div>
                    <h3 class="font-display text-lg font-semibold text-gray-900">Seleccione sus rubros</h3>
                    <p class="mt-2 text-sm leading-6 text-gray-600">Escoja los códigos UNSPSC de los bienes y servicios que su empresa ofrece. Así sabremos qué licitaciones buscarle.</p>
                </div>

                <div data-animate data-delay="3" class="relative">
                    <div class="absolute -left-10 sm:-left-16 flex h-9 w-9 sm:h-[52px] sm:w-[52px] items-center justify-center rounded-full bg-emerald-500 font-display text-sm sm:text-lg font-bold text-white shadow-lg">3</div>
                    <h3 class="font-display text-lg font-semibold text-gray-900">Reciba oportunidades</h3>
                    <p class="mt-2 text-sm leading-6 text-gray-600">Le notificamos cada licitación que coincida. Analice el pliego con IA, prepare su oferta con nuestras herramientas y presente.</p>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ═══ INDUSTRIES ═══ --}}
<section class="py-24 sm:py-32 bg-slate-50/50">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-2xl text-center" data-animate>
            <h2 class="font-display text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">
                ¿Para quién es?
            </h2>
            <p class="mt-4 text-lg text-gray-600">
                Para toda empresa que participe o quiera participar en compras y contrataciones públicas.
            </p>
        </div>
        <div data-animate class="mx-auto mt-12 flex max-w-3xl flex-wrap justify-center gap-3">
            @foreach(['Construcción y obras civiles', 'Ingeniería y consultoría', 'Suministros y materiales', 'Tecnología y servicios', 'Alimentos y catering', 'Mobiliario y equipos', 'Servicios profesionales', 'Salud y farmacéutica'] as $industry)
            <span class="rounded-full border border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 shadow-sm">{{ $industry }}</span>
            @endforeach
        </div>
    </div>
</section>

{{-- ═══ FAQ ═══ --}}
<section class="py-24 sm:py-32">
    <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
        <h2 class="font-display text-center text-2xl font-bold text-gray-900 sm:text-3xl" data-animate>Preguntas frecuentes</h2>

        <div class="mt-12 divide-y divide-gray-200" x-data="{ open: null }">
            @php
            $faqs = [
                ['¿Cómo saben qué licitaciones me interesan?', 'Usted selecciona los códigos UNSPSC (rubros) de los bienes y servicios que ofrece su empresa. El sistema cruza automáticamente cada nueva licitación con sus rubros y le notifica cuando hay coincidencia.'],
                ['¿Es seguro subir mis documentos?', 'Sí. Sus documentos se almacenan en servidores seguros con conexión encriptada. Cada empresa tiene un espacio aislado — ningún otro usuario puede acceder a sus datos.'],
                ['¿Puedo cancelar en cualquier momento?', 'Sí, sin penalidades. Su acceso continúa hasta el final del período pagado. Puede cancelar desde su panel de facturación.'],
                ['¿Qué incluye el análisis de pliegos con IA?', 'La inteligencia artificial lee el documento del pliego completo y genera un resumen con requisitos, montos, plazos, documentos solicitados y condiciones especiales. Le ahorra horas de lectura.'],
            ];
            @endphp

            @foreach($faqs as $i => [$q, $a])
            <div data-animate>
                <button @click="open === {{ $i }} ? open = null : open = {{ $i }}"
                        class="flex w-full items-center justify-between py-5 text-left text-sm font-semibold text-gray-900">
                    {{ $q }}
                    <svg class="size-5 shrink-0 text-gray-400 transition-transform duration-200" :class="open === {{ $i }} && 'rotate-45'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                    </svg>
                </button>
                <div x-show="open === {{ $i }}" x-cloak x-collapse>
                    <p class="pb-5 text-sm leading-6 text-gray-600">{{ $a }}</p>
                </div>
            </div>
            @endforeach

            <div class="pt-6 text-center">
                <a href="/precios#faq" class="text-sm font-medium text-emerald-600 hover:text-emerald-500">Ver más preguntas frecuentes &rarr;</a>
            </div>
        </div>
    </div>
</section>

{{-- ═══ CONTACT ═══ --}}
<section id="contacto" class="py-24 sm:py-32">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-2xl" data-animate>
            <div class="text-center">
                <p class="font-display text-sm font-semibold text-emerald-600">Contáctenos</p>
                <h2 class="font-display mt-2 text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">
                    ¿Tiene preguntas? Escríbanos
                </h2>
                <p class="mt-4 text-lg text-gray-600">
                    Responderemos a la brevedad posible.
                </p>
            </div>

            @if(session('contact_sent'))
            <div class="mt-8 rounded-xl border border-emerald-200 bg-emerald-50 p-6 text-center">
                <svg class="mx-auto size-10 text-emerald-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                </svg>
                <p class="mt-3 font-display text-lg font-semibold text-emerald-800">Mensaje enviado</p>
                <p class="mt-1 text-sm text-emerald-700">Gracias por contactarnos. Le responderemos pronto.</p>
            </div>
            @endif

            <form method="POST" action="{{ route('contact.store') }}" class="mt-10 space-y-6">
                @csrf
                {{-- Honeypot --}}
                <div class="hidden" aria-hidden="true">
                    <input type="text" name="website" tabindex="-1" autocomplete="off">
                </div>
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <label for="contact-name" class="block text-sm font-medium text-gray-700">Nombre</label>
                        <input type="text" name="name" id="contact-name" required
                               value="{{ old('name') }}"
                               class="mt-1 block w-full rounded-lg border border-gray-300 px-4 py-3 text-gray-900 shadow-sm transition focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm"
                               placeholder="Su nombre">
                        @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="contact-email" class="block text-sm font-medium text-gray-700">Correo electrónico</label>
                        <input type="email" name="email" id="contact-email" required
                               value="{{ old('email') }}"
                               class="mt-1 block w-full rounded-lg border border-gray-300 px-4 py-3 text-gray-900 shadow-sm transition focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm"
                               placeholder="correo@ejemplo.com">
                        @error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div>
                    <label for="contact-message" class="block text-sm font-medium text-gray-700">Mensaje</label>
                    <textarea name="message" id="contact-message" rows="5" required
                              class="mt-1 block w-full rounded-lg border border-gray-300 px-4 py-3 text-gray-900 shadow-sm transition focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm"
                              placeholder="¿En qué podemos ayudarle?">{{ old('message') }}</textarea>
                    @error('message')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="text-center">
                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-lg bg-emerald-500 px-8 py-3.5 text-sm font-semibold text-white shadow-lg shadow-emerald-500/25 transition hover:bg-emerald-400 hover:shadow-emerald-500/40">
                        <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5"/>
                        </svg>
                        Enviar mensaje
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>

{{-- ═══ CTA ═══ --}}
<section class="relative overflow-hidden bg-blue-800 py-24 sm:py-32">
    <div class="absolute inset-0 opacity-[0.03]"
         style="background-image: radial-gradient(circle, #fff 1px, transparent 1px); background-size: 32px 32px;"></div>
    <div class="relative mx-auto max-w-3xl px-4 text-center sm:px-6 lg:px-8" data-animate>
        <h2 class="font-display text-3xl font-bold tracking-tight text-white sm:text-4xl">
            Empiece a ganar más licitaciones hoy
        </h2>
        <p class="mt-4 text-lg text-blue-100">
            Regístrese y configure su empresa en minutos. Sin compromisos de permanencia.
        </p>
        <div class="mt-10 flex flex-wrap justify-center gap-4">
            <a href="/registro"
               class="rounded-lg bg-emerald-500 px-8 py-3.5 text-sm font-semibold text-white shadow-lg shadow-emerald-500/25 transition hover:bg-emerald-400 hover:shadow-emerald-500/40">
                Crear cuenta
            </a>
            <a href="/precios"
               class="rounded-lg border border-blue-400/30 px-8 py-3.5 text-sm font-semibold text-white transition hover:border-blue-300/50 hover:bg-blue-700/50">
                Ver precios
            </a>
        </div>
    </div>
</section>

@endsection
