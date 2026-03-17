<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Equipment;
use App\Models\OfferGeneratedFile;
use App\Models\Personnel;
use App\Models\Project;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\TemplateProcessor;

class FormGeneratorService
{
    private string $tplRoot;

    private string $outDir;

    public function __construct()
    {
        $this->tplRoot = resource_path('form_templates');
        $this->outDir = storage_path('app/generated');
    }

    /**
     * Generate a form, persist the file record, and return it.
     */
    public function generate(
        string $formCode,
        array $params = [],
        ?int $offerId = null,
        ?int $supersedes = null
    ): OfferGeneratedFile {
        $company = Company::instance();

        [$fullPath, $context] = match ($formCode) {
            'SNCC.F.033' => $this->buildF033($company, $params),
            'SNCC.F.034' => $this->buildF034($company, $params),
            'SNCC.F.036' => $this->buildF036($company, $params),
            'SNCC.F.037' => $this->buildF037($company, $params),
            'SNCC.F.042' => $this->buildF042($company, $params),
            'SNCC.D.045' => $this->buildD045($company, $params),
            'SNCC.D.048' => $this->buildD048($company, $params),
            'SNCC.D.049' => $this->buildD049($company, $params),
            'DECL.JURADA' => $this->buildDeclJurada($company, $params),
            'DECL.COMPROMISO_ETICO' => $this->buildCompromisoEtico($company, $params),
            default => throw new \InvalidArgumentException("Unknown form code: {$formCode}"),
        };

        return OfferGeneratedFile::create([
            'offer_id' => $offerId,
            'form_code' => $formCode,
            'source_context_json' => $context,
            'path' => 'generated/'.basename($fullPath),
            'sha256' => hash_file('sha256', $fullPath),
            'file_size' => filesize($fullPath),
            'supersedes_id' => $supersedes,
            'generated_at' => now(),
            'generated_by' => Auth::id(),
        ]);
    }

    // ── Private helpers ───────────────────────────────────────────────

    private function tpl(string $relative): TemplateProcessor
    {
        $path = $this->tplRoot.'/'.$relative;
        if (! file_exists($path)) {
            throw new \RuntimeException("Template not found: {$relative}");
        }

        return new TemplateProcessor($path);
    }

    private function save(TemplateProcessor $tpl, string $formCode): string
    {
        $filename = Str::slug($formCode).'_'.date('Ymd_His').'_'.Str::random(6).'.docx';
        $fullPath = $this->outDir.'/'.$filename;
        $tpl->saveAs($fullPath);

        return $fullPath;
    }

    /**
     * Set multiple token→value pairs on a TemplateProcessor.
     * Unknown tokens (not in this template) are silently skipped.
     * Values are XML-escaped to prevent document corruption.
     */
    private function set(TemplateProcessor $tpl, array ...$maps): void
    {
        foreach ($maps as $map) {
            foreach ($map as $token => $value) {
                try {
                    $tpl->setValue($token, htmlspecialchars((string) ($value ?? ''), ENT_XML1, 'UTF-8'));
                } catch (\Throwable) {
                    // token absent in this template — skip
                }
            }
        }
    }

    /** Standard company-level tokens, sourced from the single Company record. */
    private function companyTokens(Company $c): array
    {
        return [
            'empresa_nombre' => $c->razon_social ?? '',
            'empresa_rnc' => $c->rnc ?? '',
            'empresa_direccion' => $c->direccion ?? '',
            'empresa_municipio' => $c->municipio ?? '',
            'empresa_telefono' => $c->telefono ?? '',
            'empresa_email' => $c->email ?? '',
            'empresa_rpe' => $c->rpe_numero ?? '',
            'rep_nombre' => $c->rep_legal_nombre ?? '',
            'rep_cedula' => $c->rep_legal_cedula ?? '',
            'rep_cargo' => $c->rep_legal_cargo ?? '',
            // Rep domicilio not stored separately — fall back to company address
            'rep_domicilio' => $c->direccion ?? '',
            'rep_municipio' => $c->municipio ?? '',
        ];
    }

    /** Standard process/bid tokens from $params. */
    private function processTokens(array $params): array
    {
        return [
            'proceso_ref' => $params['proceso_ref'] ?? $params['proceso_codigo'] ?? '',
            'proceso_nombre' => $params['proceso_nombre'] ?? '',
            'proceso_tipo' => $params['proceso_tipo'] ?? '',
            'entidad_nombre' => $params['entidad_nombre'] ?? $params['entidad'] ?? '',
            'obra_nombre' => $params['obra_nombre'] ?? $params['proceso_nombre'] ?? '',
            'fecha' => now()->format('d/m/Y'),
        ];
    }

    // ── Form builders ─────────────────────────────────────────────────

    private function buildF033(Company $c, array $p): array
    {
        $tpl = $this->tpl('SNCC_F033_Of_Economica.docx');
        $this->set($tpl, $this->companyTokens($c), $this->processTokens($p));

        return [$this->save($tpl, 'SNCC.F.033'), [
            'company' => $c->only(['razon_social', 'rnc']),
            ...$p,
        ]];
    }

    private function buildF034(Company $c, array $p): array
    {
        $tpl = $this->tpl('SNCC_F034_Presentacion_de_Oferta.docx');
        $this->set($tpl, $this->companyTokens($c), $this->processTokens($p));

        return [$this->save($tpl, 'SNCC.F.034'), [
            'company' => $c->only(['razon_social', 'rnc']),
            ...$p,
        ]];
    }

