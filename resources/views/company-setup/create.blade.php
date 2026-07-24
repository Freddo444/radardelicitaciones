<!DOCTYPE html>
<html lang="es" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }} — Configurar empresa</title>
    <link rel="apple-touch-icon" sizes="180x180" href="/images/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/images/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/images/favicon-16x16.png">
    <link rel="shortcut icon" href="/favicon.ico">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <x-umami-script />
</head>
<body class="h-full">

<div class="min-h-full" x-data="companySetup()">
    <div class="mx-auto max-w-2xl px-4 py-12 sm:px-6 lg:px-8">

        <h1 class="text-2xl font-bold text-gray-900">Configurar empresa</h1>
        <p class="mt-1 text-sm text-gray-500">Ingresa tu número RPE para autocompletar los datos desde la DGCP.</p>

        @if($errors->any())
        <div class="mt-4 rounded-md bg-red-50 p-3 text-sm text-red-700">
            <ul class="list-disc pl-4 space-y-1">
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        {{-- Step 1: RPE Lookup --}}
        <div class="mt-8 rounded-lg bg-white p-6 shadow ring-1 ring-gray-900/5" x-show="!formReady">
            <label for="rpe_input" class="block text-sm font-medium text-gray-700">Número RPE</label>
            <p class="mt-1 text-xs text-gray-500">Registro de Proveedores del Estado — se encuentra en tu certificación de la DGCP.</p>
            <div class="mt-3 flex gap-3">
                <input id="rpe_input" type="number" x-model="rpeInput" min="1" placeholder="Ej: 7835"
                       @keydown.enter.prevent="lookup()"
                       class="block w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus:outline-2 focus:-outline-offset-2 focus:outline-blue-600"/>
                <button @click="lookup()" :disabled="loading || !rpeInput"
                        class="shrink-0 rounded-md bg-blue-600 px-5 py-2 text-sm font-semibold text-white hover:bg-blue-500 disabled:opacity-50">
                    <span x-show="!loading">Buscar</span>
                    <span x-show="loading">Buscando...</span>
                </button>
            </div>

            {{-- Any lookup failure (DGCP down, server error) still offers a way
                 forward, so a broken API never traps the user at step one. --}}
            <div x-show="lookupError" x-cloak class="mt-4 rounded-md bg-amber-50 p-3 text-sm text-amber-800">
                <p x-text="lookupError"></p>
                <button type="button" @click="startManualEntry()" class="mt-2 block font-semibold text-amber-900 underline">
                    Continuar sin autocompletar
                </button>
            </div>

            <div x-show="notFound" x-cloak class="mt-4 rounded-md bg-blue-50 p-3 text-sm text-blue-800">
                <p class="font-medium">¿Aún no estás registrado como proveedor en la DGCP? No hay problema.</p>
                <p class="mt-1 text-blue-700">Puedes configurar tu empresa manualmente y elegir los rubros que te interesa monitorear. Cuando te registres en la DGCP, podrás sincronizar tus rubros con un clic.</p>
                <button type="button" @click="startManualEntry()" class="mt-2 inline-flex rounded-md bg-blue-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-blue-500">
                    Configurar manualmente
                </button>
            </div>

            <div class="mt-4 border-t border-gray-100 pt-3 text-center">
                <button type="button" @click="startManualEntry()" class="text-xs font-medium text-gray-500 hover:text-gray-700">
                    ¿Prefieres llenar los datos tú mismo? Configurar sin RPE
                </button>
            </div>
        </div>

        {{-- Step 2: Company form (populated or manual) --}}
        <form method="POST" action="{{ route('company-setup.store') }}" class="mt-6 space-y-6" x-show="formReady" x-cloak>
            @csrf

            {{-- Found banner --}}
            <div x-show="wasLookedUp" class="rounded-md bg-green-50 p-3 text-sm text-green-700">
                Datos encontrados en la DGCP. Verifica y ajusta si es necesario.
            </div>

            <input type="hidden" name="rpe_numero" :value="form.rpe_numero">
            <input type="hidden" name="registro_mercantil" :value="form.registro_mercantil">

            <div class="rounded-lg bg-white p-6 shadow ring-1 ring-gray-900/5 space-y-5">
                <h2 class="text-lg font-semibold text-gray-900">Datos de la empresa</h2>

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="razon_social" class="block text-sm font-medium text-gray-700">Razón social *</label>
                        <input id="razon_social" type="text" name="razon_social" required x-model="form.razon_social"
                               class="mt-1 block w-full rounded-md bg-white px-3 py-1.5 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus:outline-2 focus:-outline-offset-2 focus:outline-blue-600"/>
                    </div>

                    <div>
                        <label for="rnc" class="block text-sm font-medium text-gray-700">RNC *</label>
                        <input id="rnc" type="text" name="rnc" required x-model="form.rnc" maxlength="20"
                               class="mt-1 block w-full rounded-md bg-white px-3 py-1.5 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus:outline-2 focus:-outline-offset-2 focus:outline-blue-600"/>
                    </div>

                    <div>
                        <label for="nombre_comercial" class="block text-sm font-medium text-gray-700">Nombre comercial</label>
                        <input id="nombre_comercial" type="text" name="nombre_comercial" x-model="form.nombre_comercial"
                               class="mt-1 block w-full rounded-md bg-white px-3 py-1.5 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus:outline-2 focus:-outline-offset-2 focus:outline-blue-600"/>
                    </div>

                    <div>
                        <label for="telefono" class="block text-sm font-medium text-gray-700">Teléfono</label>
                        <input id="telefono" type="text" name="telefono" x-model="form.telefono"
                               class="mt-1 block w-full rounded-md bg-white px-3 py-1.5 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus:outline-2 focus:-outline-offset-2 focus:outline-blue-600"/>
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                        <input id="email" type="email" name="email" x-model="form.email"
                               class="mt-1 block w-full rounded-md bg-white px-3 py-1.5 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus:outline-2 focus:-outline-offset-2 focus:outline-blue-600"/>
                    </div>

                    <div class="sm:col-span-2">
                        <label for="direccion" class="block text-sm font-medium text-gray-700">Dirección</label>
                        <input id="direccion" type="text" name="direccion" x-model="form.direccion"
                               class="mt-1 block w-full rounded-md bg-white px-3 py-1.5 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus:outline-2 focus:-outline-offset-2 focus:outline-blue-600"/>
                    </div>

                    <div>
                        <label for="municipio" class="block text-sm font-medium text-gray-700">Municipio</label>
                        <input id="municipio" type="text" name="municipio" x-model="form.municipio"
                               class="mt-1 block w-full rounded-md bg-white px-3 py-1.5 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus:outline-2 focus:-outline-offset-2 focus:outline-blue-600"/>
                    </div>

                    <div>
                        <label for="provincia" class="block text-sm font-medium text-gray-700">Provincia</label>
                        <input id="provincia" type="text" name="provincia" x-model="form.provincia"
                               class="mt-1 block w-full rounded-md bg-white px-3 py-1.5 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus:outline-2 focus:-outline-offset-2 focus:outline-blue-600"/>
                    </div>
                </div>
            </div>

            {{-- Manual rubro picker (RPE not found / manual entry) --}}
            <div x-show="!wasLookedUp" class="rounded-lg bg-white p-6 shadow ring-1 ring-gray-900/5">
                <h2 class="text-lg font-semibold text-gray-900">¿Qué rubros quieres monitorear?</h2>
                <p class="mt-1 text-xs text-gray-500">Busca por palabra clave (ej: "construcción", "alimentos") o por código UNSPSC. Elige los que ofrece tu empresa — así sabremos qué licitaciones avisarte.</p>

                <div class="relative mt-3">
                    <input type="text" x-model="rubroQuery" @input.debounce.300ms="searchRubros()"
                           placeholder="Buscar rubro..."
                           class="block w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus:outline-2 focus:-outline-offset-2 focus:outline-blue-600"/>
                    <div x-show="rubroResults.length > 0" x-cloak
                         class="absolute z-10 mt-1 max-h-56 w-full overflow-y-auto rounded-md border border-gray-200 bg-white shadow-lg">
                        <template x-for="res in rubroResults" :key="res.code">
                            <button type="button" @click="addRubro(res)"
                                    class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm hover:bg-blue-50">
                                <span class="font-mono text-xs text-gray-400" x-text="res.code"></span>
                                <span class="text-gray-900" x-text="res.name"></span>
                            </button>
                        </template>
                    </div>
                    <p x-show="rubroSearching" x-cloak class="mt-1 text-xs text-gray-400">Buscando...</p>
                    <p x-show="!rubroSearching && rubroQuery.length >= 3 && rubroResults.length === 0" x-cloak class="mt-1 text-xs text-gray-400">Sin resultados. Prueba otra palabra.</p>
                </div>

                {{-- Selected rubros as chips --}}
                <div x-show="rubros.length > 0" x-cloak class="mt-4 flex flex-wrap gap-2">
                    <template x-for="(rubro, i) in rubros" :key="rubro.code">
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-blue-50 py-1 pl-3 pr-2 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-200">
                            <span x-text="rubro.name"></span>
                            <button type="button" @click="rubros.splice(i, 1)" class="text-blue-400 hover:text-blue-700">
                                <svg viewBox="0 0 20 20" fill="currentColor" class="size-3.5"><path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"/></svg>
                            </button>
                        </span>
                    </template>
                </div>
                <p x-show="rubros.length === 0" x-cloak class="mt-3 text-xs text-amber-600">Agrega al menos un rubro para empezar a recibir avisos de licitaciones.</p>
            </div>

            {{-- Rubros from DGCP --}}
            <div x-show="wasLookedUp && rubros.length > 0" class="rounded-lg bg-white p-6 shadow ring-1 ring-gray-900/5">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Rubros registrados en la DGCP</h2>
                        <p class="mt-1 text-xs text-gray-500">
                            <span x-text="rubros.filter(r => r.selected).length"></span> de
                            <span x-text="rubros.length"></span> seleccionados — desactiva los que no te interesa monitorear.
                        </p>
                    </div>
                    <div class="flex gap-2">
                        <button type="button" @click="rubros.forEach(r => r.selected = true)"
                                class="text-xs font-medium text-blue-600 hover:text-blue-500">Todos</button>
                        <span class="text-gray-300">|</span>
                        <button type="button" @click="rubros.forEach(r => r.selected = false)"
                                class="text-xs font-medium text-blue-600 hover:text-blue-500">Ninguno</button>
                    </div>
                </div>

                <div class="mt-4 max-h-72 overflow-y-auto divide-y divide-gray-100 rounded-md border border-gray-200">
                    <template x-for="(rubro, i) in rubros" :key="rubro.code">
                        <label class="flex items-center gap-3 px-3 py-2 hover:bg-gray-50 cursor-pointer text-sm">
                            <input type="checkbox" x-model="rubro.selected"
                                   class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="text-gray-400 font-mono text-xs" x-text="rubro.code"></span>
                            <span class="text-gray-900" x-text="rubro.name"></span>
                        </label>
                    </template>
                </div>
            </div>

            {{-- Hidden inputs for selected rubros — rendered for both the DGCP
                 and manual paths so the selection always submits. --}}
            <template x-for="(rubro, i) in rubros.filter(r => r.selected)" :key="'h-'+rubro.code">
                <div>
                    <input type="hidden" :name="'rubros['+i+'][code]'" :value="rubro.code">
                    <input type="hidden" :name="'rubros['+i+'][name]'" :value="rubro.name">
                </div>
            </template>

            <div class="flex items-center justify-between gap-4">
                <button type="button" x-show="wasLookedUp" @click="backToRpeSearch()"
                        class="text-sm font-medium text-gray-500 hover:text-gray-700">
                    ← Buscar otro RPE
                </button>
                <button type="button" x-show="formReady && !wasLookedUp" @click="backToRpeSearch()"
                        class="text-sm font-medium text-gray-500 hover:text-gray-700">
                    ← Volver al buscador RPE
                </button>
                <div class="ml-auto">
                    <button type="submit"
                            class="inline-flex items-center rounded-md bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-500">
                        Crear empresa y continuar
                    </button>
                </div>
            </div>
        </form>

    </div>
