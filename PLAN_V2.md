# SECP Monitor — Plan V2: Bid Operations Platform

**Status:** Planning — v2.2 structural patch applied 2026-03-12
**Date:** 2026-03-12
**Scope:** Dominican Republic government procurement (DGCP / Compras Dominicanas)

---

## Vision

Transform the v1 procurement monitor into a full bid operations platform. The system tracks
new procurement opportunities (v1) and now also assembles the entire bid response — pulling
company data from a structured vault, auto-parsing pliego requirements with Gemini, generating
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
│  ─ Notify      │  ─ Equipos     │  ─ Gemini parsing     │
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
- Issuer (who issued the document: DGII, TSS, Registro Mercantil, etc.)
- Document number / reference
- Signed by (name of signing official, if applicable)
- Notarized / legalized flag (boolean)
- Copy type: `original` / `copia` / `copia_certificada` / `apostilla`
- Language: `es` / `en` / other
- Tags (free text, comma-separated or array JSON)
- Source linkage: optionally linked to a `Person`, `Project`, or `Equipment` record
- Confidentiality flag: `internal_only` (prevents inclusion in auto-assembled packages)
- Notes

**File versioning (immutable row model):**

`vault_documents` rows are immutable once created. When a user replaces a document (e.g., uploads a renewed RPE), the system creates a new row and marks the old one as superseded:

- `replaces_document_id` (FK to previous `vault_documents.id`, nullable) — points to the row this version supersedes
- `superseded_at` (timestamp, nullable) — set on the OLD row when a newer version is uploaded
- `is_current` (boolean) — `true` only on the latest version in a chain; `false` on all superseded rows

**How current version is determined:** `WHERE is_current = true AND (superseded_at IS NULL OR superseded_at > now())`. For display, only `is_current = true` rows appear in the vault list by default; a "Ver versiones anteriores" toggle reveals the full chain.

**How offers/snapshots reference versions:** `offer_requirement_items.vault_ref_id` stores the specific `vault_documents.id` assigned at checklist time. If the user uploads a new version of a document after assignment, the checklist item still points to the previously assigned version. The UI shows a yellow "Nueva versión disponible" badge on that requirement row, allowing the user to optionally update the assignment to the new version.

**Downloads:** `GET /documentos/{id}/download` always serves the exact row ID requested, regardless of whether it is current or superseded. No redirect to the latest version. This ensures historical offers can always re-download what was actually used.

**Old versions are never deleted.** The file on disk and the DB row both persist indefinitely. Storage cleanup is a future admin tool, not automatic.

**UI:**
- `/documentos` — document list grouped by category, showing only `is_current` rows by default
- Upload, label, set dates; "Reemplazar archivo" action creates new version row + supersedes old
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

**Calculation rules (explicit):**
- All divisions: if denominator is zero, return `null` and display "N/D" (not a division error)
- Rounding: 4 decimal places stored, 2 displayed
- Currency: all values stored in DOP; if a fiscal year was filed in USD, user marks it and the system stores USD amounts in a `currency` field — no auto-conversion (rates fluctuate, user must decide)
- Multi-year selection for bids: user manually selects which year(s) to use per offer (no auto-best logic — pliego requirements vary)
- Override: user can manually enter an index value (stored in `*_override` fields) with a reason note; displayed alongside auto-calculated value with a "Manual" badge
- Thresholds shown but not enforced: the system displays typical DGCP minimums as reference; actual per-bid thresholds come from the parsed pliego

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
- Every generated file is persisted as an `offer_generated_files` row (see schema). Files are never ephemeral temp files — they are stored on disk and recorded in DB immediately upon generation

**Generated file persistence (`offer_generated_files`):**

Every form generation — whether triggered from `/formularios` standalone or from within an offer workspace — creates a row:
- `offer_id` (nullable — standalone generations from `/formularios` have no offer context)
- `form_code` (e.g., `SNCC.F.033`, `SNCC.D.045`)
- `source_context_json` — snapshot of the data used to fill the form at generation time (selected personnel ID + their data, financial year + indices, bid info, etc.). Allows exact reproduction and audit
- `path` — file path under `storage/app/generated/`
- `sha256` — file hash
- `generated_at`, `generated_by` (user id)
- `supersedes_id` (FK to a previous `offer_generated_files.id`, nullable) — if the user regenerates the same form type for the same offer, the new row points to the old one; both are kept on disk

