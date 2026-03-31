<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use App\Models\OfferEquipment;
use Illuminate\Http\Request;

class EquiposController extends Controller
{
    public function index(Request $request)
    {
        $company = currentCompany();

        $query = Equipment::where('company_id', $company->id)
            ->orderBy('active', 'desc')
            ->orderBy('descripcion');

        if ($request->filled('tenencia')) {
            $query->where('tenencia', $request->tenencia);
        }
        if ($request->boolean('inactivos')) {
            // show all
        } else {
            $query->where('active', true);
        }

        $items = $query->paginate(25)->withQueryString();

        return view('equipos.index', [
            'items' => $items,
            'tenencias' => Equipment::$tenencias,
            'condiciones' => Equipment::$condiciones,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'descripcion' => 'required|string|max:255',
            'tenencia' => 'required|in:propio,arrendado,leasing',
            'cantidad' => 'required|integer|min:1',
        ]);

        $company = currentCompany();

        Equipment::create(array_merge($data, ['company_id' => $company->id]));

        return redirect()->route('equipos.index')->with('success', 'Equipo agregado.');
    }

    public function update(Request $request, Equipment $equipo)
    {
        $data = $request->validate([
            'descripcion' => 'required|string|max:255',
            'marca' => 'nullable|string|max:100',
            'modelo' => 'nullable|string|max:100',
            'anio' => 'nullable|integer|min:1900|max:'.(date('Y') + 1),
            'tenencia' => 'required|in:propio,arrendado,leasing',
            'capacidad' => 'nullable|string|max:500',
            'condicion' => 'required|in:bueno,regular,malo',
            'cantidad' => 'required|integer|min:1',
            'notas' => 'nullable|string|max:1000',
        ]);

        $equipo->update($data);

        return redirect()->route('equipos.index')->with('success', 'Equipo actualizado.');
    }

    public function toggle(Equipment $equipo)
    {
        $equipo->update(['active' => ! $equipo->active]);

        return redirect()->route('equipos.index')
            ->with('success', $equipo->active ? 'Equipo activado.' : 'Equipo desactivado.');
    }

    public function destroy(Equipment $equipo)
    {
        $activeRef = OfferEquipment::where('equipment_id', $equipo->id)
            ->whereHas('offer', fn ($q) => $q->whereIn('estado', ['borrador', 'en_preparacion', 'listo']))
            ->with('offer:id,proceso_nombre')
            ->first();

        if ($activeRef) {
            return back()->with('error', "Este registro está en uso por la oferta \"{$activeRef->offer->proceso_nombre}\". Desactívalo en lugar de eliminarlo.");
        }

        $equipo->delete();

        return redirect()->route('equipos.index')->with('success', 'Equipo eliminado.');
    }
}
