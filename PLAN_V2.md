# SECP Monitor — Plan V2: Bid Operations Platform

**Status:** Planning
**Date:** 2026-03-12
**Scope:** Dominican Republic government procurement (DGCP / Compras Dominicanas)

---

## Vision

Transform the v1 procurement monitor into a full bid operations platform. The system tracks
new procurement opportunities (v1) and now also assembles the entire bid response — pulling
company data from a structured vault, auto-parsing pliego requirements with Claude, generating
SNCC-standard forms, and producing a submission-ready package.

Target user: Dominican procurement professional managing bids across multiple companies or
multiple concurrent processes. Pain point: repeated manual work assembling the same documents
in every bid, tracking expiry dates, calculating financial indices, filling the same forms again
and again.

---

## Core Architecture

```
┌─────────────────────────────────────────────────────────┐
│                     SECP Monitor V2                     │
├────────────────┬────────────────┬───────────────────────┤
│   V1 Monitor   │  Vault         │  Bid Prep             │
│   (existing)   │  (company      │  (offer assembly)     │
│                │   data store)  │                       │
│  ─ Poll DGCP   │  ─ Empresa     │  ─ Link to process    │
│  ─ Match rubros│  ─ Personal    │  ─ Auto-fetch pliego  │
│  ─ Notify      │  ─ Equipos     │  ─ Claude parsing     │
│                │  ─ Proyectos   │  ─ Requirements list  │
│                │  ─ Financiero  │  ─ CUMPLE checklist   │
│                │  ─ Documentos  │  ─ Form generator     │
└────────────────┴────────────────┴───────────────────────┘
```

---

## Module Breakdown

---

### M1 — Empresa (Company Profile)

Single-record profile for the bidding company. Foundation for all auto-filled forms.

**Fields:**
- Razón social, nombre comercial
- RNC
- Dirección completa (calle, municipio, provincia)
- Teléfono, correo electrónico, sitio web
- Representante legal (nombre, cédula, cargo)
- Número RPE + fecha de vencimiento
- Número CPA + fecha de vencimiento

**UI:**
- `/empresa` — edit form, saved to DB
- Expiry warnings on dashboard for RPE and CPA

---

### M2 — Bóveda de Documentos (Document Vault)

Stores all recurring company documents with category labels and expiry tracking.

**Categories:**
- Legal: RNC, Acta constitutiva, Estatutos, Poderes notariales
- Habilitaciones: RPE, CPA, DGT certification, TSS certification
- Tributario: Certificado de cumplimiento fiscal (DGII), Estado de cuenta DGII
- Seguridad Social: TSS paz y salvo
- Corporativo: List of shareholders (F.034 input)

**Per document:**
- Filename + file (stored in `storage/app/vault/`)
- Category
- Issue date, expiry date (nullable)
- Notes

**UI:**
- `/documentos` — document list grouped by category
- Upload, label, set dates
- Color-coded expiry status: green/yellow (30d)/red (expired)
- Dashboard widget: "X documentos vencen en 30 días"

---

### M3 — Registro de Personal (HR Vault)

Structured staff profiles. Maps to SNCC.D.045 (CV) and SNCC.D.048 (professional experience).

**Per person:**
- Nombre completo, cédula, fecha de nacimiento
- Cargo / especialidad
- Nivel educativo, titulación, institución, año
- Idiomas
- Experience entries:
  - Empresa, cargo, periodo, descripción
  - Maps to D.048 rows
- Skills / certifications
- Photo (for CV cover)
- Profile status: active / inactive

**UI:**
- `/personal` — staff list with filters
- `/personal/{id}` — edit profile + experience entries
- Generate D.045 / D.048 PDF from profile (form generator)

---

### M4 — Cartera de Proyectos (Project Portfolio)

Past project experience. Maps to SNCC.D.049 format.

**Per project:**
- Nombre del proyecto / contrato
- Cliente (entidad contratante)
- Número de contrato
- Monto (DOP/USD)
- Fecha inicio, fecha fin
- Descripción del objeto
- Categoría UNSPSC (rubro)
- Contact at client (for verification)
- Supporting documents (contract scan, acta de recepción)

**UI:**
- `/proyectos` — portfolio list, filterable by category/year/amount
- `/proyectos/{id}` — detail + documents
- Generate D.049 table

---

### M5 — Inventario de Equipos (Equipment Inventory)

Equipment/machinery the company owns or has access to. Maps to SNCC.F.036.

**Per item:**
- Descripción
- Marca, modelo, año
- Estado (Propio / Arrendado / Leasing)
- Capacidad / características técnicas
- Condición (Bueno / Regular / Malo)
- Cantidad

**UI:**
- `/equipos` — inventory table
- Add / edit / toggle active
- Generate F.036 table from selected items

---