**Linking to requirements:** When a generated form is assigned to satisfy a requirement in the checklist (M8b), `offer_requirement_items.vault_ref_type = 'offer_generated_files'` and `vault_ref_id = offer_generated_files.id`. This links the exact generated file version (not just "the F.033 form") to the requirement.

**Regeneration behavior:**
- Regenerating a form for an offer that already has a generated file for that `form_code`: creates a new `offer_generated_files` row with `supersedes_id` pointing to the old one
- The old file remains on disk and its row remains intact
- Any `offer_requirement_items` still pointing to the old generated file show a "Nueva versión disponible" badge, same as for vault documents
- The offer must not be in `listo` or `enviado` state to regenerate — form generation is locked at those states

**UI:**
- `/formularios` — select form, select year (for financial), select personnel → generate → download
- Generated files appear in a "Formularios generados" list inside `/ofertas/{id}` Tab 3
- Each entry shows: form code, generated date, file size, download link, superseded chain if applicable
- Also accessible from bid prep module (context-aware: pre-selects bid data)

---

### M8 — Preparación de Oferta (Bid Preparation)

The centerpiece: ties together a specific monitored bid with the vault and generates the full
submission package.

**Per offer (linked to a `Bid` from v1):**
- Proceso code, título, entidad
- Estado: `borrador` / `en_preparacion` / `listo` / `enviado`
- Fecha límite de oferta (from monitor)

#### Offer State Machine

| State | Meaning | Allowed actions |
|---|---|---|
| `borrador` | Offer created, pliego not yet parsed | Edit notes; trigger pliego fetch + parse; delete offer |
| `en_preparacion` | Parse attempted (any result); checklist in progress | Assign vault items to requirements; generate forms; edit selections; edit timeline events; re-parse |
| `listo` | Parse `verified` + all requirements `CUMPLE` or manually accepted | Assemble package; mark as Enviado |
| `enviado` | Submitted; workspace archived | View only; reopen via explicit user action |

**Transition rules (exact):**
- `borrador → en_preparacion`: automatic when a `offer_parse_attempts` row is created, regardless of parse outcome (even `failed` counts — the user now has something to work with)
- `en_preparacion → listo`: user-triggered; requires parse status = `verified` AND all `offer_requirements` in status `CUMPLE` or `accepted` (accepted = user explicitly acknowledges a gap and accepts the risk with a reason text)
- `listo → enviado`: user manually confirms submission. Requires at least one `offer_snapshots` row to exist. The system does not mark this automatically
- State never advances past `listo` automatically — the user must confirm each transition

**Read-only rules by state:**
- `listo`: parse fields locked; requirements locked (no add/remove/reassign); offer selections (personnel/projects/equipment/financials) locked; form regeneration blocked
- `enviado`: entire offer workspace is fully read-only. No edits, no re-assembly, no re-parse. The offer is an audit record

**If a verified parse is edited after verification:**
- `human_verified_at` and `human_verified_by` are cleared immediately on any edit to `offer_requirements` rows (add, delete, or field change)
- If offer was in `listo` state, it drops back to `en_preparacion` automatically
- A persistent warning banner shows: "Los requisitos han sido modificados. La verificación humana fue invalidada. Revisa y vuelve a verificar antes de ensamblar."
- Existing `offer_snapshots` are preserved untouched — they reflect the previous verified state and are labeled with their `assembled_at` timestamp

**Assembly versioning:**
- Every execution of "Ensamblar Oferta" appends a NEW row to `offer_snapshots` — previous rows are never overwritten or deleted
- The most recent snapshot is the active one; older snapshots are visible in a collapsible "Historial de ensamblados" panel on Tab 4
- Re-assembling in `listo` state is allowed as many times as needed (e.g., to include an updated document). Each creates a new dated snapshot

**After submission (`enviado`):**
- Offer workspace becomes fully read-only — all form inputs disabled
- The linked `Bid` in the monitor retains its own lifecycle independently
- Offer remains visible in `/ofertas` with "Enviado" badge and `enviado_at` timestamp
- No assembly, re-parse, checklist editing, or selection changes are permitted

**Reopening (`enviado → en_preparacion`):**
- "Reabrir oferta" button visible only in `enviado` state
- Requires confirmation dialog: "Reabrir solo si hubo un error en la entrega. El historial de ensamblados y la verificación se conservan."
- On confirmation: state resets to `en_preparacion`; parse verification is preserved (only editing requirements clears it); all snapshots are preserved
- Use case: wrong version submitted, correction needed before re-submitting

