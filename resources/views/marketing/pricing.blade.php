@extends('marketing.layout')

@section('title', 'Precios — Radar de Licitaciones')
@section('description', 'Plan profesional desde $45/mes. Monitoreo ilimitado, análisis con IA, alertas, espacio de ofertas.')
@section('navBg', 'bg-white/95 backdrop-blur-md shadow-sm')
@section('logoText', 'text-zinc-900')
@section('navLink', 'text-zinc-600 hover:text-zinc-900')

@section('content')

<section class="relative pt-32 pb-24 sm:pt-40 sm:pb-32">
    <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_70%_40%_at_50%_-10%,rgba(79,70,229,0.07),transparent)]"></div>
    <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-2xl text-center" data-animate>
            <p class="font-display text-sm font-semibold text-indigo-600">Precios</p>
            <h1 class="font-display mt-2 text-4xl font-bold tracking-tight text-zinc-900 sm:text-5xl">
                Precio simple, sin sorpresas
            </h1>
            <p class="mt-4 text-lg text-zinc-600">
                Un solo plan con todo incluido. Pague solo por lo que necesita.
            </p>
        </div>

        {{-- Pricing calculator --}}
        <div data-animate class="mx-auto mt-16 max-w-lg" x-data="{ companies: 1, users: 2, annual: false }">
            <div class="rounded-3xl bg-white/90 p-6 shadow-xl ring-1 ring-zinc-200/80 backdrop-blur-sm sm:p-8 md:p-10">
                {{-- Badge --}}
                <div class="flex items-center justify-between">
                    <div class="inline-flex items-center rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-800 ring-1 ring-indigo-200/80">
                        Plan Profesional
                    </div>
                    {{-- Billing toggle --}}
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-medium" :class="annual ? 'text-zinc-400' : 'text-zinc-900'">Mensual</span>
                        <button type="button" @click="annual = !annual"
                                class="relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out"
                                :class="annual ? 'bg-indigo-600' : 'bg-zinc-200'">
                            <span class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                                  :class="annual ? 'translate-x-4' : 'translate-x-0'"></span>
                        </button>
                        <span class="text-xs font-medium" :class="annual ? 'text-zinc-900' : 'text-zinc-400'">Anual</span>
                        <span x-show="annual" x-cloak class="inline-flex items-center rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-semibold text-indigo-800">-20%</span>
                    </div>
                </div>

                {{-- Price display --}}
                <div class="mt-6">
                    <div x-show="!annual" class="flex items-baseline gap-2">
                        <span class="font-display text-4xl font-extrabold text-zinc-900 sm:text-5xl">$<span x-text="45 + Math.max(0, companies - 1) * 20 + Math.max(0, users - 2) * 10"></span></span>
                        <span class="text-lg text-zinc-500">/mes</span>
                    </div>
                    <div x-show="annual" x-cloak class="flex items-baseline gap-2">
                        <span class="font-display text-4xl font-extrabold text-zinc-900 sm:text-5xl">$<span x-text="Math.round((45 + Math.max(0, companies - 1) * 20 + Math.max(0, users - 2) * 10) * 12 * 0.8 * 100) / 100"></span></span>
                        <span class="text-lg text-zinc-500">/año</span>
                    </div>
                    <p x-show="annual" x-cloak class="mt-1 text-sm text-indigo-600">
                        Ahorras $<span x-text="Math.round((45 + Math.max(0, companies - 1) * 20 + Math.max(0, users - 2) * 10) * 12 * 0.2 * 100) / 100"></span> al año
                    </p>
                </div>

                {{-- Breakdown --}}
                <div class="mt-2 space-y-0.5 text-sm text-zinc-500">
                    <p>Base: $45/mes <span class="text-zinc-400">(1 empresa, 2 usuarios)</span></p>
                    <p x-show="companies > 1" x-cloak>+ $<span x-text="(companies - 1) * 20"></span>/mes <span class="text-zinc-400">(<span x-text="companies - 1"></span> empresa<span x-show="companies > 2">s</span> extra)</span></p>
                    <p x-show="users > 2" x-cloak>+ $<span x-text="(users - 2) * 10"></span>/mes <span class="text-zinc-400">(<span x-text="users - 2"></span> usuario<span x-show="users > 3">s</span> extra)</span></p>
                </div>

                {{-- Sliders --}}
                <div class="mt-8 space-y-6">
                    <div>
                        <div class="flex items-center justify-between text-sm">
                            <label class="font-medium text-zinc-700">Empresas</label>
                            <span class="font-display font-semibold text-zinc-900" x-text="companies"></span>
                        </div>
                        <input type="range" min="1" max="5" x-model="companies"
                               class="mt-2 w-full cursor-pointer accent-indigo-600">
                        <div class="mt-1 flex justify-between text-xs text-zinc-400"><span>1</span><span>5</span></div>
                    </div>
                    <div>
                        <div class="flex items-center justify-between text-sm">
                            <label class="font-medium text-zinc-700">Usuarios</label>
                            <span class="font-display font-semibold text-zinc-900" x-text="users"></span>
                        </div>
                        <input type="range" min="2" max="15" x-model="users"
                               class="mt-2 w-full cursor-pointer accent-indigo-600">
                        <div class="mt-1 flex justify-between text-xs text-zinc-400"><span>2</span><span>15</span></div>
                    </div>
                </div>

                {{-- CTA --}}
                <a href="{{ route('register.trial') }}"
                   class="mt-8 block w-full rounded-xl bg-indigo-600 py-3.5 text-center text-sm font-semibold text-white shadow-md shadow-indigo-600/25 transition hover:bg-indigo-500">
                    Prueba gratis 7 días
                </a>
                <p class="mt-2 text-center text-xs text-zinc-500">
                    o <a href="{{ route('register.show') }}" class="font-medium text-indigo-600 hover:text-indigo-500">suscríbete ahora</a>
                </p>

                {{-- Included features --}}
                <ul class="mt-8 space-y-3 text-sm text-zinc-600">
                    @foreach([
                        'Monitoreo ilimitado de licitaciones',
                        'Análisis de pliegos con IA',
                        'Alertas por email y Telegram',
                        'Espacio de trabajo para ofertas',
                        'Pre-llenado de formularios RPE',
                        'Bóveda de documentos por empresa',
                        'Tablero Kanban de seguimiento',
                        'Calendario de vencimientos',
                        'Soporte por correo electrónico',
                    ] as $feature)
                    <li class="flex items-start gap-3">
                        <svg class="mt-0.5 size-5 shrink-0 text-indigo-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75"/>
                        </svg>
                        {{ $feature }}
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>

        {{-- Payment methods --}}
        <div data-animate class="mx-auto mt-12 max-w-lg text-center">
            <p class="text-sm font-medium text-zinc-700">Métodos de pago aceptados</p>
            <div class="mt-3 flex flex-wrap items-center justify-center gap-x-6 gap-y-3 text-xs text-zinc-500 sm:text-sm">
                <span class="flex items-center gap-1.5">
                    <svg class="size-5 text-blue-600" viewBox="0 0 24 24" fill="currentColor"><path d="M7.076 21.337H2.47a.641.641 0 0 1-.633-.74L4.944 3.72a.77.77 0 0 1 .757-.656h6.21c2.062 0 3.486.462 4.232 1.372.348.425.573.886.676 1.39.109.533.11 1.168.003 1.944l-.009.058v.515l.407.228c.345.182.619.39.826.627.347.396.57.893.665 1.477.098.6.065 1.298-.098 2.073-.188.894-.495 1.671-.914 2.31a4.702 4.702 0 0 1-1.417 1.473c-.544.363-1.19.635-1.92.807-.71.168-1.516.254-2.393.254H11.09a.956.956 0 0 0-.944.805l-.032.182-1.514 9.479Z"/></svg>
                    PayPal
                </span>
                <span class="flex items-center gap-1.5 text-zinc-400">
                    <svg class="size-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z"/></svg>
                    Azul <span class="text-xs">(próximamente)</span>
                </span>
                <span class="flex items-center gap-1.5">
                    <svg class="size-5 text-zinc-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0 0 12 9.75c-2.551 0-5.056.2-7.5.582V21"/></svg>
                    Transferencia bancaria
                </span>
            </div>
        </div>
    </div>
