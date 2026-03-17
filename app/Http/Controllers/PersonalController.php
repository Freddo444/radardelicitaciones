<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Personnel;
use App\Models\PersonnelExperience;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PersonalController extends Controller
{
    public function index()
    {
        $company = Company::instance();

        $people = Personnel::where('company_id', $company->id)
            ->orderBy('active', 'desc')
            ->orderBy('nombre')
            ->paginate(25);

        return view('personal.index', compact('people'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:255',
            'cedula' => 'nullable|string|max:20',
            'cargo' => 'nullable|string|max:255',
        ]);

        $company = Company::instance();

        Personnel::create(array_merge($data, [
            'company_id' => $company->id,
            'active' => true,
        ]));

        return redirect()->route('personal.index')->with('success', 'Persona agregada.');
    }

    public function show(Personnel $personal)
    {
        $personal->load('experiences');

        return view('personal.show', compact('personal'));
    }

    public function update(Request $request, Personnel $personal)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:255',
            'cedula' => 'nullable|string|max:20',
            'fecha_nac' => 'nullable|date',
            'cargo' => 'nullable|string|max:255',
            'nivel_educativo' => 'nullable|in:'.implode(',', array_keys(Personnel::$nivelesEducativos)),
            'titulo' => 'nullable|string|max:255',
            'institucion' => 'nullable|string|max:255',
            'anio_titulo' => 'nullable|integer|min:1950|max:2100',
            'idiomas' => 'nullable|string',
            'skills' => 'nullable|string',
            'active' => 'boolean',
        ]);

        // Parse comma-separated strings into arrays
        $data['idiomas'] = $this->parseTags($request->idiomas);
        $data['skills'] = $this->parseTags($request->skills);
        $data['active'] = $request->boolean('active', true);

        // Photo upload
        if ($request->hasFile('photo')) {
            $request->validate(['photo' => 'image|max:5120']);
            $ext = $request->file('photo')->getClientOriginalExtension();
            $path = $request->file('photo')->storeAs(
                "vault/{$personal->company_id}/photos",
                Str::uuid().'.'.$ext,
                'vault'
            );
            // Remove old photo if exists
            if ($personal->photo_path) {
                Storage::disk('vault')->delete($personal->photo_path);
            }
            $data['photo_path'] = $path;
        }

        $personal->update($data);

        return redirect()->route('personal.show', $personal)->with('success', 'Perfil actualizado.');
    }

    public function toggle(Personnel $personal)
    {
        $personal->update(['active' => ! $personal->active]);

        return redirect()->route('personal.index')
            ->with('success', $personal->active ? 'Persona activada.' : 'Persona desactivada.');
    }

    // Experience entries
    public function storeExperience(Request $request, Personnel $personal)
    {
        $data = $request->validate([
            'empresa' => 'required|string|max:255',
            'cargo' => 'required|string|max:255',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
            'descripcion' => 'nullable|string|max:2000',
        ]);

        $personal->experiences()->create($data);

        return redirect()->route('personal.show', $personal)->with('success', 'Experiencia agregada.');
    }

    public function updateExperience(Request $request, Personnel $personal, PersonnelExperience $experience)
    {
        abort_unless($experience->person_id === $personal->id, 403);

        $data = $request->validate([
            'empresa' => 'required|string|max:255',
            'cargo' => 'required|string|max:255',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
            'descripcion' => 'nullable|string|max:2000',
        ]);

        $experience->update($data);

        return redirect()->route('personal.show', $personal)->with('success', 'Experiencia actualizada.');
    }

    public function destroyExperience(Personnel $personal, PersonnelExperience $experience)
    {
        abort_unless($experience->person_id === $personal->id, 403);
        $experience->delete();

        return redirect()->route('personal.show', $personal)->with('success', 'Experiencia eliminada.');
    }

    private function parseTags(?string $input): array
    {
        if (! $input) {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $input))));
    }
}
