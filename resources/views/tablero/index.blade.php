@extends('layouts.app')
@section('title', 'Tablero')

@section('content')
<div x-data="tablero()" x-init="init()" class="h-[calc(100vh-4rem)] flex flex-col">

    {{-- Header --}}
    <div class="shrink-0 border-b border-gray-200 bg-white px-4 py-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <h1 class="text-base font-semibold text-gray-900">Tablero</h1>
                {{-- View toggle --}}
                <div class="flex rounded-lg bg-gray-100 p-0.5">
                    <button @click="setView('kanban')"
                            class="rounded-md px-3 py-1 text-xs font-medium transition-colors"
                            :class="view === 'kanban' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700'">
                        Tareas
                    </button>
                    <button @click="setView('calendar')"
                            class="rounded-md px-3 py-1 text-xs font-medium transition-colors"
                            :class="view === 'calendar' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700'">
                        Calendario
                    </button>
                </div>
            </div>
            <div class="flex items-center gap-3">
                {{-- Search (kanban only) --}}
                <div class="relative" x-show="view === 'kanban'">
                    <input type="text" x-model="search" @input.debounce.300ms="loadCards()"
                           placeholder="Buscar en el tablero..."
                           class="block w-64 rounded-md border-0 py-1.5 pl-8 pr-3 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-blue-600 sm:text-sm">
                    <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 size-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                    </svg>
                </div>
                {{-- Month nav (calendar only) --}}
                <div class="flex items-center gap-2" x-show="view === 'calendar'" x-cloak>
                    <button @click="prevMonth()" class="rounded-md bg-gray-50 p-1.5 text-gray-500 ring-1 ring-inset ring-gray-300 hover:bg-gray-100">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/>
                        </svg>
                    </button>
                    <span class="text-sm font-medium text-gray-900 min-w-[140px] text-center" x-text="calMonthLabel"></span>
                    <button @click="nextMonth()" class="rounded-md bg-gray-50 p-1.5 text-gray-500 ring-1 ring-inset ring-gray-300 hover:bg-gray-100">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/>
                        </svg>
                    </button>
                    <button @click="goToday()" class="rounded-md bg-gray-50 px-2.5 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-300 hover:bg-gray-100">Hoy</button>
                </div>
                <button @click="showFilters = !showFilters"
                        class="rounded-md bg-gray-50 px-2.5 py-1.5 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-300 hover:bg-gray-100"
                        :class="hasFilters && 'ring-blue-400 text-blue-700 bg-blue-50'"
                        x-show="view === 'kanban'">
                    <span class="flex items-center gap-1">
                        <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z"/>
                        </svg>
                        Filtros
                    </span>
                </button>
                <button @click="refresh()" class="rounded-md bg-gray-50 p-1.5 text-gray-500 ring-1 ring-inset ring-gray-300 hover:bg-gray-100" title="Refrescar">
                    <svg class="size-4" :class="loading && 'animate-spin'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Filter panel --}}
        <div x-show="showFilters" x-cloak x-transition
             class="mt-3 flex flex-wrap items-end gap-3 border-t border-gray-100 pt-3">
            <div>
                <label class="block text-xs font-medium text-gray-500">Entidad</label>
                <input type="text" x-model="filters.entity" @input.debounce.500ms="loadCards()"
                       placeholder="Nombre..."
                       class="mt-1 block w-48 rounded-md border-0 py-1 pl-2.5 text-sm text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-blue-600">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500">Cierre desde</label>
                <input type="date" x-model="filters.deadline_from" @change="loadCards()"
                       class="mt-1 block rounded-md border-0 py-1 pl-2.5 text-sm text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-blue-600">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500">Cierre hasta</label>
                <input type="date" x-model="filters.deadline_to" @change="loadCards()"
                       class="mt-1 block rounded-md border-0 py-1 pl-2.5 text-sm text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-blue-600">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500">Monto mín.</label>
                <input type="number" x-model="filters.amount_min" @change="loadCards()" min="0" step="10000"
                       placeholder="0"
                       class="mt-1 block w-32 rounded-md border-0 py-1 pl-2.5 text-sm text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-blue-600">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500">Monto máx.</label>
                <input type="number" x-model="filters.amount_max" @change="loadCards()" min="0" step="10000"
                       placeholder="0"
                       class="mt-1 block w-32 rounded-md border-0 py-1 pl-2.5 text-sm text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-blue-600">
            </div>
            <button @click="clearFilters()" class="rounded-md px-2.5 py-1 text-xs font-medium text-gray-500 hover:text-gray-700" x-show="hasFilters">
                Limpiar
            </button>
        </div>
    </div>

    {{-- ═══ KANBAN VIEW ═══ --}}
    <div x-show="view === 'kanban'" class="flex-1 overflow-x-auto overflow-y-hidden">
        <div class="flex h-full gap-4 p-4 sm:p-6 min-w-max">
            <template x-for="(col, estado) in columns" :key="estado">
                <div class="flex w-72 shrink-0 flex-col rounded-xl bg-gray-50 ring-1 ring-gray-200">
                    <div class="flex items-center justify-between px-3 py-3">
                        <div class="flex items-center gap-2">
                            <span class="flex size-6 items-center justify-center rounded-lg"
                                  :class="columnHeaderClass(col.color)"
                                  x-html="columnIcon(col.icon)"></span>
                            <h3 class="text-sm font-semibold text-gray-900" x-text="col.label"></h3>
                            <span class="rounded-full bg-gray-200 px-2 py-0.5 text-xs font-medium text-gray-600"
                                  x-text="col.cards.length"></span>
                        </div>
                    </div>
                    <div class="flex-1 overflow-y-auto px-3 pb-3 space-y-2">
                        <template x-for="card in col.cards" :key="card.id">
                            <div class="rounded-lg bg-white p-3 shadow-sm ring-1 ring-gray-200 hover:ring-gray-300 cursor-pointer transition-shadow"
                                 @click="openCard(card)">
                                <p class="text-sm font-medium text-gray-900 line-clamp-2" x-text="card.title"></p>
                                <p class="mt-1 text-xs text-gray-500 truncate" x-text="card.entity"></p>
                                <div class="mt-2 flex items-center justify-between gap-2">
                                    <template x-if="card.deadline_label">
                                        <span class="inline-flex items-center rounded px-1.5 py-0.5 text-xs font-medium"
                                              :class="deadlineBadge(card.deadline_color)"
                                              x-text="card.deadline_label"></span>
                                    </template>
                                    <template x-if="card.amount && card.amount > 0">
                                        <span class="text-xs text-gray-500" x-text="formatAmount(card.amount, card.currency)"></span>
                                    </template>
                                </div>
                                <template x-if="card.mipymes">
                                    <span class="mt-1.5 inline-flex rounded bg-purple-50 px-1.5 py-0.5 text-[10px] font-medium text-purple-700 ring-1 ring-inset ring-purple-600/20">MIPYMES</span>
                                </template>
                                <div class="mt-2 flex flex-wrap gap-1 border-t border-gray-100 pt-2" @click.stop>
                                    <template x-for="target in allowedMoves(card.estado)" :key="target">
                                        <button @click="moveCard(card, target)"
                                                class="rounded px-2 py-0.5 text-[10px] font-medium transition-colors"
                                                :class="moveBtnClass(target)"
                                                x-text="moveLabel(target)"></button>
                                    </template>
                                </div>
                            </div>
                        </template>
                        <template x-if="col.cards.length === 0">
                            <div class="flex items-center justify-center py-8 text-xs text-gray-400">Sin ofertas</div>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- ═══ CALENDAR VIEW ═══ --}}
    <div x-show="view === 'calendar'" x-cloak class="flex-1 overflow-y-auto p-4 sm:p-6 lg:px-8">
        <div class="mx-auto max-w-5xl">
            {{-- Day headers --}}
            <div class="grid grid-cols-7 gap-px text-center text-xs font-semibold text-gray-700 mb-1">
                <div class="py-2">Lun</div>
                <div class="py-2">Mar</div>
                <div class="py-2">Mié</div>
                <div class="py-2">Jue</div>
                <div class="py-2">Vie</div>
                <div class="py-2">Sáb</div>
                <div class="py-2">Dom</div>
            </div>
            {{-- Calendar grid --}}
            <div class="grid grid-cols-7 gap-px rounded-lg bg-gray-200 ring-1 ring-gray-200 overflow-hidden">
                <template x-for="cell in calCells" :key="cell.key">
                    <div class="min-h-[100px] bg-white p-1.5 text-sm"
                         :class="cell.isCurrentMonth ? '' : 'bg-gray-50 text-gray-400'"
                         @click="cell.events.length && (selectedDate = cell.date)">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-medium" :class="cell.isToday ? 'flex size-6 items-center justify-center rounded-full bg-blue-600 text-white' : ''"
                                  x-text="cell.day"></span>
                        </div>
                        <div class="mt-1 space-y-0.5">
                            <template x-for="(ev, i) in cell.events.slice(0, 3)" :key="i">
                                <div class="truncate rounded px-1 py-0.5 text-[10px] font-medium cursor-pointer"
                                     :class="calEventClass(ev.color)"
                                     @click.stop="openOffer(ev.offer_id)"
                                     x-text="ev.title"></div>
                            </template>
                            <template x-if="cell.events.length > 3">
                                <button @click.stop="selectedDate = cell.date"
                                        class="text-[10px] text-gray-500 hover:text-blue-600 font-medium"
                                        x-text="'+' + (cell.events.length - 3) + ' más'"></button>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        {{-- Day detail panel --}}
        <template x-if="selectedDate">
            <div class="mx-auto max-w-5xl mt-4">
                <div class="rounded-lg border border-gray-200 bg-white p-4">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-semibold text-gray-900" x-text="selectedDateLabel"></h3>
                        <button @click="selectedDate = null" class="text-gray-400 hover:text-gray-600">
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    <ul class="divide-y divide-gray-100">
                        <template x-for="ev in selectedDateEvents" :key="ev.offer_id + ev.title">
                            <li class="flex items-center justify-between py-2 cursor-pointer hover:bg-gray-50 -mx-2 px-2 rounded"
                                @click="openOffer(ev.offer_id)">
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <span class="flex size-2 shrink-0 rounded-full" :class="calDotClass(ev.color)"></span>
                                        <p class="text-sm font-medium text-gray-900 truncate" x-text="ev.title"></p>
                                    </div>
                                    <p class="ml-4 text-xs text-gray-500" x-text="ev.entity"></p>
                                </div>
                                <div class="text-right shrink-0 ml-3">
                                    <span class="text-xs text-gray-500" x-text="ev.time"></span>
                                    <span class="ml-2 inline-flex rounded px-1.5 py-0.5 text-[10px] font-medium"
                                          :class="calEventClass(ev.color)"
                                          x-text="ev.type === 'deadline' ? 'Cierre' : 'Evento'"></span>
                                </div>
                            </li>
                        </template>
                    </ul>
                </div>
            </div>
        </template>
    </div>