**Vault file replacement rule:**
- Replacing or updating a vault document NEVER deletes the old file from disk or its DB row
- Old `vault_documents` rows are superseded via versioning (Patch 2 below) but remain intact
- `offer_requirement_items` and `offer_snapshots` always reference the exact `vault_document_id` (specific version) that was assigned at the time — historical offers are never silently changed

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

5. Each parse attempt is stored as a row in `offer_parse_attempts` (not overwriting the previous attempt). The most recent `verified` attempt (or, if none, the most recent attempt overall) is the "active parse" for the offer.

**Parse attempt fields (`offer_parse_attempts` table):**
- `offer_id`
- `bid_document_id` (FK to `bid_documents` — the exact PDF that was parsed)
- `status`: `pending` / `running` / `parsed` / `needs_review` / `verified` / `failed`
- `confidence_score` (0–100, Gemini self-rated in the prompt response)
- `parser_version` (string identifying the prompt template version, e.g., `v1.0`)
- `raw_extraction` (LONGTEXT — raw Gemini API response body, stored verbatim before any processing)
- `parsed_json` (JSON — structured output after post-processing the raw extraction)
- `failure_reason` (TEXT, nullable — error message if status = `failed`)
- `human_verified_at` (timestamp, nullable)
- `human_verified_by` (user id, nullable)
- `triggered_by` (user id — who initiated this parse attempt)
- `timestamps`

**Parse lifecycle — status values:**
- `pending`: job queued but not yet sent to Gemini
- `running`: HTTP request to Gemini in progress
- `parsed`: Gemini responded with extractable JSON; post-processing succeeded; awaiting human review
- `needs_review`: Gemini responded but confidence_score < 60 OR post-processing found missing required fields; system flags for mandatory human attention before proceeding
- `verified`: human has reviewed and confirmed or corrected the parsed output
- `failed`: Gemini returned an error, timed out, returned malformed JSON that could not be post-processed, or the PDF could not be read

**Failure behavior (explicit):**
- **Timeout / HTTP error:** `status = failed`, `failure_reason` = error message. UI shows: "El análisis automático falló. Puedes reintentar o crear los requisitos manualmente." Retry button triggers a new `offer_parse_attempts` row.
- **Malformed JSON from Gemini:** If the raw response cannot be parsed as JSON, attempt a regex extraction of partial fields. If partial extraction yields ≥1 field, set `status = needs_review` and `parsed_json` = partial result. If nothing is extractable, set `status = failed`.
- **Partial extraction (some fields null):** `status = needs_review`. The UI shows the parsed fields alongside null fields clearly labeled "No encontrado — completar manualmente." The user can fill in missing fields in the verification UI before verifying.
- **All-failure scenario:** If all parse attempts for an offer are `failed`, the checklist starts empty. User can manually add requirements via "Agregar requisito" button regardless of parse status. Manual requirements are regular `offer_requirements` rows with `source = 'manual'`.

**Re-parse behavior:**
- Every re-parse creates a NEW `offer_parse_attempts` row — previous attempts are never overwritten or deleted
- Re-parsing is only allowed when offer state is `borrador` or `en_preparacion`
- If the active parse was `verified` and user triggers a re-parse, a confirmation dialog warns: "Volver a analizar invalidará la verificación actual. Los requisitos existentes se conservarán pero deberán revisarse."
- On re-parse confirmed: `human_verified_at` is cleared on the previous attempt row; a new attempt row is created; existing `offer_requirements` rows are preserved (not deleted) so the user can diff old vs new

**Manual requirements (AI-independent fallback):**
- "Agregar requisito manualmente" button is always visible in Tab 1 (Pliego & Requisitos), regardless of parse status
- Manually created `offer_requirements` rows have `source = 'manual'` and are never overwritten by a subsequent auto-parse
- Auto-parse results populate rows with `source = 'gemini'`; if a re-parse runs, existing `source = 'gemini'` rows from the previous parse are marked `superseded = true` and new ones are created
- The UI groups requirements by source: Gemini (active parse), Manual, Superseded (collapsed by default)

