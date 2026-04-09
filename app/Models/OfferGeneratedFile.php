<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OfferGeneratedFile extends Model
{
    protected $fillable = [
        'offer_id',
        'prellenado_package_id',
        'form_code',
        'source_context_json',
        'path',
        'sha256',
        'file_size',
        'supersedes_id',
        'generated_at',
        'generated_by',
    ];

    protected $casts = [
        'source_context_json' => 'array',
        'generated_at' => 'datetime',
    ];

    /** All form codes the generator supports, with human labels. */
    public static array $forms = [
        // ── Estándar ──
        'CARATULA.A' => 'Carátula Sobre A',
        'CARATULA.B' => 'Carátula Sobre B',
        'SNCC.F.033' => 'F.033 — Oferta económica',
        'SNCC.F.034' => 'F.034 — Presentación de oferta',
        'SNCC.F.035' => 'F.035 — Soporte técnico',
        'SNCC.F.036' => 'F.036 — Equipos del oferente',
        'SNCC.F.037' => 'F.037 — Personal de plantilla del oferente',
        'SNCC.F.040' => 'F.040 — Debida diligencia y conflicto de interés',
        'SNCC.F.042' => 'F.042 — Información del oferente',
        'SNCC.F.047' => 'F.047 — Autorización del fabricante',
        'SNCC.F.056' => 'F.056 — Entrega de muestras',
        'SNCC.D.038' => 'D.038 — Garantía GFC',
        'SNCC.D.043' => 'D.043 — Experiencia del consultor',
        'SNCC.D.044' => 'D.044 — Enfoque y metodología',
        'SNCC.D.045' => 'D.045 — Currículo del personal profesional',
        'SNCC.D.048' => 'D.048 — Experiencia profesional del personal',
        'SNCC.D.049' => 'D.049 — Experiencia como contratista',
        'SNCC.D.051' => 'D.051 — Designación de agente',
        'SNCC.D.052' => 'D.052 — Aceptación de agente',
        'OFERTA.TECNICA' => 'Formulario Oferta Técnica',
        'DECL.INTEGRIDAD' => 'Compromiso de integridad de proveedores',
        'DECL.COMPROMISO_ETICO' => 'Compromiso ético de proveedores',
        // ── Cartas ──
        'CARTA.ACEPTACION' => 'Carta de aceptación, intención y disponibilidad',
        'CARTA.ENTREGA' => 'Carta de condiciones de entrega',
        'CARTA.PAGO' => 'Carta de condiciones de pago',
        'CARTA.GARANTIA' => 'Carta de compromiso de garantía',
        'CARTA.PRECIO' => 'Carta de compromiso oferta de precio',
        // ── Aseguradoras ──
        'FIANZA.DCS' => 'Fianza — Dominicana de Seguros',
        'FIANZA.DCS.FC' => 'Fiel cumplimiento — Dominicana de Seguros',
        'FIANZA.APS' => 'Fianza — APS',
        // ── Declaraciones juradas ──
        'DECL.JURADA' => 'Declaración jurada (Art. 14)',
        'DECL.CRONOGRAMA' => 'Declaración jurada — Cronograma de entrega',
        'DECL.GARANTIA' => 'Declaración jurada — Garantía del producto',
        'DECL.NATURALES' => 'Declaración sencilla — Personas naturales',
        // ── Formularios FL ──
        'FL.01' => 'FL-01 — Inscripción',
        'FL.02' => 'FL-02 — Presentación de ofertas',
        'FL.03' => 'FL-03 — Designación de agentes',
        'FL.04' => 'FL-04 — Aceptación de agente',
        'FL.05' => 'FL-05 — Oferta económica',
        'FL.06' => 'FL-06 — Declaración jurada oferente',
        // ── Ley 47-25 ──
        'LEY47.JURADA' => 'Ley 47-25 — Declaración jurada del oferente',
        'LEY47.COLUSION' => 'Ley 47-25 — Oferta libre de colusión',
        'LEY47.JURIDICAS' => 'Ley 47-25 — Personas jurídicas',
        'LEY47.BENEFICIARIOS' => 'Ley 47-25 — Beneficiarios finales (simple)',
        'LEY47.BENEFICIARIO.FORM' => 'Ley 47-25 — Formulario beneficiario final',
        'LEY47.DILIGENCIA' => 'Ley 47-25 — Debida diligencia simplificada',
        'LEY47.SIG007' => 'Ley 47-25 — FORM-SIG-007 Debida diligencia',
    ];

    /** Map form codes to template file paths (relative to resources/form_templates/). */
    public static array $templatePaths = [
        'CARATULA.A' => 'estandar/Caratula_Sobre_A.docx',
        'CARATULA.B' => 'estandar/Caratula_Sobre_B.docx',
        'SNCC.F.033' => 'estandar/SNCC_F033_Of_Economica.docx',
        'SNCC.F.034' => 'estandar/SNCC_F034_Presentacion_de_Oferta.docx',
        'SNCC.F.035' => 'estandar/SNCC_F035_Soporte_Tecnico.docx',
        'SNCC.F.036' => 'estandar/SNCC_F036_Equipos_Oferente.docx',
        'SNCC.F.037' => 'estandar/SNCC_F037_Personal_Oferente.docx',
        'SNCC.F.040' => 'estandar/SNCCP-PROV-F-040- Formulario debida diligencia y conflicto de interes.docx',
        'SNCC.F.042' => 'estandar/SNCC_F042_Informacion_Oferente.docx',
        'SNCC.F.047' => 'estandar/SNCC_F047_Autorizacion_Fabricante.docx',
        'SNCC.F.056' => 'estandar/SNCC_F_056_Formulario_de_Entrega_de_Muestras.docx',
        'SNCC.D.038' => 'estandar/SNCC_D038_Garantia_GFC.docx',
        'SNCC.D.043' => 'estandar/SNCC_D043_Experiencia_Consultor.docx',
        'SNCC.D.044' => 'estandar/SNCC_D044_Enfoque_y_Metodologia.docx',
        'SNCC.D.045' => 'estandar/SNCC_D045_Curriculo_Personal.docx',
        'SNCC.D.048' => 'estandar/SNCC_D048_Experiencia_Profesional_Personal.docx',
        'SNCC.D.049' => 'estandar/SNCC_D049_Experiencia_contratista.docx',
        'SNCC.D.051' => 'estandar/SNCC_D051_Designacion_Agente.docx',
        'SNCC.D.052' => 'estandar/SNCC_D052_Aceptacion_Agente.docx',
        'OFERTA.TECNICA' => 'estandar/Formulario_Oferta_Tecnica.docx',
        'DECL.INTEGRIDAD' => 'estandar/Compromiso_de_Integridad_de_Proveedores.docx',
        'DECL.COMPROMISO_ETICO' => 'estandar/compromiso-etico-de-proveedores.docx',
        'CARTA.ACEPTACION' => 'cartas/Carta_Aceptacion_de_Intencion_y_Disponibilidad.docx',
        'CARTA.ENTREGA' => 'cartas/Carta_de_aceptacion_de_condiciones_de_entrega.docx',
        'CARTA.PAGO' => 'cartas/Carta_de_aceptacion_de_condiciones_de_pago.docx',
        'CARTA.GARANTIA' => 'cartas/Carta_de_compromiso_de_garantia.docx',
        'CARTA.PRECIO' => 'cartas/Carta_de_compromiso_respecto_a_la_oferta_de_precio_presentada.docx',
        'FIANZA.DCS' => 'aseguradoras/DCS-FO-OP-010_SOLICITUD_DE_FIANZAS_DOMINICANA_DE_SEGUROS.docx',
        'FIANZA.DCS.FC' => 'aseguradoras/DCS-FO-OP-010_SOLICITUD_DE_FIANZAS_DOMINICANA_DE_SEGUROS_FIEL_CUMPLIMIENTO.docx',
        'FIANZA.APS' => 'aseguradoras/FORMULARIO_SOLICITUD_DE_FIANZA_APS_formato-1.docx',
        'DECL.JURADA' => 'declaraciones/DECLARACION_JURADA_ART_14_PLANTILLA.docx',
        'DECL.CRONOGRAMA' => 'declaraciones/declaracion_jurada_personas_juridicas_cronograma.docx',
        'DECL.GARANTIA' => 'declaraciones/declaracion_jurada_personas_juridicas_garantia.docx',
        'DECL.NATURALES' => 'declaraciones/declaracion-sencilla-personas-naturales.docx',
        'FL.01' => 'formularios_fl/FL-01_Formulario_de_Inscripcion.docx',
        'FL.02' => 'formularios_fl/FL-02_Presentacion_de_Ofertas.docx',
        'FL.03' => 'formularios_fl/FL-03_Carta_de_Designacion_o_Sustitución_de_Agentes_Autorizados.docx',
        'FL.04' => 'formularios_fl/FL-04_Carta_de_Aceptacion_de_Designacion_como_Agente_Autorizado.docx',
        'FL.05' => 'formularios_fl/FL-05_Oferta_Economica.docx',
        'FL.06' => 'formularios_fl/FL-06_Declaracion_Jurada_del_Oferente_Proponente.docx',
        'LEY47.JURADA' => 'ley_47_25/Declaracion_jurada_del_oferente.docx',
        'LEY47.COLUSION' => 'ley_47_25/declaracion_jurada_oferta_libre_colusion.docx',
        'LEY47.JURIDICAS' => 'ley_47_25/declaracion_jurada_personas_juridicas.docx',
        'LEY47.BENEFICIARIOS' => 'ley_47_25/declaracion-simple-sobre-beneficiarios-finales.docx',
        'LEY47.BENEFICIARIO.FORM' => 'ley_47_25/Formulario-Beneficiario-simple-final.docx',
        'LEY47.DILIGENCIA' => 'ley_47_25/Formulario_Debida_Diligencia_Externa_Simplificada.docx',
        'LEY47.SIG007' => 'ley_47_25/FORM-SIG-007-007_Formato_Debida_Diligencia_Externa_Simplificada.docx',
    ];

    /** Forms that require selecting a personnel record. */
    public static array $requiresPersonnel = ['SNCC.D.045', 'SNCC.D.048'];

    /** Forms that require selecting a financial year. */
    public static array $requiresFinancialYear = [];

    /** Forms that require selecting projects. */
    public static array $requiresProjects = ['SNCC.D.049'];

    public function prellenadoPackage(): BelongsTo
    {
        return $this->belongsTo(PrellenadoPackage::class);
    }

    public function supersedes(): BelongsTo
    {
        return $this->belongsTo(OfferGeneratedFile::class, 'supersedes_id');
    }

    public function supersededBy(): HasMany
    {
        return $this->hasMany(OfferGeneratedFile::class, 'supersedes_id');
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function fileSizeFormatted(): string
    {
        if (! $this->file_size) {
            return '—';
        }
        if ($this->file_size >= 1048576) {
            return round($this->file_size / 1048576, 1).' MB';
        }

        return round($this->file_size / 1024, 1).' KB';
    }
}
