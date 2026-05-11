@php
    $cal = $calendarIntegration ?? [];
    $g = $cal['google'] ?? ['connected' => false];
@endphp

<div id="calendario-panel" x-data="{ calSub: 'ical' }">
    <h2 class="text-base/7 font-semibold text-gray-900">Calendario del Tablero</h2>
    <p class="mt-1 text-sm/6 text-gray-500">
        Sincronice fechas límite de ofertas y eventos del Tablero con Google Calendar, Apple Calendar u otros clientes compatibles con iCalendar.
    </p>

    {{-- Sub-tabs --}}
    <div class="mt-6 border-b border-gray-200">
        <nav class="-mb-px flex gap-x-6" aria-label="Subsecciones de calendario">
            <button type="button" @click="calSub = 'ical'"
                    :class="calSub === 'ical'
                        ? 'border-blue-600 text-blue-600'
                        : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
                    class="border-b-2 px-1 pb-3 text-sm font-medium whitespace-nowrap">
                Suscripción iCal
            </button>
            <button type="button" @click="calSub = 'google'"
                    :class="calSub === 'google'
                        ? 'border-blue-600 text-blue-600'
                        : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
                    class="border-b-2 px-1 pb-3 text-sm font-medium whitespace-nowrap">
                Google Calendar
            </button>
        </nav>
    </div>

    {{-- iCal --}}
    <div x-show="calSub === 'ical'" x-cloak>
        <dl class="mt-2 divide-y divide-gray-100 text-sm/6">
            <div class="py-6 sm:flex sm:items-start">
                <dt class="font-medium text-gray-900 sm:w-64 sm:flex-none sm:pr-6">
                    Enlace secreto
                    <p class="mt-1 text-xs font-normal text-gray-500">
                        No comparta esta URL. Quien la tenga puede ver las fechas del tablero de su empresa en una app de calendario.
                    </p>
                </dt>
                <dd class="mt-2 sm:mt-0 sm:flex-auto">
                    @if(!empty($cal['feed_url']))
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                            <input type="text" readonly value="{{ $cal['feed_url'] }}"
                                   id="calendar-feed-url"
                                   class="block w-full min-w-0 flex-1 rounded-md bg-white px-3 py-1.5 text-xs text-gray-700 outline-1 -outline-offset-1 outline-gray-300 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600"/>
                            <button type="button"
                                    onclick="navigator.clipboard.writeText(document.getElementById('calendar-feed-url').value); this.innerText='Copiado'; setTimeout(()=>{this.innerText='Copiar';}, 1500);"
                                    class="shrink-0 rounded-md bg-white px-2.5 py-1.5 text-sm font-semibold text-gray-900 shadow-xs inset-ring inset-ring-gray-300 hover:bg-gray-50">
                                Copiar
                            </button>
                        </div>
                        <p class="mt-3 text-xs text-gray-500">
                            En Google Calendar: <span class="font-medium text-gray-700">Otros calendarios → Agregar → Desde URL</span> y pegue el enlace.
                        </p>
                    @else
                        <p class="text-sm/6 text-gray-700">Aún no hay enlace. Genere uno para suscribirse desde su calendario.</p>
                    @endif
                </dd>
            </div>

            <div class="py-6 sm:flex sm:items-center">
                <dt class="font-medium text-gray-900 sm:w-64 sm:flex-none sm:pr-6">
                    {{ !empty($cal['feed_url']) ? 'Rotar enlace' : 'Generar enlace' }}
                    <p class="mt-1 text-xs font-normal text-gray-500">
                        @if(!empty($cal['feed_url']))
                            Crear uno nuevo invalida el anterior.
                        @else
                            Crea un enlace privado para esta empresa.
                        @endif
                    </p>
                </dt>
                <dd class="mt-1 sm:mt-0 sm:flex-auto">
                    <form method="POST" action="{{ route('settings.calendar.feed.regenerate') }}">
                        @csrf
                        <button type="submit"
                                class="rounded-md bg-white px-2.5 py-1.5 text-sm font-semibold text-gray-900 shadow-xs inset-ring inset-ring-gray-300 hover:bg-gray-50">
                            {{ !empty($cal['feed_url']) ? 'Generar nuevo enlace' : 'Generar enlace secreto' }}
                        </button>
                    </form>
                </dd>
            </div>
        </dl>
    </div>

    {{-- Google --}}
    <div x-show="calSub === 'google'" x-cloak>
        @if(empty($cal['google_configured']))
            <div class="mt-6 rounded-md bg-yellow-50 p-4 text-sm/6 text-yellow-800 ring-1 ring-inset ring-yellow-600/20">
                <p class="font-medium">Integración no configurada</p>
                <p class="mt-2 text-yellow-700">
                    El administrador del sistema debe definir
                    <code class="rounded bg-yellow-100 px-1 py-0.5 text-xs">GOOGLE_CALENDAR_CLIENT_ID</code> y
                    <code class="rounded bg-yellow-100 px-1 py-0.5 text-xs">GOOGLE_CALENDAR_CLIENT_SECRET</code>,
                    y autorizar esta URI de redirección en Google Cloud:
                </p>
                <code class="mt-2 block break-all rounded bg-yellow-100 px-2 py-1 text-xs text-yellow-900">{{ route('settings.calendar.google.callback', [], true) }}</code>
            </div>
        @elseif(empty($g['connected']))
            <dl class="mt-2 divide-y divide-gray-100 text-sm/6">
                <div class="py-6 sm:flex sm:items-start">
                    <dt class="font-medium text-gray-900 sm:w-64 sm:flex-none sm:pr-6">
                        Conectar cuenta
                        <p class="mt-1 text-xs font-normal text-gray-500">
                            Los eventos del Tablero se copiarán al calendario principal de la cuenta que autorice. Cada usuario enlaza su propia cuenta.
                        </p>
                    </dt>
                    <dd class="mt-2 sm:mt-0 sm:flex-auto">
                        <a href="{{ route('settings.calendar.google.redirect') }}"
                           class="inline-flex items-center gap-x-2 rounded-md bg-blue-600 px-3 py-1.5 text-sm font-semibold text-white shadow-xs hover:bg-blue-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                            <svg class="size-4" viewBox="0 0 24 24" aria-hidden="true">
                                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.27-4.74 3.27-8.1Z"/>
                                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.65l-3.57-2.77c-.99.66-2.26 1.06-3.71 1.06-2.86 0-5.28-1.93-6.15-4.53H2.17v2.84A11 11 0 0 0 12 23Z"/>
                                <path fill="#FBBC05" d="M5.85 14.11A6.6 6.6 0 0 1 5.5 12c0-.74.13-1.45.35-2.11V7.05H2.17A11 11 0 0 0 1 12c0 1.78.43 3.46 1.17 4.95l3.68-2.84Z"/>
                                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1A11 11 0 0 0 2.17 7.05l3.68 2.84C6.72 7.31 9.14 5.38 12 5.38Z"/>
                            </svg>
                            Conectar con Google
                        </a>
                    </dd>
                </div>
            </dl>
        @else
            <dl class="mt-2 divide-y divide-gray-100 text-sm/6">
                <div class="py-6 sm:flex sm:items-center">
                    <dt class="font-medium text-gray-900 sm:w-64 sm:flex-none sm:pr-6">Estado</dt>
                    <dd class="mt-1 sm:mt-0 sm:flex-auto">
                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                            <span class="inline-flex items-center gap-x-1.5 rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">
                                <svg class="size-1.5 fill-green-500" viewBox="0 0 6 6" aria-hidden="true"><circle cx="3" cy="3" r="3"/></svg>
                                Conectado
                            </span>
                            @if(! ($g['sync_enabled'] ?? false))
                                <span class="inline-flex items-center rounded-md bg-yellow-50 px-2 py-1 text-xs font-medium text-yellow-800 ring-1 ring-inset ring-yellow-600/20">
                                    Sincronización en pausa
                                </span>
                            @endif
                            <span class="text-gray-500">{{ $g['email'] ?: 'Cuenta de Google' }}</span>
                        </div>
                        @if(!empty($g['last_synced_at']))
                            <p class="mt-2 text-xs text-gray-500">Última sincronización: {{ \Illuminate\Support\Carbon::parse($g['last_synced_at'])->timezone('America/Santo_Domingo')->format('d/m/Y H:i') }}</p>
                        @endif
                        @if(!empty($g['last_sync_error']))
                            <p class="mt-2 text-xs text-red-600">{{ $g['last_sync_error'] }}</p>
                        @endif
                    </dd>
                </div>

                <div class="py-6 sm:flex sm:items-center">
                    <dt class="font-medium text-gray-900 sm:w-64 sm:flex-none sm:pr-6">
                        Acciones
                        <p class="mt-1 text-xs font-normal text-gray-500">Vuelva a empujar todos los eventos o pause la sincronización temporal.</p>
                    </dt>
                    <dd class="mt-1 flex flex-wrap gap-2 sm:mt-0 sm:flex-auto">
                        <form method="POST" action="{{ route('settings.calendar.google.resync') }}">
                            @csrf
                            <button type="submit" class="rounded-md bg-white px-2.5 py-1.5 text-sm font-semibold text-gray-900 shadow-xs inset-ring inset-ring-gray-300 hover:bg-gray-50">
                                Sincronizar todo
                            </button>
                        </form>
                        <form method="POST" action="{{ route('settings.calendar.google.sync-toggle') }}">
                            @csrf
                            <button type="submit" class="rounded-md bg-white px-2.5 py-1.5 text-sm font-semibold text-gray-900 shadow-xs inset-ring inset-ring-gray-300 hover:bg-gray-50">
                                {{ ($g['sync_enabled'] ?? false) ? 'Pausar sincronización' : 'Reanudar sincronización' }}
                            </button>
                        </form>
                    </dd>
                </div>

                <div class="py-6 sm:flex sm:items-center">
                    <dt class="font-medium text-gray-900 sm:w-64 sm:flex-none sm:pr-6">
                        Desconectar
                        <p class="mt-1 text-xs font-normal text-gray-500">
                            Borra los eventos creados por Radar de Licitaciones en esa cuenta de Google.
                        </p>
                    </dt>
                    <dd class="mt-1 sm:mt-0 sm:flex-auto">
                        <form method="POST" action="{{ route('settings.calendar.google.disconnect') }}"
                              onsubmit="return confirm('¿Desconectar Google Calendar y borrar los eventos sincronizados?');">
                            @csrf
                            <button type="submit" class="rounded-md bg-white px-2.5 py-1.5 text-sm font-semibold text-red-700 shadow-xs inset-ring inset-ring-red-200 hover:bg-red-50">
                                Desconectar Google Calendar
                            </button>
                        </form>
                    </dd>
                </div>
            </dl>
        @endif
    </div>
</div>