**Parse trust policy:** Parsed requirements (status `parsed` or `needs_review`) are shown with a "Pendiente verificación" banner. The checklist (8b) can be worked on, but the "Ensamblar Oferta" button is blocked until the active parse reaches `verified` status. User clicks "Verificar extracción" to review side-by-side (pliego PDF iframe left + parsed JSON right) and confirm, correct, or accept each field. Once all fields reviewed, click "Confirmar verificación" — sets `status = verified` and stamps `human_verified_at` / `human_verified_by`.

#### 8b — Requirements Checklist (CUMPLE / NO CUMPLE)

Generated from Gemini's parsed output. Per requirement (`offer_requirements` table):
- Description (from pliego)
- Type: document / financial / personnel / equipment / experience / format
- Status: CUMPLE / NO CUMPLE / PENDIENTE
- Notes

Each requirement is satisfied by one or more vault items via the `offer_requirement_items` junction table:
- `offer_requirement_id`
- `vault_ref_type` (vault_documents / personnel / projects / equipment / financials / generated_form)
- `vault_ref_id`
- `role_note` (e.g., "director técnico", "año 2024", "copia certificada")

This design handles real-world cases:
- One requirement satisfied by multiple docs (e.g., RNC + paz y salvo DGII together)
- One document reused across multiple requirements (e.g., RPE satisfies both habilitación and experience)
- One requirement partially satisfied by a generated form plus an uploaded support file

**UI:** Table checklist. Each requirement row has an "Asignar documentos" drawer that lets user search/select from vault items. Status auto-calculates: CUMPLE when ≥1 item assigned, PENDIENTE by default.

#### 8c — Offer Composition (Selections)

Before generating forms, the user selects which vault records to include in this specific offer. These selections are stored in explicit association tables — not as JSON blobs — so forms and assembly have a clean, queryable source of truth.

**Tables:** `offer_personnel`, `offer_projects`, `offer_equipment`, `offer_financials`

Each table has: `id`, `offer_id`, `{entity}_id` (FK to the relevant vault table), `role_note` (optional context, e.g., "director técnico", "año fiscal 2024"), `timestamps`

**Rules:**
- Selections are editable while offer is in `borrador` or `en_preparacion` state
- Selections are locked (read-only) once offer transitions to `listo` or `enviado`
- Deleting a vault record (personnel, project, etc.) that is referenced by an active offer is blocked with an error: "Este registro está en uso por la oferta X. Desactívalo en lugar de eliminarlo."
- At assembly time (M8d), `offer_snapshots` captures the data of the selected records at that exact moment — the selection tables tell the snapshot *what* to freeze

**UI:** Tab 2.5 (or a dedicated "Composición" section) inside `/ofertas/{id}`:
- Personnel picker: search active staff, add to offer with role note
- Projects picker: search portfolio, add to offer
- Equipment picker: search inventory, add to offer
- Financials picker: select one or more fiscal years (for multi-year index requirements)
- Each section shows currently selected records with a remove button (only available in `en_preparacion` state)

#### 8d — Form Generation per Bid

Context-aware form generator within the offer. Pulls from the offer's selection tables:
- F.033: uses bid's entidad, objeto, monto from monitor
- F.037: pulls indices from selected `offer_financials` years
- D.045/D.048: pulls profiles from selected `offer_personnel`
- D.049: pulls records from selected `offer_projects`
- F.036: pulls items from selected `offer_equipment`
- Each generated file is stored as an `offer_generated_files` row (see M7)

#### 8e — Package Assembly

"Ensamblar Oferta" button (blocked until human parse verification complete):

**Step 1 — Packaging plan generation** (before ZIP):

From the parsed pliego (`formato_oferta`, `documentos_requeridos`, `copias`), the system generates a structured packaging plan:

```
Sobre Técnico:
  [ ] F.033 — Formulario de oferta (original + 1 copia)
  [ ] RPE vigente (copia certificada)
  [ ] CV personal clave (original)
  [ ] D.049 Referencias de proyectos (original)
  [ ] Estado financiero 2024 (copia certificada — CPA firmado)

Sobre Económico:
  [ ] F.042 — Resumen económico (original + 1 copia)
  [ ] F.037 — Capacidad financiera (original)

Firmas/sellos requeridos:
  [ ] Representante legal: F.033, F.042
  [ ] Sello empresa: todas las páginas de F.033

Instrucciones de impresión:
  [ ] Sobre técnico: anillado, portada, páginas numeradas
  [ ] Sobre económico: carpeta sellada

Notas entrega física:
  [ ] Entregar en: {entidad}, {dirección_de_entrega}
  [ ] Fecha límite: {fecha_entrega}
```

