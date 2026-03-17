@php $editing = $editing ?? false; $prefix = $editing ? 'edit-' : ''; @endphp
<div class="flex-1 space-y-5 overflow-y-auto px-4 py-6 sm:px-6">

    <div>
        <label for="{{ $prefix }}empresa" class="block text-sm font-medium text-gray-900">Empresa / Organización <span class="text-red-500">*</span></label>
        <input type="text" id="{{ $prefix }}empresa" name="empresa" required
               class="mt-1.5 w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600"/>
    </div>

    <div>
        <label for="{{ $prefix }}cargo" class="block text-sm font-medium text-gray-900">Cargo <span class="text-red-500">*</span></label>
        <input type="text" id="{{ $prefix }}cargo" name="cargo" required
               class="mt-1.5 w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600"/>
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div>
            <label for="{{ $prefix }}fecha_inicio" class="block text-sm font-medium text-gray-900">Inicio <span class="text-red-500">*</span></label>
            <input type="date" id="{{ $prefix }}fecha_inicio" name="fecha_inicio" required
                   class="mt-1.5 w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600"/>
        </div>
        <div>
            <label for="{{ $prefix }}fecha_fin" class="block text-sm font-medium text-gray-900">Fin <span class="text-xs font-normal text-gray-400">(vacío = actual)</span></label>
            <input type="date" id="{{ $prefix }}fecha_fin" name="fecha_fin"
                   class="mt-1.5 w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600"/>
        </div>
    </div>

    <div>
        <label for="{{ $prefix }}descripcion" class="block text-sm font-medium text-gray-900">Descripción de responsabilidades</label>
        <textarea id="{{ $prefix }}descripcion" name="descripcion" rows="4"
                  class="mt-1.5 w-full rounded-md bg-white px-3 py-2 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-blue-600"></textarea>
    </div>

</div>