### M6 — Bóveda Financiera (Financial Vault)

Financial records and automatic calculation of required indices.

**Per fiscal year:**
- Año fiscal
- Estado financiero: Activo Total, Activo Circulante, Inventarios, Pasivo Total, Pasivo Circulante, Patrimonio, Ingresos, Utilidad
- IR-2 / Declaración de ISR (upload PDF)
- Estado financiero certificado (upload PDF — CPA signed)

**Auto-calculated indices** (displayed inline, recalculated on save):
- **Solvencia** = Activo Total / Pasivo Total (≥ 1.0 typically required)
- **Liquidez** = (Activo Circulante − Inventarios) / Pasivo Circulante (≥ 1.0)
- **Endeudamiento** = Pasivo Total / Activo Total (≤ 0.60 typically)
- **Capital de Trabajo** = Activo Circulante − Pasivo Circulante

**UI:**
- `/financiero` — year list
- `/financiero/{year}` — enter balances, see calculated indices, upload documents
- Multiple years stored (bids often require 2–3 years)

---

### M7 — Generador de Formularios (Form Generator)

Auto-fills SNCC standard forms from vault data. Output: filled PDF or DOCX.

**Forms supported:**

| Form | Description | Source data |
|---|---|---|
| SNCC.F.033 | Formulario de Oferta | Empresa, bid info |
| SNCC.F.034 | Declaración de no adeudo / no deuda | Empresa, RPE |
| SNCC.F.037 | Capacidad financiera | Financiero (indices) |
| SNCC.F.042 | Resumen de oferta | Empresa, bid items |
| SNCC.D.045 | CV del personal | Personal profile |
| SNCC.D.048 | Experiencia profesional | Personal experience entries |
| SNCC.D.049 | Referencias de proyectos | Proyectos |
| SNCC.F.036 | Inventario de equipos | Equipos |
| Declaración de integridad | Standard text | Empresa |
| Declaración de no parentesco | Standard text | Empresa |
| Declaración de no inhabilitación | Standard text | Empresa |
| Declaración jurada representante | Standard text | Empresa, rep. legal |

**Implementation approach:**
- DOCX templates using a PHP library (e.g., `phpoffice/phpword`) with placeholders
- Fill placeholders from DB, render to DOCX
- Optional: convert to PDF via LibreOffice (headless)
- Store generated files per bid offer

**UI:**
- `/formularios` — select form, select year (for financial), select personnel → download
- Also accessible from bid prep module (context-aware: pre-selects bid data)

---

### M8 — Preparación de Oferta (Bid Preparation)

The centerpiece: ties together a specific monitored bid with the vault and generates the full
submission package.

**Per offer (linked to a `Bid` from v1):**
- Proceso code, título, entidad
- Estado: Borrador / En preparación / Listo / Enviado
- Fecha límite de oferta (from monitor)

**Sub-steps:**

#### 8a — Pliego Fetch & Gemini Parse (automatic)

1. On offer creation, call `/procesos/documentos?proceso={code}`
2. Filter documents: look for `tipo_documento` matching keywords like "pliego", "bases", "términos de referencia", "especificaciones"
3. Download the PDF from `url_documento` (no auth required)
4. Upload PDF directly to Gemini (supports native PDF understanding) with structured prompt:

```
Eres un experto en contrataciones públicas de la República Dominicana.
Analiza este pliego de condiciones y extrae la siguiente información en JSON:
{
  "documentos_requeridos": [{"nombre": "", "copias": 0, "tipo": "original|copia|apostilla"}],
  "indices_financieros": {"solvencia_min": 0, "liquidez_min": 0, "endeudamiento_max": 0},
  "personal_requerido": [{"cargo": "", "experiencia_años": 0, "certificaciones": []}],
  "equipos_requeridos": [{"descripcion": "", "cantidad": 0}],
  "experiencia_requerida": {"proyectos_similares": 0, "monto_minimo": 0, "currency": "DOP"},
  "formato_oferta": {"copias": 0, "idioma": "", "formato": ""},
  "fechas_clave": {"visita_campo": null, "aclaraciones": null, "entrega_oferta": null},
  "criterios_evaluacion": [{"criterio": "", "peso": 0}],
  "notas": ""
}
```

5. Store parsed JSON as `requirements` on the offer record

#### 8b — Requirements Checklist (CUMPLE / NO CUMPLE)

Generated from Claude's parsed output. Per requirement:
- Description (from pliego)
- Type: document / financial / personnel / equipment / experience / format
- Status: CUMPLE / NO CUMPLE / PENDIENTE
- Assigned item from vault (FK to document, person, project, equipment)
- Notes

**UI:** Kanban-style or table checklist. User drags vault items to satisfy requirements.

#### 8c — Form Generation per Bid