This packaging plan is shown to the user as a checklist before the ZIP is generated. User can override envelope assignments and copy counts.

**Step 2 — Assembly snapshot** (immutable record):

At assembly time, read from the offer's selection tables (`offer_personnel`, `offer_projects`, `offer_equipment`, `offer_financials`) and snapshot the exact state of every referenced record:
- Company profile copy (JSON serialization of `companies` row at assembly time)
- Financial years used (copies of balance figures + calculated indices from selected `offer_financials`)
- Personnel data (full record copies from selected `offer_personnel` → `personnel` rows)
- Project data (full record copies from selected `offer_projects` → `projects` rows)
- Vault document file hashes: SHA-256 + path for every `vault_documents.id` referenced in `offer_requirement_items` at this moment
- Generated form file hashes: SHA-256 + path for every `offer_generated_files.id` referenced in `offer_requirement_items`
- Assembled ZIP hash + path

All data copies stored as JSON columns in `offer_snapshots`. This is the audit trail — what was actually submitted. Future edits to vault records, personnel, or company profile cannot alter a completed snapshot.

Stored in `offer_snapshots`. Append-only (see State Machine rules).

**Step 3 — ZIP creation:**
- Creates `{proceso_code}_{empresa}_{fecha}.zip`
- Organized into subfolders matching envelope structure
- Shows download link + logs `assembled_at` timestamp

**UI:**
- `/ofertas` — list of bid preparations
- `/ofertas/create?bid={id}` — start from monitor bid
- `/ofertas/{id}` — main prep workspace with tabs:
  - Tab 1: Pliego & Requisitos (8a — parse status, verification)
  - Tab 2: Checklist (8b — requirements + vault assignments)
  - Tab 3: Composición (8c — personnel/projects/equipment/financials selections)
  - Tab 4: Formularios (8d — generate + list generated files)
  - Tab 5: Cronograma (M8g — timeline events)
  - Tab 6: Ensamblar (8e — packaging plan + ZIP + snapshot history)

---

### M8g — Bid Timeline / Task Layer

Parsed `fechas_clave` from the pliego are not just stored as JSON — they become first-class records in `offer_events`.

**Per event:**
- `offer_id`
- `event_type`: `visita_campo` / `aclaraciones_deadline` / `entrega_oferta` / `apertura_sobres` / `adjudicacion_estimada` / `custom`
- `event_date`
- `description`
- `alert_days_before` (default varies by type: submission = 3 days, clarifications = 1 day)
- `alerted_at` (null until notification sent)
- `status`: `pending` / `completed` / `missed`

**UI:**
- Timeline tab inside `/ofertas/{id}` showing all events in chronological order
- Events from parsed pliego auto-populated (user can edit dates)
- User can add custom events
- Dashboard M9 shows upcoming deadlines across all active offers (sourced from `offer_events`)

**Why this matters:** Without first-class event records, the system is a document packer, not an operations platform.

---

### M8h — Source Document Storage

DGCP pliego PDFs fetched during offer creation are stored locally, not just referenced by URL.

**Table: `bid_documents`**

| Column | Type | Notes |
|---|---|---|
| `id` | int | |
| `offer_id` | int FK | |
| `document_type` | string | `pliego`, `bases`, `terminos_ref`, `adenda`, `aclaracion`, `otros` |
| `original_filename` | string | |
| `source_url` | string | DGCP URL used to fetch |
| `downloaded_at` | timestamp | |
| `sha256` | string | file integrity check |
| `local_path` | string | path under `storage/app/bid_docs/` |
| `file_size_bytes` | int | |

**Why:** Without local storage, re-parsing or re-verification requires re-fetching from DGCP (unreliable). Also required for the assembly snapshot audit trail.

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

### M10 — Navigation Restructure ✓ DONE (v1.02)

~~V2 adds 6 new top-level sections. Update `layouts/app.blade.php` nav before building any V2 module.~~

**Completed in v1.02.** Sidebar layout built with `el-dialog` for mobile, fixed `lg:w-72` desktop sidebar, all V2 nav sections stubbed with `cursor-not-allowed` dimming. Grupo Alzare brand colors (blue-800/900). `layouts/sidebar-content.blade.php` shared partial. No further work needed here.

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
- Cache parsed results — never re-parse same document unless user explicitly requests it
- Raw Gemini response stored in `offer_parse_attempts.raw_extraction` for all attempts
- If free tier rate limit is hit (429 response): `status = failed`, `failure_reason = 'rate_limit'`, UI shows: "Límite de solicitudes alcanzado. Espera unos minutos e intenta de nuevo." No automatic retry loop.
- If free tier limits are hit long-term: Settings UI shows a masked `GEMINI_API_KEY` field where user can swap to a paid key without code changes