    private function buildF036(Company $c, array $p): array
    {
        $tpl = $this->tpl('SNCC_F036_Equipos_Oferente.docx');
        $items = Equipment::where('company_id', $c->id)->active()->orderBy('descripcion')->get();

        $this->set($tpl, $this->companyTokens($c), $this->processTokens($p));

        return [$this->save($tpl, 'SNCC.F.036'), [
            'company' => $c->only(['razon_social']),
            'item_count' => $items->count(),
            ...$p,
        ]];
    }

    private function buildF037(Company $c, array $p): array
    {
        $tpl = $this->tpl('SNCC_F037_Personal_Oferente.docx');
        $this->set($tpl, $this->companyTokens($c), $this->processTokens($p));

        return [$this->save($tpl, 'SNCC.F.037'), [
            'company' => $c->only(['razon_social']),
            ...$p,
        ]];
    }

    private function buildF042(Company $c, array $p): array
    {
        $tpl = $this->tpl('SNCC_F042_Informacion_Oferente.docx');
        $this->set($tpl, $this->companyTokens($c), $this->processTokens($p));

        return [$this->save($tpl, 'SNCC.F.042'), [
            'company' => $c->only(['razon_social', 'rnc']),
            ...$p,
        ]];
    }

    private function buildD045(Company $c, array $p): array
    {
        $person = Personnel::findOrFail($p['personnel_id']);
        $tpl = $this->tpl('SNCC_D045_Curriculo_Personal.docx');

        $educacion = implode(' | ', array_filter([
            $person->nivel_educativo ? ucfirst($person->nivel_educativo) : null,
            $person->titulo,
            $person->institucion ? 'Institución: '.$person->institucion : null,
            $person->anio_titulo ? 'Año: '.$person->anio_titulo : null,
        ]));

        $this->set($tpl,
            $this->companyTokens($c),
            $this->processTokens($p),
            [
                'persona_nombre' => $person->nombre,
                'persona_apellidos' => $person->nombre,
                'persona_cargo_propuesto' => $p['cargo_propuesto'] ?? $person->cargo ?? '',
                'persona_fecha_nac' => $person->fecha_nac?->format('d/m/Y') ?? '',
                'persona_nacionalidad' => 'Dominicano/a',
                'persona_educacion' => $educacion ?: '',
                'persona_idiomas' => implode(', ', $person->idiomas ?? []),
            ]
        );

        return [$this->save($tpl, 'SNCC.D.045'), [
            'person_id' => $person->id,
            'nombre' => $person->nombre,
        ]];
    }

    private function buildD048(Company $c, array $p): array
    {
        $person = Personnel::with('experiences')->findOrFail($p['personnel_id']);
        $tpl = $this->tpl('SNCC_D048_Experiencia_Profesional_Personal.docx');

        $this->set($tpl,
            $this->companyTokens($c),
            $this->processTokens($p),
            [
                'persona_nombre' => $person->nombre,
                'persona_apellidos' => $person->nombre,
                'persona_cargo_propuesto' => $p['cargo_propuesto'] ?? $person->cargo ?? '',
                'persona_fecha_nac' => $person->fecha_nac?->format('d/m/Y') ?? '',
                'persona_idiomas' => implode(', ', $person->idiomas ?? []),
            ]
        );

        return [$this->save($tpl, 'SNCC.D.048'), [
            'person_id' => $person->id,
            'nombre' => $person->nombre,
            'exp_count' => $person->experiences->count(),
        ]];
    }

    private function buildD049(Company $c, array $p): array
    {
        $projectIds = $p['project_ids'] ?? [];
        $projects = Project::where('company_id', $c->id)->whereIn('id', $projectIds)->get();
        $tpl = $this->tpl('SNCC_D049_Experiencia_contratista.docx');

        $this->set($tpl, $this->companyTokens($c), $this->processTokens($p));

        return [$this->save($tpl, 'SNCC.D.049'), [
            'company' => $c->only(['razon_social']),
            'project_ids' => $projectIds,
            'project_count' => $projects->count(),
        ]];
    }

    private function buildDeclJurada(Company $c, array $p): array
    {
        $tpl = $this->tpl('legal/DECLARACION_JURADA_ART_14_PLANTILLA.docx');
        $this->set($tpl,
            $this->companyTokens($c),
            $this->processTokens($p),
            [
                'rep_nacionalidad' => $p['rep_nacionalidad'] ?? 'Dominicano/a',
                'rep_estado_civil' => $p['rep_estado_civil'] ?? '',
            ]
        );

        return [$this->save($tpl, 'DECL.JURADA'), [
            'company' => $c->only(['razon_social', 'rnc', 'rep_legal_nombre', 'rep_legal_cedula']),
        ]];
    }

    private function buildCompromisoEtico(Company $c, array $p): array
    {
        $tpl = $this->tpl('compromiso-etico-de-proveedores.docx');
        $this->set($tpl,
            $this->companyTokens($c),
            $this->processTokens($p),
            [
                'rep_nacionalidad' => $p['rep_nacionalidad'] ?? 'Dominicano/a',
                'rep_estado_civil' => $p['rep_estado_civil'] ?? '',
            ]
        );

        return [$this->save($tpl, 'DECL.COMPROMISO_ETICO'), [
            'company' => $c->only(['razon_social', 'rnc']),
            ...$p,
        ]];
    }
}