Context-aware form generator within the offer. Pre-fills:
- F.033: uses bid's entidad, objeto, monto from monitor
- F.037: pulls selected fiscal year indices
- D.045/D.048: pulls selected personnel
- D.049: pulls selected projects
- F.036: pulls selected equipment

#### 8d — Package Assembly

"Ensamblar Oferta" button:
- Runs through checklist
- Collects: generated forms (DOCX/PDF) + vault documents
- Creates a ZIP archive named `{proceso_code}_{empresa}_{fecha}.zip`
- Shows download link
- Logs assembly timestamp

**UI:**
- `/ofertas` — list of bid preparations
- `/ofertas/create?bid={id}` — start from monitor bid
- `/ofertas/{id}` — main prep workspace with tabs:
  - Tab 1: Pliego & Requisitos
  - Tab 2: Checklist
  - Tab 3: Formularios
  - Tab 4: Ensamblar

---

### M9 — Dashboard V2

Replace the current bid-list-only dashboard with an operations command center.

**Sections:**

1. **Alertas de vencimiento** — top banner if any vault documents expire within 30 days. Red if expired, yellow if ≤30 days. Click → goes to `/documentos`.

2. **Ofertas activas** — cards for each in-progress bid preparation, showing:
   - Process title + entity
   - Deadline (days remaining, color-coded)
   - Preparation status (Borrador / En preparación / Listo)
   - Quick link to offer workspace

3. **Convocatorias recientes** — last 5 matched bids from the monitor (condensed, not full table). "Ver todas" link.

4. **Estado del sondeo** — last polled time, next scheduled poll, manual poll button (same as v1).

5. **Resumen de bóveda** — compact stat row: personnel count, projects count, documents count, financial years stored.

**Goal:** User opens the app and immediately sees: what's expiring, what bids are in progress, what's new. No scrolling to find important information.

---

### M10 — Navigation Restructure

V2 adds 6 new top-level sections. Update `layouts/app.blade.php` nav before building any V2 module.

**New nav structure:**

```
Monitor          → / (dashboard)
Convocatorias    → / (same, or split to /convocatorias)
Rubros           → /rubros

── Empresa ──────────────────
Perfil           → /empresa
Documentos       → /documentos
Personal         → /personal
Proyectos        → /proyectos
Equipos          → /equipos
Financiero       → /financiero

── Ofertas ──────────────────
Preparaciones    → /ofertas
Formularios      → /formularios

── Sistema ──────────────────
Configuración    → /configuracion
Usuarios         → /usuarios
Logs             → /logs
```

Group nav items under collapsible or labeled sections. Keep the sidebar pattern already in use.

---

## AI Integration — Google Gemini (Free Tier)

**Model:** `gemini-2.0-flash` — free tier, handles PDF natively, solid Spanish comprehension

**Free tier limits (more than sufficient for this use case):**
- 1,500 requests/day
- 1M tokens/minute
- Direct PDF upload support (no conversion/extraction needed)

**Config stored in `.env`:**
```
GEMINI_API_KEY=your_key_here
```
Key never stored in DB. Shown as masked field in Settings UI.

**Usage points:**
1. Pliego parsing (M8a) — one call per offer creation, PDF uploaded directly
2. Requirement classification — classify extracted items into vault categories
3. Optional: "¿Cuáles de mis proyectos aplican para este proceso?" — match portfolio to requirements

**Implementation:**
- PHP HTTP call to `https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent`
- Upload PDF as inline base64 or via File API (for large pliegos > 20MB)
- Response parsed as JSON (instruct model to return structured JSON)
- Wrapped in `GeminiService` class for clean swap-out later if needed

**Rate / cost management:**
- Cache parsed results — never re-parse same document
- Store raw Gemini response JSON alongside structured parse
- User can trigger re-parse manually if needed
- If free tier limits are hit (unlikely at personal scale), fallback message prompts user to wait or configure a paid key

---

## Multi-Company Support (V3)

Deferred. V2 is single-company only.

V3 upgrade path is low-effort because `company_id` FK is baked into all vault tables from day 1:
- Add `Company` model and company switcher in nav
- All existing queries just get a `->where('company_id', $activeCompany->id)` scope
- No structural migrations needed

---

## V3 Roadmap Items

- **Multi-company support** (see above)
- **Fianzas Digitales** — digital surety bond generation/request in-platform. User has industry connections to source a partnership with a Dominican bonding company. This is LicitaHoy's strongest moat and a major friction-remover in the bid process (currently requires physical visits or separate broker).
- **Multi-user / team seats** — shared bid workspace, task assignment, change history
- **Competitive Intelligence** — historical award prices per rubro and per supplier from DGCP historical data. Who won, at what price, how many times.

---

## Technical Stack Additions