**GeminiService error handling contract:**
- All calls to Gemini are wrapped in `GeminiService::parsePliego(BidDocument $doc): ParseAttemptResult`
- `ParseAttemptResult` is a value object with: `status`, `parsed_json`, `raw_extraction`, `confidence_score`, `failure_reason`
- The service never throws to the caller for API errors — it always returns a result with `status = failed` and a populated `failure_reason`
- The calling code (job/command) writes the result to `offer_parse_attempts` regardless of outcome
- Timeout: HTTP client configured with 120s timeout for Gemini calls (pliegos can be large PDFs)

---

## Multi-Company Support (V3)

Deferred. V2 is single-company only.

**What `company_id` from day 1 buys us:** The DB migrations don't need to be rewritten. Every vault table already has the FK. That is the easy part.

**What is NOT low-effort in V3:**
- **Vault isolation:** Every query in every controller needs a `->where('company_id', $activeCompany->id)` scope applied consistently. Missing one is a data leak between companies. Needs a global model scope or policy layer.
- **Offer numbering:** Each company may have its own offer sequence. Shared counters break.
- **Document storage isolation:** `storage/app/vault/` paths need company-namespacing. Existing single-company paths would need migration.
- **Template selection:** Generated form templates may differ per company (different logos, rep legal, address).
- **User-to-company permissions:** Multi-user V3 brings RBAC — which user can see which company's data. That is a non-trivial auth layer.
- **Company onboarding UX:** Filling out full Empresa profile for each new company from scratch. Needs import/copy tools.

**Bottom line:** V3 multi-company is a meaningful engineering effort, not a one-liner. The `company_id` FK decision avoids re-migrating the DB — everything else still needs design. Plan accordingly if this is scoped for V3.

---

## Security Stance

V1 had no login wall (single operator, internal tool, low-risk data). V2 introduces a vault containing company RNC, tax documents, financial statements, corporate records, signed forms, and personnel IDs. That changes the risk profile completely.

**Required before V2 vault is used in production:**

1. **Authentication:** V1 already has Laravel auth + users table. The login wall must be enforced on all vault and offer routes via `auth` middleware. No vault route is publicly accessible.

2. **Private storage disk:** Configure a `vault` disk in `config/filesystems.php` pointing to `storage/app/vault/` (outside `public/`). Never store vault files in `public/storage/`.

3. **Controller-gated downloads:** All vault file downloads go through a controller method that checks auth and ownership before streaming the file. No direct file URLs exposed. Use `Storage::disk('vault')->download()`.

4. **Encrypted secrets:** `.env` values for `APP_KEY`, `GEMINI_API_KEY`, and any future API keys must be present and never committed to version control. `.env` in `.gitignore` — already standard Laravel.

5. **Signed URLs or session-bound download tokens** for any generated ZIPs or DOCX files (at minimum, route through controller).

6. **Backup policy:** At minimum, daily `mysqldump` + copy of `storage/app/vault/` to a separate location. Vault data is irreplaceable. Document the backup job in the server runbook.

7. **`.env` APP_ENV=production:** Disable debug mode in production. No stack traces exposed to browser.

**Out of scope for V2:** Field-level encryption (overkill for single-server internal tool), S3 migration, SSO.

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

-- Document vault (immutable versioned rows)
vault_documents: id, company_id, category, name, filename, path,
                 issued_at, expires_at, notes,
                 issuer, document_number, signed_by, notarized, copy_type,
                 language, tags, source_type, source_id, internal_only,
                 replaces_document_id, superseded_at, is_current,
                 timestamps

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
offers: id, bid_id, company_id, estado, notes, timestamps
-- Note: parse metadata moved to offer_parse_attempts; assembly metadata to offer_snapshots

-- AI parse attempts (append-only; multiple per offer allowed)
offer_parse_attempts: id, offer_id, bid_document_id, status, confidence_score,
                      parser_version, raw_extraction, parsed_json, failure_reason,
                      human_verified_at, human_verified_by, triggered_by, timestamps

