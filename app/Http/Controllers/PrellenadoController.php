<?php

namespace App\Http\Controllers;

use App\Models\Bid;
use App\Models\Equipment;
use App\Models\Personnel;
use App\Models\PrellenadoPackage;
use App\Models\Project;
use App\Models\VaultDocument;
use App\Services\FormGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class PrellenadoController extends Controller
{
    /**
     * Template catalog organized by category.
     * Keys are relative paths under resources/form_templates/.
     */
    public static function templateCatalog(): array
    {
        return [
            'Estándar' => [
                'estandar/Caratula_Sobre_A.docx' => 'Carátula Sobre A',
                'estandar/Caratula_Sobre_B.docx' => 'Carátula Sobre B',
                'estandar/SNCC_F033_Of_Economica.docx' => 'F.033 — Oferta económica',
                'estandar/SNCC_F034_Presentacion_de_Oferta.docx' => 'F.034 — Presentación de oferta',
                'estandar/SNCC_F035_Soporte_Tecnico.docx' => 'F.035 — Soporte técnico',
                'estandar/SNCC_F036_Equipos_Oferente.docx' => 'F.036 — Equipos del oferente',
                'estandar/SNCC_F037_Personal_Oferente.docx' => 'F.037 — Personal de plantilla',
                'estandar/SNCC_F042_Informacion_Oferente.docx' => 'F.042 — Información del oferente',
                'estandar/SNCC_F047_Autorizacion_Fabricante.docx' => 'F.047 — Autorización del fabricante',
                'estandar/SNCC_F_056_Formulario_de_Entrega_de_Muestras.docx' => 'F.056 — Entrega de muestras',
                'estandar/SNCC_D038_Garantia_GFC.docx' => 'D.038 — Garantía GFC',
                'estandar/SNCC_D043_Experiencia_Consultor.docx' => 'D.043 — Experiencia del consultor',
                'estandar/SNCC_D044_Enfoque_y_Metodologia.docx' => 'D.044 — Enfoque y metodología',
                'estandar/SNCC_D045_Curriculo_Personal.docx' => 'D.045 — Currículo del personal',
                'estandar/SNCC_D048_Experiencia_Profesional_Personal.docx' => 'D.048 — Experiencia profesional',
                'estandar/SNCC_D049_Experiencia_contratista.docx' => 'D.049 — Experiencia como contratista',
                'estandar/SNCC_D051_Designacion_Agente.docx' => 'D.051 — Designación de agente',
                'estandar/SNCC_D052_Aceptacion_Agente.docx' => 'D.052 — Aceptación de agente',
                'estandar/Formulario_Oferta_Tecnica.docx' => 'Formulario Oferta Técnica',
                'estandar/Compromiso_de_Integridad_de_Proveedores.docx' => 'Compromiso de integridad',
                'estandar/compromiso-etico-de-proveedores.docx' => 'Compromiso ético de proveedores',
                'estandar/SNCCP-PROV-F-040- Formulario debida diligencia y conflicto de interes.docx' => 'F.040 — Debida diligencia',
            ],
            'Cartas' => [
                'cartas/Carta_Aceptacion_de_Intencion_y_Disponibilidad.docx' => 'Aceptación, intención y disponibilidad',
                'cartas/Carta_de_aceptacion_de_condiciones_de_entrega.docx' => 'Condiciones de entrega',
                'cartas/Carta_de_aceptacion_de_condiciones_de_pago.docx' => 'Condiciones de pago',
                'cartas/Carta_de_compromiso_de_garantia.docx' => 'Compromiso de garantía',
                'cartas/Carta_de_compromiso_respecto_a_la_oferta_de_precio_presentada.docx' => 'Compromiso oferta de precio',
            ],
            'Aseguradoras' => [
                'aseguradoras/SOLICITUD_DE_FIANZA_CDS_Rellenable.docx' => 'Fianza — Dominicana de Seguros',
                'aseguradoras/DCS-FO-OP-010_SOLICITUD_DE_FIANZAS_DOMINICANA_DE_SEGUROS.docx' => 'Fianza — Dominicana de Seguros (alt)',
                'aseguradoras/DCS-FO-OP-010_SOLICITUD_DE_FIANZAS_DOMINICANA_DE_SEGUROS_FIEL_CUMPLIMIENTO.docx' => 'Fiel cumplimiento — Dominicana',
                'aseguradoras/FORMULARIO_SOLICITUD_DE_FIANZA_APS_formato-1.docx' => 'Fianza — APS',
                'aseguradoras/F-SR-111_Solicitud_y_Contragarantia_para_Fianzas_Reservas.docx' => 'Fianza — Seguros Reservas',
            ],
            'Declaraciones Juradas' => [
                'declaraciones/DECLARACION_JURADA_ART_14_PLANTILLA.docx' => 'Declaración jurada Art. 14',
                'declaraciones/declaracion_jurada_personas_juridicas_cronograma.docx' => 'Cronograma de entrega',
                'declaraciones/declaracion_jurada_personas_juridicas_garantia.docx' => 'Garantía del producto',
                'declaraciones/declaracion-sencilla-personas-naturales.docx' => 'Personas naturales (sencilla)',
            ],
            'Formularios FL' => [
                'formularios_fl/FL-01_Formulario_de_Inscripcion.docx' => 'FL-01 — Inscripción',
                'formularios_fl/FL-02_Presentacion_de_Ofertas.docx' => 'FL-02 — Presentación de ofertas',
                'formularios_fl/FL-03_Carta_de_Designacion_o_Sustitución_de_Agentes_Autorizados.docx' => 'FL-03 — Designación de agentes',
                'formularios_fl/FL-04_Carta_de_Aceptacion_de_Designacion_como_Agente_Autorizado.docx' => 'FL-04 — Aceptación de agente',
                'formularios_fl/FL-05_Oferta_Economica.docx' => 'FL-05 — Oferta económica',
                'formularios_fl/FL-06_Declaracion_Jurada_del_Oferente_Proponente.docx' => 'FL-06 — Declaración jurada oferente',
            ],
            'Ley 47-25' => [
                'ley_47_25/Declaracion_jurada_del_oferente.docx' => 'Declaración jurada del oferente',
                'ley_47_25/declaracion_jurada_oferta_libre_colusion.docx' => 'Oferta libre de colusión',
                'ley_47_25/declaracion_jurada_personas_juridicas.docx' => 'Personas jurídicas',
                'ley_47_25/declaracion-simple-sobre-beneficiarios-finales.docx' => 'Beneficiarios finales (simple)',
                'ley_47_25/Formulario-Beneficiario-simple-final.docx' => 'Formulario beneficiario final',
                'ley_47_25/Formulario_Debida_Diligencia_Externa_Simplificada.docx' => 'Debida diligencia simplificada',
                'ley_47_25/FORM-SIG-007-007_Formato_Debida_Diligencia_Externa_Simplificada.docx' => 'FORM-SIG-007 Debida diligencia',
            ],
        ];
    }

    public function show(Bid $bid)
    {
        $company = currentCompany();
        $personnel = Personnel::where('company_id', $company->id)->where('active', true)->orderBy('nombre')->get();
        $equipment = Equipment::where('company_id', $company->id)->where('active', true)->orderBy('descripcion')->get();
        $projects = Project::where('company_id', $company->id)->orderByDesc('fecha_inicio')->get();
        $vaultDocs = VaultDocument::where('company_id', $company->id)->orderBy('name')->get();

        // Fetch cached articles for the pricing table
        $articles = $bid->cached_articles ?? [];

        $catalog = self::templateCatalog();

        return view('prellenado.show', compact('bid', 'company', 'personnel', 'equipment', 'projects', 'vaultDocs', 'articles', 'catalog'));
    }

    public function generate(Request $request, Bid $bid, FormGeneratorService $generator)
    {
        $request->validate([
            'templates' => 'required|array|min:1',
            'templates.*' => 'string',
        ]);

        $selectedTemplates = $request->input('templates', []);
        $personnelIds = $request->input('personnel_ids', []);
        $equipmentIds = $request->input('equipment_ids', []);
        $projectIds = $request->input('project_ids', []);
        $articlesData = $request->input('articles', []);

        // Create the package record
        $package = PrellenadoPackage::create([
            'bid_id' => $bid->id,
            'user_id' => Auth::id(),
            'company_id' => currentCompany()?->id,
            'form_selections' => $selectedTemplates,
            'resource_selections' => [
                'personnel_ids' => $personnelIds,
                'equipment_ids' => $equipmentIds,
                'project_ids' => $projectIds,
            ],
            'articles_data' => $articlesData,
        ]);

        // Build shared params from bid context
        $params = [
            'proceso_ref' => $bid->process_code,
            'proceso_codigo' => $bid->process_code,
            'proceso_nombre' => $bid->title,
            'entidad_nombre' => $bid->buyer_name,
            'entidad' => $bid->buyer_name,
        ];

        // Generate each selected template
        $files = [];
        foreach ($selectedTemplates as $templatePath) {
            try {
                $file = $generator->generateFromTemplate(
                    $templatePath,
                    $params,
                    offerId: null,
                    prellenadoPackageId: $package->id
                );
                $files[] = $file;
            } catch (\Throwable $e) {
                Log::warning("Prellenado: failed to generate {$templatePath}: {$e->getMessage()}");
            }
        }

        // Bundle into ZIP
        if (! empty($files)) {
            $zipFilename = 'prellenado_'.str_replace(['-', ' '], '_', $bid->process_code).'_'.date('Ymd_His').'.zip';
            $zipPath = storage_path('app/generated/'.$zipFilename);

            $zip = new ZipArchive;
            if ($zip->open($zipPath, ZipArchive::CREATE) === true) {
                foreach ($files as $file) {
                    $filePath = storage_path('app/'.$file->path);
                    if (file_exists($filePath)) {
                        $zip->addFile($filePath, basename($file->path));
                    }
                }

                // Add vault documents if any were uploaded
                $vaultDocIds = $request->input('vault_doc_ids', []);
                if (! empty($vaultDocIds)) {
                    $vaultDocs = VaultDocument::where('company_id', currentCompany()?->id)
                        ->whereIn('id', $vaultDocIds)
                        ->get();
                    foreach ($vaultDocs as $doc) {
                        $docPath = Storage::disk('vault')->path($doc->path);
                        if (file_exists($docPath)) {
                            $zip->addFile($docPath, 'empresa/'.$doc->filename);
                        }
                    }
                }

                $zip->close();

                $package->update([
                    'zip_path' => 'generated/'.$zipFilename,
                    'zip_sha256' => hash_file('sha256', $zipPath),
                ]);
            }
        }

        return redirect()->route('documentos-generados.index')
            ->with('success', 'Prellenado generado: '.count($files).' documento(s) en el paquete.');
    }
}