| Addition | Purpose |
|---|---|
| `phpoffice/phpword` | DOCX template filling |
| HTTP (built-in Laravel) | Gemini API calls (no SDK needed) |
| `spatie/laravel-pdf` or LibreOffice | PDF generation (optional) |
| `ziparchive` (PHP built-in) | ZIP package assembly |
| New storage disk: `vault` | Document storage |

---

## Database Changes

```sql
-- Company profile (single row)
companies: id, razon_social, nombre_comercial, rnc, direccion, municipio, provincia,
           telefono, email, web, rep_legal_nombre, rep_legal_cedula, rep_legal_cargo,
           rpe_numero, rpe_vence, cpa_numero, cpa_vence, timestamps

-- Document vault
vault_documents: id, company_id, category, name, filename, path,
                 issued_at, expires_at, notes, timestamps

-- Personnel
personnel: id, company_id, nombre, cedula, fecha_nac, cargo, nivel_educativo,
           titulo, institucion, anio_titulo, idiomas, photo_path, active, timestamps

personnel_experience: id, person_id, empresa, cargo, fecha_inicio, fecha_fin,
                      descripcion, timestamps

-- Projects
projects: id, company_id, nombre, cliente, numero_contrato, monto, currency,
          fecha_inicio, fecha_fin, descripcion, unspsc_codigo, contacto_cliente,
          contacto_telefono, timestamps

project_documents: id, project_id, nombre, path, timestamps

-- Equipment
equipment: id, company_id, descripcion, marca, modelo, anio, tenencia,
           capacidad, condicion, cantidad, active, timestamps

-- Financial years
financials: id, company_id, anio, activo_total, activo_circulante, inventarios,
            pasivo_total, pasivo_circulante, patrimonio, ingresos, utilidad,
            ir2_path, estado_financiero_path, timestamps

-- Bid offers (links v1 Bid to prep workspace)
offers: id, bid_id, company_id, estado, requirements_json, gemini_parsed_at,
        assembled_at, assembled_path, notes, timestamps

-- Requirements checklist per offer
offer_requirements: id, offer_id, tipo, descripcion, status,
                    vault_ref_type, vault_ref_id, notas, timestamps
```

---

## Routes (new)

```
/empresa                        GET/POST  — company profile
/documentos                     GET       — vault index
/documentos/upload              POST      — upload document
/documentos/{id}                PUT/DELETE
/personal                       GET       — staff list
/personal/create                GET/POST
/personal/{id}                  GET/PUT/DELETE
/proyectos                      GET/POST
/proyectos/{id}                 GET/PUT/DELETE
/equipos                        GET/POST
/equipos/{id}                   GET/PUT/DELETE
/financiero                     GET       — year list
/financiero/{year}              GET/POST
/formularios                    GET       — generator UI
/formularios/generate           POST      — generate + download
/ofertas                        GET       — offer list
/ofertas/create                 GET/POST  — start new offer from bid
/ofertas/{id}                   GET       — offer workspace
/ofertas/{id}/parse-pliego      POST      — trigger Gemini parse
/ofertas/{id}/assemble          POST      — build ZIP package
/ofertas/{id}/requirements      PUT       — update checklist
```

---

## Build Order

1. **Empresa + Documentos** — foundation, needed by all forms
2. **Personal** — HR vault with D.045/D.048 generation
3. **Proyectos** — portfolio with D.049 generation
4. **Equipos** — F.036 generation
5. **Financiero** — balance sheet + index calculation
6. **Formularios** — form generator using above vaults
7. **Gemini integration** — pliego parser
8. **Ofertas** — bid prep workspace, checklist, assembly
9. **Dashboard V2** — expiry alerts, active offers summary, recent notifications
10. **Nav restructure** — update `layouts/app.blade.php` to include all V2 sections

---

## Decisions

- **OQ-V2-1:** Single company for V2, multi-company deferred to V3.
  - **Implementation:** Add `company_id` FK to all vault tables from day 1, defaulting to company id=1 (invisible to user). V3 becomes just adding a company switcher in the nav — no DB migrations needed.
- **OQ-V2-2:** DOCX output (user can edit before printing). No PDF conversion needed.
  - Library: `phpoffice/phpword` with placeholder templates.
- **OQ-V2-3:** No LibreOffice dependency — DOCX only, no PDF conversion.
- **OQ-V2-4:** Gemini API key stored in `.env` only, shown as masked field in Settings UI. Free key from Google AI Studio.
- **OQ-V2-5:** Single offer per bid for V2 (one company = one offer). Multiple offers per bid deferred to V3 alongside multi-company.
- **OQ-V2-6:** SNCC form templates (F.033, F.034, F.036, F.037, F.042, D.045, D.048, D.049) — user will provide DOCX templates when we reach M7 (Generador de Formularios).