</div>

<script>
function tablero() {
    return {
        view: 'kanban',
        columns: {},
        loading: false,
        search: '',
        showFilters: false,
        filters: { entity: '', deadline_from: '', deadline_to: '', amount_min: '', amount_max: '' },

        // Calendar state
        calYear: new Date().getFullYear(),
        calMonth: new Date().getMonth(), // 0-indexed
        calEvents: [],
        calCells: [],
        selectedDate: null,

        init() {
            this.loadCards();
        },

        setView(v) {
            this.view = v;
            if (v === 'calendar' && this.calCells.length === 0) {
                this.loadCalendar();
            }
        },

        refresh() {
            if (this.view === 'kanban') this.loadCards();
            else this.loadCalendar();
        },

        get hasFilters() {
            return Object.values(this.filters).some(v => v !== '' && v !== null && v !== undefined);
        },

        clearFilters() {
            this.filters = { entity: '', deadline_from: '', deadline_to: '', amount_min: '', amount_max: '' };
            this.loadCards();
        },

        // ── Kanban ──────────────────────────
        async loadCards() {
            this.loading = true;
            try {
                const p = new URLSearchParams();
                if (this.search) p.set('q', this.search);
                if (this.filters.entity) p.set('entity', this.filters.entity);
                if (this.filters.deadline_from) p.set('deadline_from', this.filters.deadline_from);
                if (this.filters.deadline_to) p.set('deadline_to', this.filters.deadline_to);
                if (this.filters.amount_min) p.set('amount_min', this.filters.amount_min);
                if (this.filters.amount_max) p.set('amount_max', this.filters.amount_max);
                const qs = p.toString();
                const res = await fetch(`/tablero/cards${qs ? '?' + qs : ''}`, { headers: { 'Accept': 'application/json' } });
                if (res.ok) { this.columns = (await res.json()).columns; }
            } catch (e) { console.error('Error loading cards:', e); }
            this.loading = false;
        },

        openCard(card) { window.location.href = `/ofertas/${card.id}`; },
        openOffer(id) { window.location.href = `/ofertas/${id}`; },

        async moveCard(card, targetEstado) {
            try {
                const res = await fetch(`/tablero/${card.id}/move`, {
                    method: 'PATCH',
                    headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                    body: JSON.stringify({ estado: targetEstado }),
                });
                if (res.ok) {
                    const old = card.estado;
                    if (this.columns[old]) this.columns[old].cards = this.columns[old].cards.filter(c => c.id !== card.id);
                    card.estado = targetEstado;
                    if (this.columns[targetEstado]) this.columns[targetEstado].cards.push(card);
                } else { alert((await res.json()).error || 'Error al mover'); }
            } catch (e) { console.error('Move error:', e); }
        },

        allowedMoves(estado) {
            return { borrador: ['en_preparacion'], en_preparacion: ['borrador', 'listo'], listo: ['en_preparacion', 'enviado'], enviado: ['adjudicada', 'perdida', 'impugnacion'], adjudicada: [], perdida: ['impugnacion'], impugnacion: ['en_preparacion', 'adjudicada', 'perdida'] }[estado] || [];
        },
        moveLabel(e) { return { borrador: 'Oportunidades', en_preparacion: 'En proceso', listo: 'Lista', enviado: 'Entregada', adjudicada: 'Adjudicada', perdida: 'Perdida', impugnacion: 'Impugnación' }[e] || e; },
        moveBtnClass(e) { return { borrador: 'bg-gray-100 text-gray-600 hover:bg-gray-200', en_preparacion: 'bg-blue-50 text-blue-700 hover:bg-blue-100', listo: 'bg-green-50 text-green-700 hover:bg-green-100', enviado: 'bg-purple-50 text-purple-700 hover:bg-purple-100', adjudicada: 'bg-yellow-50 text-yellow-800 hover:bg-yellow-100', perdida: 'bg-red-50 text-red-700 hover:bg-red-100', impugnacion: 'bg-orange-50 text-orange-700 hover:bg-orange-100' }[e] || 'bg-gray-100 text-gray-600'; },
        columnHeaderClass(c) { return { gray: 'bg-gray-100 text-gray-600', blue: 'bg-blue-100 text-blue-600', green: 'bg-green-100 text-green-600', purple: 'bg-purple-100 text-purple-600', yellow: 'bg-yellow-100 text-yellow-700', red: 'bg-red-100 text-red-600', orange: 'bg-orange-100 text-orange-600' }[c] || 'bg-gray-100 text-gray-600'; },
        columnIcon(i) {
            const s = 'class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"';
            return {
                building: `<svg ${s}><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Zm0 3h.008v.008h-.008v-.008Z"/></svg>`,
                clipboard: `<svg ${s}><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15a2.25 2.25 0 0 1 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z"/></svg>`,
                'check-circle': `<svg ${s}><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>`,
                'paper-plane': `<svg ${s}><path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5"/></svg>`,
                trophy: `<svg ${s}><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 0 1-.982-3.172M9.497 14.25a7.454 7.454 0 0 0 .981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 0 0 7.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.41 9.71 2.25 12 2.25c2.291 0 4.545.16 6.75.47v1.516M18.75 4.236c.982.143 1.954.317 2.916.52A6.003 6.003 0 0 1 16.27 9.728M18.75 4.236V4.5c0 2.108-.966 3.99-2.48 5.228m0 0a6.04 6.04 0 0 1-2.02 1.118c-.742.26-1.508.402-2.25.402s-1.508-.143-2.25-.402a6.04 6.04 0 0 1-2.02-1.118"/></svg>`,
                'x-circle': `<svg ${s}><path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>`,
                scale: `<svg ${s}><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v17.25m0 0c-1.472 0-2.882.265-4.185.75M12 20.25c1.472 0 2.882.265 4.185.75M18.75 4.97A48.416 48.416 0 0 0 12 4.5c-2.291 0-4.545.16-6.75.47m13.5 0c1.01.143 2.01.317 3 .52m-3-.52 2.62 10.726c.122.499-.106 1.028-.589 1.202a5.988 5.988 0 0 1-2.031.352 5.988 5.988 0 0 1-2.031-.352c-.483-.174-.711-.703-.59-1.202L18.75 4.971Zm-16.5.52c.99-.203 1.99-.377 3-.52m0 0 2.62 10.726c.122.499-.106 1.028-.589 1.202a5.989 5.989 0 0 1-2.031.352 5.989 5.989 0 0 1-2.031-.352c-.483-.174-.711-.703-.59-1.202L5.25 4.971Z"/></svg>`,
            }[i] || `<svg ${s}><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>`;
        },
        deadlineBadge(c) { return { red: 'bg-red-100 text-red-700', amber: 'bg-amber-100 text-amber-700', yellow: 'bg-yellow-100 text-yellow-700', green: 'bg-green-100 text-green-700', gray: 'bg-gray-100 text-gray-600' }[c] || 'bg-gray-100 text-gray-600'; },
        formatAmount(a, c) { if (!a || a == 0) return ''; return (c === 'USD' ? 'US$' : 'RD$') + Number(a).toLocaleString('en', { minimumFractionDigits: 0, maximumFractionDigits: 0 }); },

        // ── Calendar ────────────────────────
        get calMonthLabel() {
            const months = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
            return months[this.calMonth] + ' ' + this.calYear;
        },

        get selectedDateLabel() {
            if (!this.selectedDate) return '';
            const d = new Date(this.selectedDate + 'T12:00:00');
            return d.toLocaleDateString('es-DO', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
        },

        get selectedDateEvents() {
            if (!this.selectedDate) return [];
            return this.calEvents.filter(e => e.date === this.selectedDate);
        },

        prevMonth() { if (this.calMonth === 0) { this.calMonth = 11; this.calYear--; } else { this.calMonth--; } this.loadCalendar(); },
        nextMonth() { if (this.calMonth === 11) { this.calMonth = 0; this.calYear++; } else { this.calMonth++; } this.loadCalendar(); },
        goToday() { const n = new Date(); this.calYear = n.getFullYear(); this.calMonth = n.getMonth(); this.loadCalendar(); },

        async loadCalendar() {
            this.loading = true;
            this.selectedDate = null;
            const m = String(this.calMonth + 1).padStart(2, '0');
            try {
                const res = await fetch(`/tablero/calendar?month=${this.calYear}-${m}`, { headers: { 'Accept': 'application/json' } });
                if (res.ok) {
                    const json = await res.json();
                    this.calEvents = json.events;
                    this.buildCells();
                }
            } catch (e) { console.error('Calendar error:', e); }
            this.loading = false;
        },

        buildCells() {
            const cells = [];
            const first = new Date(this.calYear, this.calMonth, 1);
            const last = new Date(this.calYear, this.calMonth + 1, 0);
            const today = new Date();
            const todayStr = today.getFullYear() + '-' + String(today.getMonth()+1).padStart(2,'0') + '-' + String(today.getDate()).padStart(2,'0');

            // Monday=0 start. JS getDay(): 0=Sun, so adjust
            let startDay = first.getDay() === 0 ? 6 : first.getDay() - 1;

            // Previous month padding
            const prevLast = new Date(this.calYear, this.calMonth, 0);
            for (let i = startDay - 1; i >= 0; i--) {
                const d = prevLast.getDate() - i;
                const pm = this.calMonth === 0 ? 12 : this.calMonth;
                const py = this.calMonth === 0 ? this.calYear - 1 : this.calYear;
                const ds = py + '-' + String(pm).padStart(2,'0') + '-' + String(d).padStart(2,'0');
                cells.push({ key: 'p'+d, day: d, date: ds, isCurrentMonth: false, isToday: false, events: this.calEvents.filter(e => e.date === ds) });
            }

            // Current month
            for (let d = 1; d <= last.getDate(); d++) {
                const ds = this.calYear + '-' + String(this.calMonth+1).padStart(2,'0') + '-' + String(d).padStart(2,'0');
                cells.push({ key: 'c'+d, day: d, date: ds, isCurrentMonth: true, isToday: ds === todayStr, events: this.calEvents.filter(e => e.date === ds) });
            }

            // Next month padding (fill to 42 cells = 6 rows)
            const remaining = 42 - cells.length;
            for (let d = 1; d <= remaining; d++) {
                const nm = this.calMonth === 11 ? 1 : this.calMonth + 2;
                const ny = this.calMonth === 11 ? this.calYear + 1 : this.calYear;
                const ds = ny + '-' + String(nm).padStart(2,'0') + '-' + String(d).padStart(2,'0');
                cells.push({ key: 'n'+d, day: d, date: ds, isCurrentMonth: false, isToday: false, events: this.calEvents.filter(e => e.date === ds) });
            }

            this.calCells = cells;
        },

        calEventClass(color) {
            return { gray: 'bg-gray-100 text-gray-700', blue: 'bg-blue-100 text-blue-700', green: 'bg-green-100 text-green-700', purple: 'bg-purple-100 text-purple-700', yellow: 'bg-yellow-100 text-yellow-800', red: 'bg-red-100 text-red-700', orange: 'bg-orange-100 text-orange-700' }[color] || 'bg-gray-100 text-gray-700';
        },
        calDotClass(color) {
            return { gray: 'bg-gray-500', blue: 'bg-blue-500', green: 'bg-green-500', purple: 'bg-purple-500', yellow: 'bg-yellow-500', red: 'bg-red-500', orange: 'bg-orange-500' }[color] || 'bg-gray-500';
        },
    };
}
</script>
@endsection