-- Offer composition selections (explicit association tables, not JSON)
offer_personnel:  id, offer_id, person_id,   role_note, timestamps
offer_projects:   id, offer_id, project_id,  role_note, timestamps
offer_equipment:  id, offer_id, equipment_id, role_note, timestamps
offer_financials: id, offer_id, financial_id, role_note, timestamps

-- Generated forms (persisted, versioned)
offer_generated_files: id, offer_id, form_code, source_context_json,
                       path, sha256, generated_at, generated_by,
                       supersedes_id, timestamps

-- Requirements checklist per offer
offer_requirements: id, offer_id, tipo, descripcion, status, notas, timestamps

-- Vault items satisfying each requirement (many-to-many junction)
offer_requirement_items: id, offer_requirement_id, vault_ref_type, vault_ref_id,
                         role_note, timestamps

-- Bid timeline events (parsed from fechas_clave + manual)
offer_events: id, offer_id, event_type, event_date, description,
              alert_days_before, alerted_at, status, timestamps

-- Source documents fetched from DGCP
bid_documents: id, offer_id, document_type, original_filename, source_url,
               downloaded_at, sha256, local_path, file_size_bytes, timestamps

-- Assembly snapshots (immutable, append-only — one row per assembly run)
offer_snapshots: id, offer_id, assembled_at, assembled_by,
                 parse_attempt_id,              -- which parse attempt was active at assembly time
                 company_snapshot_json,         -- full companies row copy
                 personnel_snapshot_json,       -- full data of selected offer_personnel records
                 projects_snapshot_json,        -- full data of selected offer_projects records
                 equipment_snapshot_json,       -- full data of selected offer_equipment records
                 financials_snapshot_json,      -- full data of selected offer_financials records (with calculated indices)
                 vault_file_hashes_json,        -- [{vault_document_id, sha256, path}] for each assigned doc
                 generated_file_hashes_json,    -- [{offer_generated_files_id, sha256, path}] for each generated form
                 zip_sha256, zip_path, timestamps
