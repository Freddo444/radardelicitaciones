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

        if (! is_dir($this->outDir)) {
            mkdir($this->outDir, 0755, true);
        }
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
        $company = currentCompany();

        // Forms with dedicated builders (special data processing)
        $result = match ($formCode) {
            'CARATULA.A', 'CARATULA.B' => $this->buildCaratula($company, $params, $formCode),
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
            default => null,
        };

        // Fallback: template-based generation for forms without a dedicated builder
        if ($result === null) {
            $templatePath = OfferGeneratedFile::$templatePaths[$formCode] ?? null;
            if (! $templatePath) {
                throw new \InvalidArgumentException("Unknown form code: {$formCode}");
            }
            $tpl = $this->tpl($templatePath);
            $this->set($tpl, $this->companyTokens($company), $this->processTokens($params), $this->dateTokens(), $params);
            $this->setImages($tpl, $company);
            $result = [$this->save($tpl, $formCode), [
                'company' => $company->only(['razon_social', 'rnc']),
                ...$params,
            ]];
        }

        [$fullPath, $context] = $result;

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

    /**
     * Generate a single template by its file path relative to tplRoot.
     * Used by prellenado for templates that don't have a dedicated builder.
     */
    public function generateFromTemplate(
        string $relativePath,
        array $params = [],
        ?int $offerId = null,
        ?int $prellenadoPackageId = null
    ): OfferGeneratedFile {
        $company = currentCompany();
        $tpl = $this->tpl($relativePath);
        $this->set($tpl, $this->companyTokens($company), $this->processTokens($params), $this->dateTokens());

        // Apply extra tokens passed directly
        $extraTokens = $params['_extra_tokens'] ?? [];
        if (! empty($extraTokens)) {
            $this->set($tpl, $extraTokens);
        }

        $this->setImages($tpl, $company);

        $slug = pathinfo($relativePath, PATHINFO_FILENAME);
        $fullPath = $this->save($tpl, $slug);

        return OfferGeneratedFile::create([
            'offer_id' => $offerId,
            'prellenado_package_id' => $prellenadoPackageId,
            'form_code' => $slug,
            'source_context_json' => [
                'template' => $relativePath,
                'company' => $company->only(['razon_social', 'rnc']),
                ...$params,
            ],
            'path' => 'generated/'.basename($fullPath),
            'sha256' => hash_file('sha256', $fullPath),
            'file_size' => filesize($fullPath),
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

    /**
     * Replace image tokens (img_firma, img_sello) with actual uploaded images.
     * Falls back to empty string if the company hasn't uploaded the image.
     */
    private function setImages(TemplateProcessor $tpl, Company $c, array $sizeOverrides = []): void
    {
        $imageMap = [
            'img_firma' => $c->firma_path,
            'img_sello' => $c->sello_path,
            'img_logo' => $c->logo_path,
        ];

        foreach ($imageMap as $token => $path) {
            if (! in_array($token, $tpl->getVariables())) {
                continue;
            }

            $fullPath = $path ? storage_path('app/public/'.$path) : null;
            $size = $sizeOverrides[$token] ?? ['width' => 150, 'height' => 75];

            if ($fullPath && file_exists($fullPath)) {
                try {
                    $tpl->setImageValue($token, [
                        'path' => $fullPath,
                        'width' => $size['width'],
                        'height' => $size['height'],
                        'ratio' => ! isset($sizeOverrides[$token]),
                    ]);
                } catch (\Throwable) {
                    $tpl->setValue($token, '');
                }
            } else {
                $tpl->setValue($token, '');
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
            // Rep tokens — both short and full-name variants used across templates
            'rep_nombre' => $c->rep_legal_nombre ?? '',
            'rep_legal_nombre' => $c->rep_legal_nombre ?? '',
            'rep_cedula' => $c->rep_legal_cedula ?? '',
            'rep_cargo' => $c->rep_legal_cargo ?? '',
            'rep_nacionalidad' => $c->rep_legal_nacionalidad ?? 'Dominicano/a',
            'rep_estado_civil' => $c->rep_legal_estado_civil ?? '',
            'rep_legal_estado_civil' => $c->rep_legal_estado_civil ?? '',
            'rep_domicilio' => $c->direccion ?? '',
            'rep_municipio' => $c->municipio ?? '',
        ];
    }

    /** Standard process/bid tokens from $params. */
    private function processTokens(array $params): array
    {
        $ref = $params['proceso_ref'] ?? $params['proceso_codigo'] ?? '';
        $nombre = $params['proceso_nombre'] ?? '';
        $entidad = $params['entidad_nombre'] ?? $params['entidad'] ?? '';

        return [
            'proceso_ref' => $ref,
            'proceso_codigo' => $ref,
            'proceso_nombre' => $nombre,
            'proceso_titulo' => $nombre,
            'proceso_tipo' => $params['proceso_tipo'] ?? '',
            'entidad_nombre' => $entidad,
            'proceso_entidad' => $entidad,
            'obra_nombre' => $params['obra_nombre'] ?? $nombre,
            'fecha' => now()->format('d/m/Y'),
        ];
    }

    private function dateTokens(): array
    {
        $now = now();
        $meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
        $unidades = ['', 'uno', 'dos', 'tres', 'cuatro', 'cinco', 'seis', 'siete', 'ocho', 'nueve', 'diez', 'once', 'doce', 'trece', 'catorce', 'quince', 'dieciséis', 'diecisiete', 'dieciocho', 'diecinueve', 'veinte', 'veintiuno', 'veintidós', 'veintitrés', 'veinticuatro', 'veinticinco', 'veintiséis', 'veintisiete', 'veintiocho', 'veintinueve', 'treinta', 'treinta y uno'];

        return [
            'dia' => $now->day,
            'dia_letras' => $unidades[$now->day] ?? (string) $now->day,
            'mes' => $meses[$now->month - 1],
            'anio' => $now->year,
            'anio_letras' => 'dos mil '.($unidades[$now->year - 2000] ?? (string) ($now->year - 2000)),
            'fecha_larga' => $now->day.' de '.$meses[$now->month - 1].' de '.$now->year,
            'ciudad' => 'Santo Domingo',
            'provincia' => 'Distrito Nacional',
        ];
    }

    // ── Form builders ─────────────────────────────────────────────────

    private function buildCaratula(Company $c, array $p, string $formCode): array
    {
        $templatePath = OfferGeneratedFile::$templatePaths[$formCode];
        $tpl = $this->tpl($templatePath);
        $this->set($tpl, $this->companyTokens($c), $this->processTokens($p), $this->dateTokens());
        $this->setImages($tpl, $c, [
            'img_firma' => ['width' => 254, 'height' => 60],
            'img_sello' => ['width' => 156, 'height' => 145],
        ]);

        return [$this->save($tpl, $formCode), [
            'company' => $c->only(['razon_social', 'rnc']),
            ...$p,
        ]];
    }

    private function buildF033(Company $c, array $p): array
    {
        $tpl = $this->tpl('estandar/SNCC_F033_Of_Economica.docx');
        $this->set($tpl, $this->companyTokens($c), $this->processTokens($p), $this->dateTokens());
        $this->setImages($tpl, $c);

        return [$this->save($tpl, 'SNCC.F.033'), [
            'company' => $c->only(['razon_social', 'rnc']),
            ...$p,
        ]];
    }

    private function buildF034(Company $c, array $p): array
    {
        $tpl = $this->tpl('estandar/SNCC_F034_Presentacion_de_Oferta.docx');
        $this->set($tpl, $this->companyTokens($c), $this->processTokens($p), $this->dateTokens());
        $this->setImages($tpl, $c);

        return [$this->save($tpl, 'SNCC.F.034'), [
            'company' => $c->only(['razon_social', 'rnc']),
            ...$p,
        ]];
    }

    private function buildF036(Company $c, array $p): array
    {
        $tpl = $this->tpl('estandar/SNCC_F036_Equipos_Oferente.docx');
        $items = Equipment::where('company_id', $c->id)->active()->orderBy('descripcion')->get();

        $this->set($tpl, $this->companyTokens($c), $this->processTokens($p), $this->dateTokens());
        $this->setImages($tpl, $c);

        return [$this->save($tpl, 'SNCC.F.036'), [
            'company' => $c->only(['razon_social']),
            'item_count' => $items->count(),
            ...$p,
        ]];
    }

    private function buildF037(Company $c, array $p): array
    {
        $tpl = $this->tpl('estandar/SNCC_F037_Personal_Oferente.docx');
        $this->set($tpl, $this->companyTokens($c), $this->processTokens($p), $this->dateTokens());
        $this->setImages($tpl, $c);

        return [$this->save($tpl, 'SNCC.F.037'), [
            'company' => $c->only(['razon_social']),
            ...$p,
        ]];
    }

    private function buildF042(Company $c, array $p): array
    {
        $tpl = $this->tpl('estandar/SNCC_F042_Informacion_Oferente.docx');
        $this->set($tpl, $this->companyTokens($c), $this->processTokens($p), $this->dateTokens());
        $this->setImages($tpl, $c);

        return [$this->save($tpl, 'SNCC.F.042'), [
            'company' => $c->only(['razon_social', 'rnc']),
            ...$p,
        ]];
    }

    private function buildD045(Company $c, array $p): array
    {
        $person = Personnel::findOrFail($p['personnel_id']);
        $tpl = $this->tpl('estandar/SNCC_D045_Curriculo_Personal.docx');

        $educacion = implode(' | ', array_filter([
            $person->nivel_educativo ? ucfirst($person->nivel_educativo) : null,
            $person->titulo,
            $person->institucion ? 'Institución: '.$person->institucion : null,
            $person->anio_titulo ? 'Año: '.$person->anio_titulo : null,
        ]));

        $this->set($tpl,
            $this->companyTokens($c),
            $this->processTokens($p),
            $this->dateTokens(),
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
        $this->setImages($tpl, $c);

        return [$this->save($tpl, 'SNCC.D.045'), [
            'person_id' => $person->id,
            'nombre' => $person->nombre,
        ]];
    }

    private function buildD048(Company $c, array $p): array
    {
        $person = Personnel::with('experiences')->findOrFail($p['personnel_id']);
        $tpl = $this->tpl('estandar/SNCC_D048_Experiencia_Profesional_Personal.docx');

        $this->set($tpl,
            $this->companyTokens($c),
            $this->processTokens($p),
            $this->dateTokens(),
            [
                'persona_nombre' => $person->nombre,
                'persona_apellidos' => $person->nombre,
                'persona_cargo_propuesto' => $p['cargo_propuesto'] ?? $person->cargo ?? '',
                'persona_fecha_nac' => $person->fecha_nac?->format('d/m/Y') ?? '',
                'persona_idiomas' => implode(', ', $person->idiomas ?? []),
            ]
        );
        $this->setImages($tpl, $c);

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
        $tpl = $this->tpl('estandar/SNCC_D049_Experiencia_contratista.docx');

        $this->set($tpl, $this->companyTokens($c), $this->processTokens($p), $this->dateTokens());
        $this->setImages($tpl, $c);

        return [$this->save($tpl, 'SNCC.D.049'), [
            'company' => $c->only(['razon_social']),
            'project_ids' => $projectIds,
            'project_count' => $projects->count(),
        ]];
    }

    private function buildDeclJurada(Company $c, array $p): array
    {
        $tpl = $this->tpl('declaraciones/DECLARACION_JURADA_ART_14_PLANTILLA.docx');
        $this->set($tpl,
            $this->companyTokens($c),
            $this->processTokens($p),
            $this->dateTokens(),
            [
                'rep_nacionalidad' => $p['rep_nacionalidad'] ?? 'Dominicano/a',
                'rep_estado_civil' => $p['rep_estado_civil'] ?? '',
            ]
        );
        $this->setImages($tpl, $c);

        return [$this->save($tpl, 'DECL.JURADA'), [
            'company' => $c->only(['razon_social', 'rnc', 'rep_legal_nombre', 'rep_legal_cedula']),
        ]];
    }

    private function buildCompromisoEtico(Company $c, array $p): array
    {
        $tpl = $this->tpl('estandar/compromiso-etico-de-proveedores.docx');
        $this->set($tpl,
            $this->companyTokens($c),
            $this->processTokens($p),
            $this->dateTokens(),
            [
                'rep_nacionalidad' => $p['rep_nacionalidad'] ?? 'Dominicano/a',
                'rep_estado_civil' => $p['rep_estado_civil'] ?? '',
            ]
        );
        $this->setImages($tpl, $c, [
            'img_firma' => ['width' => 150, 'height' => 35],
            'img_sello' => ['width' => 100, 'height' => 93],
        ]);

        return [$this->save($tpl, 'DECL.COMPROMISO_ETICO'), [
            'company' => $c->only(['razon_social', 'rnc']),
            ...$p,
        ]];
    }
}