</section>

{{-- ═══ FAQ ═══ --}}
<section id="faq" class="border-t border-zinc-200/80 bg-zinc-50/80 py-24 sm:py-32">
    <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
        <h2 class="font-display text-center text-2xl font-bold text-zinc-900 sm:text-3xl" data-animate>Preguntas frecuentes</h2>

        <div class="mt-12 divide-y divide-zinc-200" x-data="{ open: null }">
            @php
            $faqs = [
                ['¿Qué son los rubros?', 'Son códigos UNSPSC de 8 dígitos que clasifican los bienes y servicios que usted ofrece. El sistema de la DGCP usa estos códigos para categorizar las licitaciones. Usted selecciona los que aplican a su empresa y nosotros le buscamos las oportunidades.'],
                ['¿Puedo agregar más empresas después?', 'Sí. Cada empresa adicional cuesta $20/mes. Puede agregar o remover empresas en cualquier momento desde su panel de facturación. Cada empresa tiene su propio espacio independiente con rubros, documentos y ofertas separadas.'],
                ['¿Cómo funciona el pago?', 'Aceptamos PayPal para pagos recurrentes automáticos y transferencia bancaria para pagos manuales. Con transferencia, sube su comprobante y lo confirmamos en horas laborables. Azul estará disponible próximamente.'],
                ['¿Puedo cancelar en cualquier momento?', 'Sí, sin penalidades ni compromisos de permanencia. Su acceso continúa hasta el final del período pagado.'],
                ['¿Qué licitaciones monitorean?', 'Todas las publicadas en el portal de Compras Dominicana (DGCP). Esto incluye comparaciones de precios, licitaciones públicas y restringidas, excepciones y cualquier otro proceso publicado en el sistema.'],
                ['¿Qué incluye el análisis con IA?', 'Nuestro sistema lee el documento del pliego completo y genera un resumen con los requisitos principales, montos estimados, plazos, documentos solicitados y condiciones especiales. Le ahorra horas de lectura.'],
            ];
            @endphp

            @foreach($faqs as $i => [$q, $a])
            <div data-animate>
                <button @click="open === {{ $i }} ? open = null : open = {{ $i }}"
                        class="flex w-full items-center justify-between py-5 text-left text-sm font-semibold text-zinc-900">
                    {{ $q }}
                    <svg class="size-5 shrink-0 text-zinc-400 transition-transform duration-200" :class="open === {{ $i }} && 'rotate-45'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                    </svg>
                </button>
                <div x-show="open === {{ $i }}" x-cloak x-collapse>
                    <p class="pb-5 text-sm leading-6 text-zinc-600">{{ $a }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ═══ CTA ═══ --}}
<section class="relative overflow-hidden bg-linear-to-br from-indigo-950 via-indigo-900 to-zinc-950 py-24 sm:py-32">
    <div class="absolute inset-0 opacity-[0.04]"
         style="background-image: radial-gradient(circle, #fff 1px, transparent 1px); background-size: 28px 28px;"></div>
    <div class="absolute inset-0 bg-[radial-gradient(ellipse_70%_50%_at_50%_0%,rgba(99,102,241,0.35),transparent)]"></div>
    <div class="relative mx-auto max-w-3xl px-4 text-center sm:px-6 lg:px-8" data-animate>
        <h2 class="font-display text-3xl font-bold tracking-tight text-white sm:text-4xl">
            Deje de perder oportunidades
        </h2>
        <p class="mt-4 text-lg text-indigo-100/90">
            Configure su radar en minutos y empiece a recibir licitaciones que coinciden con su perfil.
        </p>
        <a href="{{ route('register.trial') }}"
           class="mt-10 block w-full rounded-xl bg-white px-8 py-3.5 text-center text-sm font-semibold text-indigo-950 shadow-lg transition hover:bg-indigo-50 sm:inline-block sm:w-auto">
            Prueba gratis 7 días
        </a>
    </div>
</section>

@endsection