```

**Note:** `vault_documents` extended fields and `offers` AI parse fields are now integrated into the main schema table definitions above. No separate ALTER statements needed — build migrations from scratch using the full definitions.

**Financials table extended fields (additions for override + currency):**
```sql
ALTER TABLE financials ADD COLUMN currency CHAR(3) DEFAULT 'DOP';
ALTER TABLE financials ADD COLUMN solvencia_override DECIMAL(10,4) NULL;
ALTER TABLE financials ADD COLUMN liquidez_override DECIMAL(10,4) NULL;
ALTER TABLE financials ADD COLUMN endeudamiento_override DECIMAL(10,4) NULL;
ALTER TABLE financials ADD COLUMN override_reason TEXT NULL;
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
/formularios                              GET       — generator UI
/formularios/generate                     POST      — generate + store offer_generated_files row + download
/documentos/{id}/download                 GET       — controller-gated file download (auth required)
/documentos/{id}/replace                  POST      — upload new version, supersede old row
/documentos/{id}/versions                 GET       — list all versions in chain
/ofertas                                  GET       — offer list
/ofertas/create                           GET/POST  — start new offer from bid
/ofertas/{id}                             GET       — offer workspace
/ofertas/{id}/parse-pliego                POST      — trigger Gemini parse (creates new offer_parse_attempts row)
/ofertas/{id}/parse-attempts/{attempt_id} GET       — view single parse attempt detail
/ofertas/{id}/verify-parse                POST      — mark active parse as verified
/ofertas/{id}/requirements                GET/POST  — list + manually add requirements
/ofertas/{id}/requirements/{rid}          PUT/DELETE
/ofertas/{id}/selections/personnel        POST/DELETE — manage offer_personnel rows
/ofertas/{id}/selections/projects         POST/DELETE — manage offer_projects rows
/ofertas/{id}/selections/equipment        POST/DELETE — manage offer_equipment rows
/ofertas/{id}/selections/financials       POST/DELETE — manage offer_financials rows
/ofertas/{id}/generated-files             GET       — list offer_generated_files
/ofertas/{id}/generated-files/{fid}/download GET    — controller-gated download of generated file
/ofertas/{id}/assemble                    POST      — build packaging plan + ZIP + create offer_snapshots row
/ofertas/{id}/snapshots                   GET       — list all offer_snapshots (assembly history)
/ofertas/{id}/snapshots/{sid}/download    GET       — download a specific assembled ZIP
/ofertas/{id}/transition                  POST      — advance/reopen offer state (body: {to: 'listo'|'enviado'|'en_preparacion'})
```

---

## Build Order

0. **✓ Nav restructure** — DONE in v1.02. Sidebar layout with all V2 sections stubbed.
1. **Empresa + Documentos (M1 + M2)** — foundation, needed by all forms. Auth middleware enforcement + vault storage disk configured here.
2. **Personal (M3)** — HR vault with D.045/D.048 generation
3. **Proyectos (M4)** — portfolio with D.049 generation
4. **Equipos (M5)** — F.036 generation
5. **Financiero (M6)** — balance sheet + index calculation with override fields
6. **Formularios (M7)** — form generator using above vaults
7. **Gemini integration (M8a)** — pliego parser with verification fields
8. **Ofertas (M8 sub-steps + M8g + M8h)** — state machine, parse attempts, composition selections, junction-table checklist, form generation with persistence, packaging plan, assembly snapshots, timeline events, source document storage
9. **Dashboard V2 (M9)** — expiry alerts, active offers summary, upcoming deadlines from offer_events

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
- **OQ-V2-7:** Offer state machine locked as defined in V2.2 patch. State transitions are user-triggered (no auto-advance past `listo`). Reopening from `enviado` is allowed with confirmation.
- **OQ-V2-8:** Vault file versioning uses immutable rows with `replaces_document_id` / `superseded_at` / `is_current`. Old files are never deleted. Offer requirement items reference specific version IDs.
- **OQ-V2-9:** Parse results stored in `offer_parse_attempts` (append-only). Re-parses create new rows. Manual requirements are always available regardless of AI parse outcome.
- **OQ-V2-10:** Offer composition uses explicit `offer_personnel`, `offer_projects`, `offer_equipment`, `offer_financials` tables — not JSON blobs. Selections locked at `listo` state.
- **OQ-V2-11:** Generated forms persisted in `offer_generated_files` with `source_context_json` and `supersedes_id` chain. Linked to requirements via `offer_requirement_items`.

---

## V2.2 Structural Clarifications Added

Applied 2026-03-12 in response to identified structural gaps.

- **State machine (Offer workflow):** Added explicit `borrador → en_preparacion → listo → enviado` transition table with exact trigger conditions, read-only locking rules per state, reopening procedure, and rule that editing a verified parse clears verification and regresses state. Assembly is append-only (new snapshot per run). Vault file replacement never silently alters historical offer references.

- **Vault file versioning:** `vault_documents` is now an immutable versioned table. File replacement creates a new row with `replaces_document_id` pointing to the old row; old row gets `superseded_at` set and `is_current = false`. Downloads always serve the exact requested row ID. `offer_requirement_items` stores specific version IDs. "Nueva versión disponible" badge shown on checklist items when a newer vault version exists.

- **Generated forms persistence:** Added `offer_generated_files` table with `form_code`, `source_context_json` (data snapshot at generation time), `path`, `sha256`, `generated_at`, `generated_by`, `supersedes_id`. Generated forms link to requirements via `offer_requirement_items.vault_ref_type = 'offer_generated_files'`. Regeneration appends a new row, preserving the old file. Generation locked in `listo`/`enviado` state. Standalone `/formularios` generations have nullable `offer_id`.

- **Offer composition selection tables:** Replaced implied JSON blobs with four explicit tables: `offer_personnel`, `offer_projects`, `offer_equipment`, `offer_financials`. Each has `offer_id`, the relevant entity FK, and `role_note`. Selections are editable in `borrador`/`en_preparacion`, locked at `listo`/`enviado`. Assembly snapshots read from these tables to freeze exact record data. Deleting a vault entity referenced by an active offer is blocked with an error.

- **AI/manual fallback + parse lifecycle:** Added `offer_parse_attempts` table (append-only). Parse `status` enum: `pending → running → parsed / needs_review / failed → verified`. Defined malformed JSON behavior (partial extraction attempt → `needs_review` or `failed`), timeout/HTTP error behavior (`failed` + `failure_reason`), partial extraction behavior (`needs_review` with null fields surfaced for manual completion). Manual requirement creation (`source = 'manual'`) always available regardless of parse outcome. Re-parse creates new row, preserves previous attempts. `GeminiService` never throws — always returns a `ParseAttemptResult`. HTTP client timeout set to 120s. Checklist/assembly cannot reach `listo` until active parse is `verified`.
