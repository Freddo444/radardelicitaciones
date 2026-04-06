@extends('layouts.app')
@section('title', 'Configuración')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:flex lg:gap-x-16 lg:px-8">

    {{-- Sidebar nav --}}
    <aside class="flex overflow-x-auto border-b border-gray-900/5 pb-4 lg:block lg:w-64 lg:flex-none lg:border-0 lg:pb-0">
        <nav class="flex-none px-0 sm:px-0">
            <ul role="list" class="flex gap-x-3 gap-y-1 whitespace-nowrap lg:flex-col">
                <li>
                    <a href="#api" class="group flex gap-x-3 rounded-md bg-gray-50 py-2 pr-3 pl-2 text-sm/6 font-semibold text-blue-600">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" class="size-6 shrink-0 text-blue-600">
                            <path d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        API DGCP
                    </a>
                </li>
                <li>
                    <a href="#sondeo" class="group flex gap-x-3 rounded-md py-2 pr-3 pl-2 text-sm/6 font-semibold text-gray-700 hover:bg-gray-50 hover:text-blue-600">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" class="size-6 shrink-0 text-gray-400 group-hover:text-blue-600">
                            <path d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        Sondeo
                    </a>
                </li>
                <li>
                    <a href="#email" class="group flex gap-x-3 rounded-md py-2 pr-3 pl-2 text-sm/6 font-semibold text-gray-700 hover:bg-gray-50 hover:text-blue-600">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" class="size-6 shrink-0 text-gray-400 group-hover:text-blue-600">
                            <path d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        Email
                    </a>
                </li>
                <li>
                    <a href="#telegram" class="group flex gap-x-3 rounded-md py-2 pr-3 pl-2 text-sm/6 font-semibold text-gray-700 hover:bg-gray-50 hover:text-blue-600">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" class="size-6 shrink-0 text-gray-400 group-hover:text-blue-600">
                            <path d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        Telegram
                    </a>
                </li>
                <li>
                    <a href="#notificaciones" class="group flex gap-x-3 rounded-md py-2 pr-3 pl-2 text-sm/6 font-semibold text-gray-700 hover:bg-gray-50 hover:text-blue-600">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" class="size-6 shrink-0 text-gray-400 group-hover:text-blue-600">
                            <path d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        Notificaciones
                    </a>
                </li>
                <li>
                    <a href="#filtros" class="group flex gap-x-3 rounded-md py-2 pr-3 pl-2 text-sm/6 font-semibold text-gray-700 hover:bg-gray-50 hover:text-blue-600">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" class="size-6 shrink-0 text-gray-400 group-hover:text-blue-600">
                            <path d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        Filtros
                    </a>
                </li>
                <li>
                    <a href="#suscripcion" class="group flex gap-x-3 rounded-md py-2 pr-3 pl-2 text-sm/6 font-semibold text-gray-700 hover:bg-gray-50 hover:text-blue-600">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" class="size-6 shrink-0 text-gray-400 group-hover:text-blue-600">
                            <path d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        Suscripci&oacute;n
                    </a>
                </li>
            </ul>
        </nav>
    </aside>

    {{-- Main content --}}
    <main class="flex-1 px-0 lg:px-0">
        {{-- Standalone test forms (outside main form to avoid nesting) --}}
        <form id="form-test-connection" method="POST" action="{{ route('settings.test-connection') }}">@csrf</form>
        <form id="form-test-email"      method="POST" action="{{ route('settings.test-email') }}">@csrf</form>
        <form id="form-test-telegram"   method="POST" action="{{ route('settings.test-telegram') }}">@csrf</form>

        <form method="POST" action="{{ route('settings.update') }}">
            @csrf

            <div class="space-y-16">

                {{-- API DGCP --}}
                <div id="api">
                    <h2 class="text-base/7 font-semibold text-gray-900">API DGCP</h2>
                    <p class="mt-1 text-sm/6 text-gray-500">Conexión con el portal de datos abiertos de la DGCP. No requiere credenciales.</p>

                    <dl class="mt-6 divide-y divide-gray-100 border-t border-gray-200 text-sm/6">
                        <div class="py-6 sm:flex sm:items-center">
                            <dt class="font-medium text-gray-900 sm:w-64 sm:flex-none sm:pr-6">
                                Catálogo UNSPSC
                                <p class="mt-1 text-xs font-normal text-gray-500">
                                    {{ $settings['catalog_item_count'] ? number_format($settings['catalog_item_count']) . ' códigos' : 'No importado' }}
                                    @if($settings['catalog_last_imported_at'])
                                        — actualizado {{ \Carbon\Carbon::parse($settings['catalog_last_imported_at'])->format('d/m/Y H:i') }}
                                    @endif
                                </p>
                            </dt>
                            <dd class="mt-1 text-xs text-gray-500 sm:mt-0 sm:flex-auto">
                                Se actualiza automáticamente cada mes
                            </dd>
                        </div>
                        <div class="py-6 sm:flex sm:items-center">
                            <dt class="font-medium text-gray-900 sm:w-64 sm:flex-none sm:pr-6">Estado de la conexión</dt>
                            <dd class="mt-1 sm:mt-0 sm:flex-auto">
                                <button type="submit" form="form-test-connection" class="rounded-md bg-white px-2.5 py-1.5 text-sm font-semibold text-gray-900 shadow-xs inset-ring inset-ring-gray-300 hover:bg-gray-50">
                                    Probar conexión
                                </button>
                            </dd>
                        </div>
                    </dl>
                </div>

                {{-- Sondeo --}}
                <div id="sondeo">
                    <h2 class="text-base/7 font-semibold text-gray-900">Sondeo</h2>
                    <p class="mt-1 text-sm/6 text-gray-500">El sistema monitorea la DGCP automáticamente cada hora y escanea el portal cada 15 minutos.</p>

                    <dl class="mt-6 divide-y divide-gray-100 border-t border-gray-200 text-sm/6">
                        <div class="py-6 sm:flex sm:items-center">
                            <dt class="font-medium text-gray-900 sm:w-64 sm:flex-none sm:pr-6">Último sondeo</dt>
                            <dd class="mt-1 sm:mt-0 sm:flex-auto">
                                <span class="text-[#6b7280]">{{ $settings['last_polled_at'] ? \Carbon\Carbon::parse($settings['last_polled_at'])->format('d/m/Y H:i:s') : 'Nunca' }}</span>
                            </dd>
                        </div>
                    </dl>
                </div>

                {{-- Email --}}
                <div id="email">
                    <h2 class="text-base/7 font-semibold text-gray-900">Email</h2>
                    <p class="mt-1 text-sm/6 text-gray-500">Correo electrónico donde se recibirán alertas cuando una convocatoria vigilada cambie.</p>

                    <dl class="mt-6 divide-y divide-gray-100 border-t border-gray-200 text-sm/6">
                        <div class="py-6 sm:flex sm:items-center">
                            <dt class="font-medium text-gray-900 sm:w-64 sm:flex-none sm:pr-6">Correo de destino</dt>
                            <dd class="mt-1 sm:mt-0 sm:flex-auto">
                                <input type="email" name="notification_email" id="notification_email"
                                       value="{{ $settings['notification_email'] }}"
                                       placeholder="tu@correo.com"
                                       class="w-64 rounded-md bg-white px-3 py-1.5 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600"/>
                            </dd>
                        </div>
                        <div class="py-6 sm:flex sm:items-center">
                            <dt class="font-medium text-gray-900 sm:w-64 sm:flex-none sm:pr-6">Prueba</dt>
                            <dd class="mt-1 sm:mt-0 sm:flex-auto">
                                <button type="submit" form="form-test-email" class="rounded-md bg-white px-2.5 py-1.5 text-sm font-semibold text-gray-900 shadow-xs inset-ring inset-ring-gray-300 hover:bg-gray-50">
                                    Enviar correo de prueba
                                </button>
                            </dd>
                        </div>
                    </dl>
                </div>

                {{-- Telegram --}}
                <div id="telegram">
                    <h2 class="text-base/7 font-semibold text-gray-900">Telegram</h2>
                    <p class="mt-1 text-sm/6 text-gray-500">Bot de Telegram para recibir alertas de convocatorias vigiladas. Crea un bot en @BotFather y obtén tu Chat ID con @userinfobot.</p>

                    <dl class="mt-6 divide-y divide-gray-100 border-t border-gray-200 text-sm/6">
                        <div class="py-6 sm:flex sm:items-center">
                            <dt class="font-medium text-gray-900 sm:w-64 sm:flex-none sm:pr-6">Bot Token</dt>
                            <dd class="mt-1 sm:mt-0 sm:flex-auto">
                                <div x-data="{ showToken: false }" class="flex items-center gap-2">
                                    <input :type="showToken ? 'text' : 'password'" name="telegram_bot_token" id="telegram_bot_token"
                                           value="{{ $settings['telegram_bot_token'] }}"
                                           placeholder="123456789:ABC-..."
                                           class="w-64 rounded-md bg-white px-3 py-1.5 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600"/>
                                    <button type="button" @click="showToken = !showToken"
                                            class="text-xs text-gray-500 hover:text-gray-700" x-text="showToken ? 'Ocultar' : 'Mostrar'"></button>
                                </div>
                            </dd>
                        </div>
                        <div class="py-6 sm:flex sm:items-center">
                            <dt class="font-medium text-gray-900 sm:w-64 sm:flex-none sm:pr-6">Chat ID</dt>
                            <dd class="mt-1 sm:mt-0 sm:flex-auto">
                                <input type="text" name="telegram_chat_id" id="telegram_chat_id"
                                       value="{{ $settings['telegram_chat_id'] }}"
                                       placeholder="123456789"
                                       class="w-64 rounded-md bg-white px-3 py-1.5 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600"/>
                            </dd>
                        </div>
                        <div class="py-6 sm:flex sm:items-center">
                            <dt class="font-medium text-gray-900 sm:w-64 sm:flex-none sm:pr-6">Prueba</dt>
                            <dd class="mt-1 sm:mt-0 sm:flex-auto">
                                <button type="submit" form="form-test-telegram" class="rounded-md bg-white px-2.5 py-1.5 text-sm font-semibold text-gray-900 shadow-xs inset-ring inset-ring-gray-300 hover:bg-gray-50">
                                    Enviar mensaje de prueba
                                </button>
                            </dd>
                        </div>
                    </dl>
                </div>

                {{-- Notificaciones --}}
                <div id="notificaciones">
                    <h2 class="text-base/7 font-semibold text-gray-900">Notificaciones</h2>
                    <p class="mt-1 text-sm/6 text-gray-500">Cómo se entregan las alertas según el tipo de evento.</p>

                    <dl class="mt-6 divide-y divide-gray-100 border-t border-gray-200 text-sm/6">
                        <div class="py-6 sm:flex sm:items-start">
                            <dt class="font-medium text-gray-900 sm:w-64 sm:flex-none sm:pr-6">Nuevas convocatorias</dt>
                            <dd class="mt-1 flex justify-between gap-x-6 sm:mt-0 sm:flex-auto">
                                <div>
                                    <div class="text-gray-900">Solo campana (in-app)</div>
                                    <p class="mt-1 text-xs text-gray-500">Todas las convocatorias que pasen los filtros aparecen en la campana de notificaciones.</p>
                                </div>
                                <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10 h-fit">Autom&aacute;tico</span>
                            </dd>
                        </div>
                        <div class="py-6 sm:flex sm:items-start">
                            <dt class="font-medium text-gray-900 sm:w-64 sm:flex-none sm:pr-6">Convocatorias vigiladas</dt>
                            <dd class="mt-1 flex justify-between gap-x-6 sm:mt-0 sm:flex-auto">
                                <div>
                                    <div class="text-gray-900">Campana + Email + Telegram</div>
                                    <p class="mt-1 text-xs text-gray-500">Al vigilar una convocatoria con el bot&oacute;n <span class="font-medium">Notificar</span>, recibe alertas por todos los canales cuando cambie estado, plazo, monto, documentos o enmiendas.</p>
                                </div>
                                <span class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20 h-fit whitespace-nowrap">Por convocatoria</span>
                            </dd>
                        </div>
                        <div class="py-6 sm:flex sm:items-start">
                            <dt class="font-medium text-gray-900 sm:w-64 sm:flex-none sm:pr-6">
                                Resumen peri&oacute;dico
                                <p class="mt-1 text-xs font-normal text-gray-500">Env&iacute;a un resumen por email y Telegram con todas las convocatorias nuevas encontradas.</p>
                            </dt>
                            <dd class="mt-2 sm:mt-0 sm:flex-auto">
                                <div class="flex items-center gap-x-4">
                                    <div class="group relative inline-flex w-8 shrink-0 rounded-full bg-gray-200 p-px inset-ring inset-ring-gray-900/5 outline-offset-2 outline-blue-600 transition-colors duration-200 ease-in-out has-checked:bg-blue-600 has-focus-visible:outline-2">
                                        <span class="size-4 rounded-full bg-white shadow-xs ring-1 ring-gray-900/5 transition-transform duration-200 ease-in-out group-has-checked:translate-x-3.5"></span>
                                        <input type="checkbox" name="digest_enabled" aria-label="Activar resumen periódico"
                                               {{ ($settings['digest_enabled'] ?? '0') === '1' ? 'checked' : '' }}
                                               class="absolute inset-0 size-full appearance-none focus:outline-hidden"/>
                                    </div>
                                    <select name="digest_frequency"
                                            class="rounded-md bg-white px-3 py-1.5 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600">
                                        <option value="hourly" {{ ($settings['digest_frequency'] ?? 'daily_9am') === 'hourly' ? 'selected' : '' }}>Cada hora</option>
                                        <option value="every_2h" {{ ($settings['digest_frequency'] ?? 'daily_9am') === 'every_2h' ? 'selected' : '' }}>Cada 2 horas</option>
                                        <option value="twice_daily" {{ ($settings['digest_frequency'] ?? 'daily_9am') === 'twice_daily' ? 'selected' : '' }}>Dos veces al d&iacute;a (9am y 3pm)</option>
                                        <option value="daily_9am" {{ ($settings['digest_frequency'] ?? 'daily_9am') === 'daily_9am' ? 'selected' : '' }}>Diario a las 9:00 AM</option>
                                    </select>
                                </div>
                            </dd>
                        </div>
                    </dl>
                </div>

                {{-- Filtros --}}
                <div id="filtros">
                    <h2 class="text-base/7 font-semibold text-gray-900">Filtros</h2>
                    <p class="mt-1 text-sm/6 text-gray-500">Criterios adicionales para reducir el volumen de notificaciones.</p>

                    <dl class="mt-6 divide-y divide-gray-100 border-t border-gray-200 text-sm/6">

                        {{-- Min amount value --}}
                        <div class="py-6 sm:flex sm:items-center">
                            <dt class="font-medium text-gray-900 sm:w-64 sm:flex-none sm:pr-6">
                                Monto mínimo
                                <p class="mt-1 text-xs font-normal text-gray-500">Solo mostrar convocatorias que superen este monto. Deja en 0 para desactivar.</p>
                            </dt>
                            <dd class="mt-1 flex gap-x-3 sm:mt-0 sm:flex-auto">
                                <input type="number" name="min_amount_value" id="min_amount_value"
                                       value="{{ $settings['min_amount_value'] }}" min="0" step="1000"
                                       class="w-64 rounded-md bg-white px-3 py-1.5 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600"/>
                                <select name="min_amount_currency"
                                        class="rounded-md bg-white px-3 py-1.5 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600">
                                    <option value="DOP" {{ $settings['min_amount_currency'] === 'DOP' ? 'selected' : '' }}>DOP</option>
                                    <option value="USD" {{ $settings['min_amount_currency'] === 'USD' ? 'selected' : '' }}>USD</option>
                                </select>
                            </dd>
                        </div>

                        {{-- Max amount value --}}
                        <div class="py-6 sm:flex sm:items-center">
                            <dt class="font-medium text-gray-900 sm:w-64 sm:flex-none sm:pr-6">
                                Monto máximo
                                <p class="mt-1 text-xs font-normal text-gray-500">Solo mostrar convocatorias por debajo de este monto. Deja en 0 para desactivar.</p>
                            </dt>
                            <dd class="mt-1 flex gap-x-3 sm:mt-0 sm:flex-auto">
                                <input type="number" name="max_amount_value" id="max_amount_value"
                                       value="{{ $settings['max_amount_value'] }}" min="0" step="1000"
                                       class="w-64 rounded-md bg-white px-3 py-1.5 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600"/>
                                <select name="max_amount_currency"
                                        class="rounded-md bg-white px-3 py-1.5 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600">
                                    <option value="DOP" {{ $settings['max_amount_currency'] === 'DOP' ? 'selected' : '' }}>DOP</option>
                                    <option value="USD" {{ $settings['max_amount_currency'] === 'USD' ? 'selected' : '' }}>USD</option>
                                </select>
                            </dd>
                        </div>

                        {{-- Open deadline filter --}}
                        <div class="py-6 sm:flex sm:items-start">
                            <dt class="font-medium text-gray-900 sm:w-64 sm:flex-none sm:pr-6">
                                Solo procesos con plazo vigente
                                <p class="mt-1 text-xs font-normal text-gray-500">Ignorar procesos cuya fecha límite de presentación de ofertas ya haya vencido.</p>
                            </dt>
                            <dd class="mt-2 sm:mt-1 sm:flex-auto">
                                <div class="group relative inline-flex w-8 shrink-0 rounded-full bg-gray-200 p-px inset-ring inset-ring-gray-900/5 outline-offset-2 outline-blue-600 transition-colors duration-200 ease-in-out has-checked:bg-blue-600 has-focus-visible:outline-2">
                                    <span class="size-4 rounded-full bg-white shadow-xs ring-1 ring-gray-900/5 transition-transform duration-200 ease-in-out group-has-checked:translate-x-3.5"></span>
                                    <input type="checkbox" name="open_deadline_filter" aria-label="Solo procesos con plazo vigente"
                                           {{ $settings['open_deadline_filter'] === '1' ? 'checked' : '' }}
                                           class="absolute inset-0 size-full appearance-none focus:outline-hidden"/>
                                </div>
                            </dd>
                        </div>

                        {{-- Excluded modalities --}}
                        <div class="py-6">
                            <dt class="mb-4 font-medium text-gray-900">
                                Excluir modalidades
                                <p class="mt-1 text-xs font-normal text-gray-500">Marcar las modalidades que NO desea recibir.</p>
                            </dt>
                            <dd class="mt-2">
                                @php
                                    $modalities = [
                                        'Comparación de Precios',
                                        'Compras Menores',
                                        'Compras por Debajo del Umbral',
                                        'Licitación Pública Nacional',
                                        'Licitación Pública Internacional',
                                        'Licitación Restringida',
                                        'Procesos de Excepción',
                                        'Subasta Inversa',
                                    ];
                                @endphp
                                <div class="grid grid-cols-1 gap-y-3 sm:grid-cols-2">
                                    @foreach($modalities as $modality)
                                    <label class="flex cursor-pointer items-center gap-x-3 text-sm text-gray-700">
                                        <input type="checkbox" name="excluded_modalities[]" value="{{ $modality }}"
                                               {{ in_array($modality, $settings['excluded_modalities']) ? 'checked' : '' }}
                                               class="size-4 rounded border-gray-300 accent-blue-600"/>
                                        <span class="text-gray-700">{{ $modality }}</span>
                                    </label>
                                    @endforeach
                                </div>
                            </dd>
                        </div>

                        {{-- Positive keywords --}}
                        <div class="py-6 sm:flex sm:items-start">
                            <dt class="font-medium text-gray-900 sm:w-64 sm:flex-none sm:pr-6">
                                Palabras clave positivas
                                <p class="mt-1 text-xs font-normal text-gray-500">Convocatorias que contengan alguna de estas palabras se marcan como "Relevante". Separar con comas.</p>
                            </dt>
                            <dd class="mt-1 sm:mt-0 sm:flex-auto">
                                <input type="text" name="radar_keywords"
                                       value="{{ $settings['radar_keywords'] }}"
                                       placeholder="software, consultoría, estudio de suelo"
                                       class="w-full rounded-md bg-white px-3 py-1.5 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600"/>
                            </dd>
                        </div>

                        {{-- Negative keywords --}}
                        <div class="py-6 sm:flex sm:items-start">
                            <dt class="font-medium text-gray-900 sm:w-64 sm:flex-none sm:pr-6">
                                Palabras clave negativas
                                <p class="mt-1 text-xs font-normal text-gray-500">Convocatorias que contengan alguna de estas palabras se ocultan del listado filtrado. Separar con comas.</p>
                            </dt>
                            <dd class="mt-1 sm:mt-0 sm:flex-auto">
                                <input type="text" name="radar_excluded_keywords"
                                       value="{{ $settings['radar_excluded_keywords'] }}"
                                       placeholder="alimentos, medicamentos, combustible"
                                       class="w-full rounded-md bg-white px-3 py-1.5 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600"/>
                            </dd>
                        </div>

                    </dl>
                </div>

            </div>

            {{-- Save button --}}
            <div class="mt-10 flex items-center justify-end gap-x-4 border-t border-gray-900/10 pt-8">
                <a href="{{ route('dashboard') }}" class="py-2 text-sm font-semibold text-gray-900">Cancelar</a>
                <button type="submit"
                        class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-xs hover:bg-blue-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                    Guardar configuración
                </button>
            </div>
        </form>

        {{-- ── Suscripción (outside main form — has its own forms) ── --}}
        @if($subscription)
        <div id="suscripcion" class="mt-16">
            <h2 class="text-base/7 font-semibold text-gray-900">Suscripci&oacute;n</h2>
            <p class="mt-1 text-sm/6 text-gray-500">Gestiona tu plan, usuarios y empresas.</p>

            {{-- Plan status card --}}
            <div class="mt-6 rounded-lg bg-white p-6 shadow ring-1 ring-gray-900/5">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Plan {{ ucfirst($subscription->plan) }}</h3>
                        @if($subscription->status === 'trialing')
                        <p class="text-sm text-gray-500">Prueba gratuita</p>
                        @else
                        <p class="text-sm text-gray-500">
                            US${{ number_format($subscription->monthly_amount, 2) }}/mes
                            @if($subscription->billing_cycle === 'annual')
                                <span class="text-xs text-green-600">(facturado anualmente con 20% dto.)</span>
                            @endif
                        </p>
                        @endif
                    </div>
                    <span class="inline-flex items-center rounded-full px-3 py-0.5 text-sm font-medium
                        {{ $subscription->isActive() && $subscription->status !== 'trialing' ? 'bg-green-100 text-green-800' : '' }}
                        {{ $subscription->status === 'trialing' ? 'bg-blue-100 text-blue-800' : '' }}
                        {{ $subscription->isPending() ? 'bg-yellow-100 text-yellow-800' : '' }}
                        {{ $subscription->isPastDue() ? 'bg-red-100 text-red-800' : '' }}
                        {{ $subscription->status === 'cancelled' ? 'bg-gray-100 text-gray-800' : '' }}
                        {{ $subscription->status === 'suspended' ? 'bg-red-100 text-red-800' : '' }}">
                        {{ match($subscription->status) {
                            'active' => 'Activa',
                            'trialing' => 'Prueba gratuita',
                            'pending' => 'Pendiente de pago',
                            'past_due' => 'Pago vencido',
                            'cancelled' => 'Cancelada',
                            'suspended' => 'Suspendida',
                            default => $subscription->status,
                        } }}
                    </span>
                </div>

                @if($subscription->status === 'trialing')
                <div class="mt-4 rounded-md bg-blue-50 p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-blue-900">D&iacute;as restantes: {{ $usage['trial_days_left'] ?? 0 }}</p>
                            <p class="mt-1 text-sm text-blue-700">An&aacute;lisis con IA: {{ $usage['trial_parses_used'] ?? 0 }} / {{ $usage['trial_parses_limit'] ?? 0 }}</p>
                        </div>
                        <a href="{{ route('register.show') }}"
                           class="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500">
                            Suscr&iacute;bete ahora
                        </a>
                    </div>
                    <p class="mt-2 text-xs text-blue-600">Desde US$45/mes &mdash; acceso completo, an&aacute;lisis ilimitados.</p>
                </div>
                @endif

                @if($subscription->current_period_end && $subscription->status !== 'trialing')
                <p class="mt-3 text-sm text-gray-500">
                    Periodo actual: {{ $subscription->current_period_start?->format('d/m/Y') }} &mdash; {{ $subscription->current_period_end->format('d/m/Y') }}
                </p>
                @endif
            </div>

            {{-- Usage + Upgrade cards --}}
            @if($usage)
            <div class="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-2">
                {{-- Companies usage --}}
                <div class="rounded-lg bg-white p-6 shadow ring-1 ring-gray-900/5" x-data="{ showConfirm: false, preview: null, loading: false }">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-medium text-gray-500">Empresas</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $usage['companies'] }} <span class="text-sm font-normal text-gray-400">/ {{ $usage['max_companies'] }}</span></p>
                    </div>
                    <div class="mt-3 h-2 w-full rounded-full bg-gray-100">
                        <div class="h-2 rounded-full {{ $usage['companies'] >= $usage['max_companies'] ? 'bg-red-500' : 'bg-blue-500' }}"
                             style="width: {{ $usage['max_companies'] > 0 ? min(100, ($usage['companies'] / $usage['max_companies']) * 100) : 0 }}%"></div>
                    </div>

                    @if($isOwner && $subscription->isActive() && $subscription->status !== 'trialing')
                    <div class="mt-4">
                        <button type="button"
                                @click="if (!preview) { loading = true; fetch('{{ route('billing.preview-addon') }}?type=company').then(r => r.json()).then(d => { preview = d; loading = false; showConfirm = true; }).catch(() => loading = false); } else { showConfirm = true; }"
                                class="inline-flex items-center gap-1.5 rounded-md bg-white px-3 py-1.5 text-sm font-semibold text-gray-900 shadow-xs ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                            <span x-text="loading ? 'Calculando...' : 'Agregar empresa'"></span>
                            <span class="text-xs text-gray-400">+US${{ number_format(\App\Services\SubscriptionService::EXTRA_COMPANY_PRICE, 0) }}/mes</span>
                        </button>

                        <div x-show="showConfirm" x-cloak class="mt-3 rounded-md border border-blue-200 bg-blue-50 p-4">
                            <p class="text-sm font-medium text-blue-900">Confirmar compra</p>
                            <dl class="mt-2 space-y-1 text-sm text-blue-800">
                                <div class="flex justify-between">
                                    <dt>Cobro prorrateado (hoy)</dt>
                                    <dd class="font-semibold" x-text="'US$' + (preview?.prorated_amount ?? 0).toFixed(2)"></dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt>Pr&oacute;ximo ciclo (<span x-text="preview?.billing_cycle === 'annual' ? 'anual' : 'mensual'"></span>)</dt>
                                    <dd class="font-semibold" x-text="'US$' + (preview?.new_recurring ?? 0).toFixed(2)"></dd>
                                </div>
                            </dl>
                            <div class="mt-3 flex gap-2">
                                <form method="POST" action="{{ route('billing.purchase-addon') }}">
                                    @csrf
                                    <input type="hidden" name="type" value="company">
                                    <button type="submit" class="rounded-md bg-blue-600 px-3 py-1.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-500">
                                        Confirmar &mdash; cobrar <span x-text="'US$' + (preview?.prorated_amount ?? 0).toFixed(2)"></span>
                                    </button>
                                </form>
                                <button type="button" @click="showConfirm = false" class="rounded-md px-3 py-1.5 text-sm font-semibold text-gray-700 hover:bg-gray-100">
                                    Cancelar
                                </button>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>

                {{-- Users usage --}}
                <div class="rounded-lg bg-white p-6 shadow ring-1 ring-gray-900/5" x-data="{ showConfirm: false, preview: null, loading: false }">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-medium text-gray-500">Usuarios</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $usage['users'] }} <span class="text-sm font-normal text-gray-400">/ {{ $usage['max_users'] }}</span></p>
                    </div>
                    <div class="mt-3 h-2 w-full rounded-full bg-gray-100">
                        <div class="h-2 rounded-full {{ $usage['users'] >= $usage['max_users'] ? 'bg-red-500' : 'bg-blue-500' }}"
                             style="width: {{ $usage['max_users'] > 0 ? min(100, ($usage['users'] / $usage['max_users']) * 100) : 0 }}%"></div>
                    </div>

                    @if($isOwner && $subscription->isActive() && $subscription->status !== 'trialing')
                    <div class="mt-4">
                        <button type="button"
                                @click="if (!preview) { loading = true; fetch('{{ route('billing.preview-addon') }}?type=user').then(r => r.json()).then(d => { preview = d; loading = false; showConfirm = true; }).catch(() => loading = false); } else { showConfirm = true; }"
                                class="inline-flex items-center gap-1.5 rounded-md bg-white px-3 py-1.5 text-sm font-semibold text-gray-900 shadow-xs ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                            <span x-text="loading ? 'Calculando...' : 'Agregar usuario'"></span>
                            <span class="text-xs text-gray-400">+US${{ number_format(\App\Services\SubscriptionService::EXTRA_USER_PRICE, 0) }}/mes</span>
                        </button>

                        <div x-show="showConfirm" x-cloak class="mt-3 rounded-md border border-blue-200 bg-blue-50 p-4">
                            <p class="text-sm font-medium text-blue-900">Confirmar compra</p>
                            <dl class="mt-2 space-y-1 text-sm text-blue-800">
                                <div class="flex justify-between">
                                    <dt>Cobro prorrateado (hoy)</dt>
                                    <dd class="font-semibold" x-text="'US$' + (preview?.prorated_amount ?? 0).toFixed(2)"></dd>
                                </div>
                                <div class="flex justify-between">
                                    <dt>Pr&oacute;ximo ciclo (<span x-text="preview?.billing_cycle === 'annual' ? 'anual' : 'mensual'"></span>)</dt>
                                    <dd class="font-semibold" x-text="'US$' + (preview?.new_recurring ?? 0).toFixed(2)"></dd>
                                </div>
                            </dl>
                            <div class="mt-3 flex gap-2">
                                <form method="POST" action="{{ route('billing.purchase-addon') }}">
                                    @csrf
                                    <input type="hidden" name="type" value="user">
                                    <button type="submit" class="rounded-md bg-blue-600 px-3 py-1.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-500">
                                        Confirmar &mdash; cobrar <span x-text="'US$' + (preview?.prorated_amount ?? 0).toFixed(2)"></span>
                                    </button>
                                </form>
                                <button type="button" @click="showConfirm = false" class="rounded-md px-3 py-1.5 text-sm font-semibold text-gray-700 hover:bg-gray-100">
                                    Cancelar
                                </button>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            {{-- Payment method --}}
            @if($subscription->gateway_subscription_id && $subscription->payment_gateway === 'paypal')
            <div class="mt-6 rounded-lg bg-white p-6 shadow ring-1 ring-gray-900/5">
                <h3 class="text-sm font-semibold text-gray-900">M&eacute;todo de pago</h3>
                <div class="mt-2 flex items-center gap-3">
                    <span class="inline-flex items-center rounded-full bg-blue-100 px-3 py-1 text-sm font-medium text-blue-800">PayPal</span>
                    <span class="text-sm text-gray-500">Cobro autom&aacute;tico {{ $subscription->billing_cycle === 'annual' ? 'anual' : 'mensual' }}</span>
                </div>
            </div>
            @endif

            {{-- Payment history --}}
            @if($payments->isNotEmpty())
            <div class="mt-6 rounded-lg bg-white p-6 shadow ring-1 ring-gray-900/5">
                <h3 class="text-sm font-semibold text-gray-900">Historial de pagos</h3>
                <table class="mt-4 w-full text-sm">
                    <thead>
                        <tr class="border-b text-left text-gray-500">
                            <th class="pb-2 font-medium">Fecha</th>
                            <th class="pb-2 font-medium">Monto</th>
                            <th class="pb-2 font-medium">M&eacute;todo</th>
                            <th class="pb-2 font-medium">Nota</th>
                            <th class="pb-2 font-medium">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach($payments as $payment)
                        <tr>
                            <td class="py-2">{{ $payment->paid_at?->format('d/m/Y') ?? '&mdash;' }}</td>
                            <td class="py-2">US${{ number_format($payment->amount, 2) }}</td>
                            <td class="py-2">{{ match($payment->gateway) {
                                'paypal' => 'PayPal',
                                'azul' => 'Azul',
                                'bank_transfer' => 'Transferencia',
                                default => $payment->gateway,
                            } }}</td>
                            <td class="py-2 text-xs text-gray-500">{{ $payment->notes ?? '&mdash;' }}</td>
                            <td class="py-2">
                                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium
                                    {{ $payment->status === 'completed' ? 'bg-green-100 text-green-800' : '' }}
                                    {{ $payment->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                    {{ $payment->status === 'failed' ? 'bg-red-100 text-red-800' : '' }}">
                                    {{ match($payment->status) {
                                        'completed' => 'Completado',
                                        'pending' => 'Pendiente',
                                        'failed' => 'Fallido',
                                        'refunded' => 'Reembolsado',
                                        default => $payment->status,
                                    } }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif

            {{-- Cancel subscription --}}
            @if($isOwner && $subscription->isActive() && $subscription->status !== 'trialing')
            <div class="mt-6 rounded-lg bg-white p-6 shadow ring-1 ring-gray-900/5" x-data="{ confirmCancel: false }">
                <h3 class="text-sm font-semibold text-gray-900">Cancelar suscripci&oacute;n</h3>
                <p class="mt-1 text-sm text-gray-500">Al cancelar, mantendr&aacute;s acceso hasta el {{ $subscription->current_period_end?->format('d/m/Y') }}.</p>
                <div class="mt-3">
                    <button type="button" @click="confirmCancel = true" x-show="!confirmCancel"
                            class="text-sm font-medium text-red-600 hover:text-red-500">
                        Cancelar suscripci&oacute;n
                    </button>
                    <div x-show="confirmCancel" x-cloak class="rounded-md border border-red-200 bg-red-50 p-4">
                        <p class="text-sm text-red-800">&iquest;Est&aacute;s seguro? Se cancelar&aacute; el cobro autom&aacute;tico y perder&aacute;s acceso al finalizar tu periodo actual.</p>
                        <div class="mt-3 flex gap-2">
                            <form method="POST" action="{{ route('billing.cancel') }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="rounded-md bg-red-600 px-3 py-1.5 text-sm font-semibold text-white shadow-sm hover:bg-red-500">
                                    S&iacute;, cancelar suscripci&oacute;n
                                </button>
                            </form>
                            <button type="button" @click="confirmCancel = false" class="rounded-md px-3 py-1.5 text-sm font-semibold text-gray-700 hover:bg-gray-100">
                                No, mantener
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            {{-- Trial expired / pending CTA --}}
            @if($subscription->trialExpired() || $subscription->isPending() || $subscription->isPastDue())
            <div class="mt-6 rounded-lg border-2 border-blue-200 bg-blue-50 p-6">
                <h3 class="text-lg font-semibold text-gray-900">
                    {{ $subscription->trialExpired() ? 'Tu prueba gratuita ha expirado' : 'Activa tu suscripción' }}
                </h3>
                <p class="mt-1 text-sm text-gray-600">Suscr&iacute;bete para mantener acceso completo.</p>
                <a href="{{ route('register.show') }}"
                   class="mt-3 inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500">
                    Suscr&iacute;bete ahora &mdash; US$45/mes
                </a>
            </div>
            @endif
        </div>
        @endif

    </main>

</div>

@if(request('tab') === 'suscripcion')
<script>document.addEventListener('DOMContentLoaded', () => document.getElementById('suscripcion')?.scrollIntoView({ behavior: 'smooth' }));</script>
@endif
@endsection