</div>

<script>
function companySetup() {
    return {
        rpeInput: '',
        loading: false,
        lookupError: null,
        notFound: false,
        formReady: false,
        wasLookedUp: false,
        rubros: [],
        rubroQuery: '',
        rubroResults: [],
        rubroSearching: false,
        form: {
            razon_social: '', rnc: '', nombre_comercial: '',
            telefono: '', email: '', direccion: '',
            municipio: '', provincia: '', rpe_numero: '', registro_mercantil: '',
        },

        async searchRubros() {
            const q = this.rubroQuery.trim();
            if (q.length < 3) { this.rubroResults = []; return; }
            this.rubroSearching = true;
            try {
                const res = await fetch(`/configurar-empresa/buscar-rubro?q=${encodeURIComponent(q)}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                const data = await res.json();
                // Hide rubros already selected
                const have = new Set(this.rubros.map(r => r.code));
                this.rubroResults = (data || []).filter(r => !have.has(r.code));
            } catch (e) {
                this.rubroResults = [];
            } finally {
                this.rubroSearching = false;
            }
        },

        addRubro(res) {
            if (!this.rubros.some(r => r.code === res.code)) {
                this.rubros.push({ code: res.code, name: res.name, selected: true });
            }
            this.rubroQuery = '';
            this.rubroResults = [];
        },

        async lookup() {
            if (!this.rpeInput) return;
            this.loading = true;
            this.lookupError = null;
            this.notFound = false;

            try {
                const res = await fetch('{{ route("company-setup.lookup-rpe") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ rpe: parseInt(this.rpeInput, 10) }),
                });
                let data = {};
                try {
                    data = await res.json();
                } catch (e) {
                    this.lookupError = 'Respuesta inválida del servidor.';
                    return;
                }

                if (!res.ok) {
                    this.lookupError = data.error || data.message || 'No se pudo consultar el RPE.';
                    return;
                }

                if (data.found) {
                    this.form = { ...this.form, ...data.company };
                    this.rubros = (data.rubros || []).map(r => ({ ...r, selected: true }));
                    this.wasLookedUp = true;
                    this.formReady = true;
                } else if (data.error) {
                    this.lookupError = data.error;
                } else {
                    this.notFound = true;
                }
            } catch (e) {
                this.lookupError = 'Error de conexión al buscar el RPE.';
            } finally {
                this.loading = false;
            }
        },

        startManualEntry() {
            this.notFound = false;
            this.lookupError = null;
            this.wasLookedUp = false;
            this.rubros = [];
            // Preserve a typed RPE so the company is still saved with it — the
            // user can then sync rubros from the DGCP later (once it's back up)
            // via the "Sincronizar con la DGCP" button, instead of being stuck
            // with an empty, unmatchable company.
            if (this.rpeInput) {
                this.form.rpe_numero = parseInt(this.rpeInput, 10);
            }
            this.formReady = true;
        },

        backToRpeSearch() {
            this.formReady = false;
            this.wasLookedUp = false;
            this.notFound = false;
            this.lookupError = null;
            this.rubros = [];
            this.form = {
                razon_social: '', rnc: '', nombre_comercial: '',
                telefono: '', email: '', direccion: '',
                municipio: '', provincia: '', rpe_numero: '', registro_mercantil: '',
            };
        },
    }
}
</script>

<x-umami-track />
<x-tawk-script />
</body>
</html>
