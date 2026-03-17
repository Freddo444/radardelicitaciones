@extends('layouts.app')
@section('title', $personal->nombre)

@section('content')
<div class="mx-auto max-w-4xl px-4 py-10 sm:px-6 lg:px-8">

    <div class="mb-6 flex items-center justify-between">
        <a href="{{ route('personal.index') }}" class="text-sm text-blue-600 hover:underline">← Personal</a>
        <span class="rounded-md px-2 py-1 text-xs font-medium {{ $personal->active ? 'bg-green-50 text-green-700 ring-1 ring-inset ring-green-600/20' : 'bg-gray-100 text-gray-500 ring-1 ring-inset ring-gray-500/10' }}">
            {{ $personal->active ? 'Activo' : 'Inactivo' }}
        </span>
    </div>

    {{-- Profile form --}}
    <form method="POST" action="{{ route('personal.update', $personal) }}" enctype="multipart/form-data" class="space-y-8">
        @csrf

        {{-- Section 1: Personal info --}}
        <div class="rounded-xl border border-gray-200 bg-white">
            <div class="border-b border-gray-200 px-6 py-4">
                <h2 class="text-sm font-semibold text-gray-900">Datos personales</h2>
            </div>
            <div class="grid grid-cols-1 gap-x-6 gap-y-5 px-6 py-6 sm:grid-cols-2">

                <div class="sm:col-span-2">
                    <label for="nombre" class="block text-sm font-medium text-gray-900">Nombre completo <span class="text-red-500">*</span></label>
                    <input type="text" id="nombre" name="nombre" required
                           value="{{ old('nombre', $personal->nombre) }}"
                           class="mt-1.5 w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600"/>
                </div>

                <div>
                    <label for="cedula" class="block text-sm font-medium text-gray-900">Cédula</label>
                    <input type="text" id="cedula" name="cedula" maxlength="20"
                           value="{{ old('cedula', $personal->cedula) }}"
                           class="mt-1.5 w-full rounded-md bg-white px-3 py-2 text-sm font-mono text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600"/>
                </div>

                <div>
                    <label for="fecha_nac" class="block text-sm font-medium text-gray-900">Fecha de nacimiento</label>
                    <input type="date" id="fecha_nac" name="fecha_nac"
                           value="{{ old('fecha_nac', $personal->fecha_nac?->format('Y-m-d')) }}"
                           class="mt-1.5 w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600"/>
                </div>

                <div class="sm:col-span-2">
                    <label for="cargo" class="block text-sm font-medium text-gray-900">Cargo / Especialidad</label>
                    <input type="text" id="cargo" name="cargo"
                           value="{{ old('cargo', $personal->cargo) }}"
                           class="mt-1.5 w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600"/>
                </div>

                <div class="flex items-center gap-x-3 sm:col-span-2">
                    <input type="checkbox" id="active" name="active" value="1"
                           {{ old('active', $personal->active) ? 'checked' : '' }}
                           class="size-4 rounded border-gray-300 text-blue-600 focus:ring-blue-600"/>
                    <label for="active" class="text-sm text-gray-700">Activo (aparece en selección de ofertas)</label>
                </div>

            </div>
        </div>

        {{-- Section 2: Education --}}
        <div class="rounded-xl border border-gray-200 bg-white">
            <div class="border-b border-gray-200 px-6 py-4">
                <h2 class="text-sm font-semibold text-gray-900">Formación académica</h2>
            </div>
            <div class="grid grid-cols-1 gap-x-6 gap-y-5 px-6 py-6 sm:grid-cols-2">

                <div>
                    <label for="nivel_educativo" class="block text-sm font-medium text-gray-900">Nivel educativo</label>
                    <select id="nivel_educativo" name="nivel_educativo"
                            class="mt-1.5 w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600">
                        <option value="">— Sin especificar —</option>
                        @foreach(\App\Models\Personnel::$nivelesEducativos as $val => $lbl)
                            <option value="{{ $val }}" {{ old('nivel_educativo', $personal->nivel_educativo) === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="anio_titulo" class="block text-sm font-medium text-gray-900">Año de titulación</label>
                    <input type="number" id="anio_titulo" name="anio_titulo" min="1950" max="2100"
                           value="{{ old('anio_titulo', $personal->anio_titulo) }}"
                           class="mt-1.5 w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600"/>
                </div>

                <div>
                    <label for="titulo" class="block text-sm font-medium text-gray-900">Titulación / Carrera</label>
                    <input type="text" id="titulo" name="titulo"
                           placeholder="Ingeniería Civil"
                           value="{{ old('titulo', $personal->titulo) }}"
                           class="mt-1.5 w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600"/>
                </div>

                <div>
                    <label for="institucion" class="block text-sm font-medium text-gray-900">Institución</label>
                    <input type="text" id="institucion" name="institucion"
                           placeholder="INTEC, UASD, PUCMM…"
                           value="{{ old('institucion', $personal->institucion) }}"
                           class="mt-1.5 w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600"/>
                </div>

                <div>
                    <label for="idiomas" class="block text-sm font-medium text-gray-900">Idiomas</label>
                    <input type="text" id="idiomas" name="idiomas"
                           placeholder="Español, Inglés, Francés"
                           value="{{ old('idiomas', $personal->idiomas ? implode(', ', $personal->idiomas) : '') }}"
                           class="mt-1.5 w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600"/>
                    <p class="mt-1 text-xs text-gray-400">Separados por coma</p>
                </div>

                <div>
                    <label for="skills" class="block text-sm font-medium text-gray-900">Habilidades / Certificaciones</label>
                    <input type="text" id="skills" name="skills"
                           placeholder="AutoCAD, PMP, OSHA 30"
                           value="{{ old('skills', $personal->skills ? implode(', ', $personal->skills) : '') }}"
                           class="mt-1.5 w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600"/>
                    <p class="mt-1 text-xs text-gray-400">Separados por coma</p>
                </div>

                <div class="sm:col-span-2">
                    <label for="photo" class="block text-sm font-medium text-gray-900">Foto de perfil</label>
                    <input type="file" id="photo" name="photo" accept="image/*"
                           class="mt-1.5 w-full text-sm text-gray-900 file:mr-3 file:rounded-md file:border-0 file:bg-blue-50 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-blue-700 hover:file:bg-blue-100"/>
                    @if($personal->photo_path)
                        <p class="mt-1 text-xs text-gray-400">Ya tiene foto. Sube una nueva para reemplazarla.</p>
                    @endif
                </div>

            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit"
                    class="inline-flex items-center gap-x-2 rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-xs hover:bg-blue-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                Guardar cambios
            </button>
        </div>
    </form>

    {{-- Experience entries --}}
    <div class="mt-12">
        <div class="flex items-center justify-between">
            <h2 class="text-sm font-semibold text-gray-900">Experiencia profesional</h2>
            <button command="show-modal" commandfor="add-exp-drawer"
                    class="inline-flex items-center gap-x-1.5 rounded-md bg-white px-2.5 py-1.5 text-sm font-semibold text-gray-900 shadow-xs inset-ring inset-ring-gray-300 hover:bg-gray-50">
                <svg viewBox="0 0 20 20" fill="currentColor" class="-ml-0.5 size-4">
                    <path d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z"/>
                </svg>
                Agregar
            </button>
        </div>

        @if($personal->experiences->isEmpty())
            <p class="mt-4 text-sm text-gray-400 italic">Sin experiencia registrada aún.</p>
        @else
            <ul role="list" class="mt-4 divide-y divide-gray-100 rounded-xl border border-gray-200 bg-white">
                @foreach($personal->experiences as $exp)
                <li class="flex items-start justify-between gap-x-6 px-4 py-4">
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-semibold text-gray-900">{{ $exp->cargo }}</p>
                        <p class="mt-0.5 text-sm text-gray-600">{{ $exp->empresa }}</p>
                        <p class="mt-0.5 text-xs text-gray-400">{{ $exp->periodoLabel() }}</p>
                        @if($exp->descripcion)
                            <p class="mt-1 text-xs text-gray-500">{{ Str::limit($exp->descripcion, 120) }}</p>
                        @endif
                    </div>
                    <div class="flex shrink-0 items-center gap-x-1">
                        <button type="button"
                                onclick="openEditExpDrawer({{ $exp->id }}, '{{ e($exp->empresa) }}', '{{ e($exp->cargo) }}', '{{ $exp->fecha_inicio->format('Y-m-d') }}', '{{ $exp->fecha_fin?->format('Y-m-d') }}', {{ json_encode($exp->descripcion) }})"
                                command="show-modal" commandfor="edit-exp-drawer"
                                class="rounded-md p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50">
                            <span class="sr-only">Editar</span>
                            <svg viewBox="0 0 20 20" fill="currentColor" class="size-4">
                                <path d="M2.695 14.763l-1.262 3.154a.5.5 0 0 0 .65.65l3.155-1.262a4 4 0 0 0 1.343-.885L17.5 5.5a2.121 2.121 0 0 0-3-3L3.58 13.42a4 4 0 0 0-.885 1.343Z"/>
                            </svg>
                        </button>
                        <form method="POST" action="{{ route('personal.experience.destroy', [$personal, $exp]) }}"
                              onsubmit="return confirm('¿Eliminar esta entrada de experiencia?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="rounded-md p-1.5 text-gray-400 hover:text-red-500 hover:bg-red-50">
                                <span class="sr-only">Eliminar</span>
                                <svg viewBox="0 0 20 20" fill="currentColor" class="size-4">
                                    <path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 0 0 6 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 1 0 .23 1.482l.149-.022.841 10.518A2.75 2.75 0 0 0 7.596 19h4.807a2.75 2.75 0 0 0 2.742-2.53l.841-10.52.149.023a.75.75 0 0 0 .23-1.482A41.03 41.03 0 0 0 14 4.193V3.75A2.75 2.75 0 0 0 11.25 1h-2.5ZM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4ZM8.58 7.72a.75.75 0 0 0-1.5.06l.3 7.5a.75.75 0 1 0 1.5-.06l-.3-7.5Zm4.34.06a.75.75 0 1 0-1.5-.06l-.3 7.5a.75.75 0 1 0 1.5.06l.3-7.5Z" clip-rule="evenodd"/>
                                </svg>
                            </button>
                        </form>
                    </div>
                </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>

{{-- Add experience drawer --}}
<el-dialog>
    <dialog id="add-exp-drawer" aria-labelledby="add-exp-title"
            class="fixed inset-0 size-auto max-h-none max-w-none overflow-hidden bg-transparent backdrop:bg-transparent">
        <div tabindex="0" class="absolute inset-0 pl-10 focus:outline-none sm:pl-16">
            <el-dialog-panel class="ml-auto block size-full max-w-md transform transition duration-500 ease-in-out data-closed:translate-x-full sm:duration-700">
                <div class="relative flex h-full flex-col divide-y divide-gray-200 bg-white shadow-xl">
                    <div class="flex items-start justify-between px-4 py-6 sm:px-6">
                        <h2 id="add-exp-title" class="text-base font-semibold text-gray-900">Agregar experiencia</h2>
                        <button type="button" command="close" commandfor="add-exp-drawer" class="relative ml-3 rounded-md text-gray-400 hover:text-gray-500">
                            <span class="sr-only">Cerrar</span>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="size-6"><path d="M6 18 18 6M6 6l12 12" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                    </div>
                    <form method="POST" action="{{ route('personal.experience.store', $personal) }}" class="flex min-h-0 flex-1 flex-col">
                        @csrf
                        @include('personal._experience_fields')
                        <div class="flex shrink-0 justify-end gap-x-3 px-4 py-4">
                            <button type="button" command="close" commandfor="add-exp-drawer"
                                    class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-xs inset-ring inset-ring-gray-300 hover:inset-ring-gray-400">Cancelar</button>
                            <button type="submit" class="inline-flex justify-center rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-xs hover:bg-blue-500">Agregar</button>
                        </div>
                    </form>
                </div>
            </el-dialog-panel>
        </div>
    </dialog>
</el-dialog>

{{-- Edit experience drawer --}}
<el-dialog>
    <dialog id="edit-exp-drawer" aria-labelledby="edit-exp-title"
            class="fixed inset-0 size-auto max-h-none max-w-none overflow-hidden bg-transparent backdrop:bg-transparent">
        <div tabindex="0" class="absolute inset-0 pl-10 focus:outline-none sm:pl-16">
            <el-dialog-panel class="ml-auto block size-full max-w-md transform transition duration-500 ease-in-out data-closed:translate-x-full sm:duration-700">
                <div class="relative flex h-full flex-col divide-y divide-gray-200 bg-white shadow-xl">
                    <div class="flex items-start justify-between px-4 py-6 sm:px-6">
                        <h2 id="edit-exp-title" class="text-base font-semibold text-gray-900">Editar experiencia</h2>
                        <button type="button" command="close" commandfor="edit-exp-drawer" class="relative ml-3 rounded-md text-gray-400 hover:text-gray-500">
                            <span class="sr-only">Cerrar</span>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="size-6"><path d="M6 18 18 6M6 6l12 12" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                    </div>
                    <form id="edit-exp-form" method="POST" action="" class="flex min-h-0 flex-1 flex-col">
                        @csrf
                        @include('personal._experience_fields', ['editing' => true])
                        <div class="flex shrink-0 justify-end gap-x-3 px-4 py-4">
                            <button type="button" command="close" commandfor="edit-exp-drawer"
                                    class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-xs inset-ring inset-ring-gray-300 hover:inset-ring-gray-400">Cancelar</button>
                            <button type="submit" class="inline-flex justify-center rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-xs hover:bg-blue-500">Guardar</button>
                        </div>
                    </form>
                </div>
            </el-dialog-panel>
        </div>
    </dialog>
</el-dialog>

<script>
function openEditExpDrawer(id, empresa, cargo, fechaInicio, fechaFin, descripcion) {
    const base = '/personal/{{ $personal->id }}/experience/';
    document.getElementById('edit-exp-form').action = base + id;
    document.getElementById('edit-empresa').value = empresa;
    document.getElementById('edit-cargo').value = cargo;
    document.getElementById('edit-fecha_inicio').value = fechaInicio;
    document.getElementById('edit-fecha_fin').value = fechaFin || '';
    document.getElementById('edit-descripcion').value = descripcion || '';
}
</script>

@endsection
