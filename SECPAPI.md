# DGCP API — TL;DR for Claude IDEs
## Overview
**Base URL:** `https://datosabiertos.dgcp.gob.do/api-dgcp/v1`
**Auth:** None required (public open data API)
**Method:** All endpoints are GET
**Default pagination:** page=0, limit=100 (max 1000 where noted)
**Response envelope:** `{ "code": 200, "hasError": false, "payload": { "content": [...] } }`
**Domain:** Dominican Republic public procurement data (DGCP = Dirección General de Contrataciones Públicas)
---
## Endpoints
### 📦 CATÁLOGO — UNSPSC product/service catalog
**GET /catalogo**
Returns catalog records filtered by UNSPSC hierarchy codes.
- `page` (int) — page number, default: first page
- `limit` (int) — records per page, default: 100
- `segmento` (string) — UNSPSC segment code
- `familia` (string) — UNSPSC family code
- `clase` (string) — UNSPSC class code
- `subclase` (string) — UNSPSC subclass code
---
### 📄 CONTRATOS — Signed contracts
**GET /contratos**
Returns all public contracts registered in the system.
- `page` (int) — page number
- `limit` (int) — records per page, default: 100
- `rpe` (int) — provider RPE (registry number)
- `proceso` (string) — process code
- `unidad_compra` (int) — purchasing unit code
- `contrato` (string) — contract code
**GET /contratos/articulos**
Returns contracted line items linked to a specific process or contract.
- `page` (int)
- `limit` (int) — default: 100
- `proceso` (string) — process code
- `contrato` (string) — contract code
- `familia` (int) — UNSPSC family code
- `clase` (int) — UNSPSC class code
- `subclase` (int) — UNSPSC subclass code
---
### 🌐 OCDS — Open Contracting Data Standard
**GET /ocds/releases**
Returns a single OCDS release for a process by its OCID.
- `ocid` (string) **REQUIRED** — Format for Dominican Republic: `ocds-6550wx-CODIGO_PROCESO`
**GET /ocds/releases/all**
Returns all OCDS releases (paginated), compliant with international OCDS standard.
- `page` (int)
- `limit` (int) — default: 100, max: 1000
- `start_date` (string) — format: `YYYY-MM-DD`
- `end_date` (string) — format: `YYYY-MM-DD`
- `year` (int) — filter by year
- `unidad_compra` (int) — purchasing unit code (unnamed param in UI)
---
### 💰 OFERTAS — Bids/offers submitted
**GET /ofertas**
Returns all public bids registered in the system.
- `page` (int)
- `limit` (int) — default: 100
- `rpe` (int) — provider RPE
- `proceso` (string) — process code
- `unidad_compra` (int) — purchasing unit code
---
### 📋 PACC — Annual Purchasing Plan (Plan Anual de Compras y Contrataciones)
**GET /pacc**
Returns PACC records by page and limit.
- `page` (int)
- `limit` (int) — default: 100
- `año` (int) — year, default: current year
- `unidad_compra` (int) — purchasing unit code
- *(unnamed string)* — PACC ID in format: `PACC-AÑO-UC-CODIGO_UNIDAD_COMPRA`
**GET /pacc/adquisiciones**
Returns all acquisition records within a PACC.
- `page` (int)
- `limit` (int) — default: 100
- `unidad_compra` (int)
- `año` (int) — default: current year
- `pacc_id` (string) — PACC identifier
**GET /pacc/articulos**
Returns line items within a PACC acquisition.
- `page` (int)
- `limit` (int) — default: 100
- `unidad_compra` (int)
- `adquisicion_id` (string) — acquisition ID within the PACC
- `pacc_id` (string) — PACC identifier
- `año` (int) — default: current year
---
### ⚙️ PROCESOS — Procurement processes
**GET /procesos**
Returns all published procurement processes ordered by publication date.
- `page` (int)
- `limit` (int) — default: 100, max: 1000
- `proceso` (string) — process code
- `unidad_compra` (int)
- `modalidad` (string) — procurement modality
- `estado` (string) — process status
- `mipyme` (boolean) — filter for MSME-reserved processes
- `mipyme_mujer` (boolean) — filter for women-owned MSME processes
- `marco_decreto_3122` (boolean) — filter by Decree 312-22 framework
- `objeto_proceso` (string) — process object/description (search)
- `startdate` (string) — publication date from, format: `YYYY-MM-DD`
- `enddate` (string) — publication date to, format: `YYYY-MM-DD`
**GET /procesos/agrupados**
Returns processes grouped by modality, exception type, purchasing unit, title, and description.
- `unidad_compra` (int) **REQUIRED**
**GET /procesos/articulos**
Returns the list of items/articles for a process.
- `proceso` (string) — process code
- `familia` (int) — UNSPSC family code
- `clase` (int) — UNSPSC class code
- `subclase` (int) — UNSPSC subclass code
- `page` (int)
- `limit` (int) — default: 100
**GET /procesos/documentos**
Returns all documents associated with a process.
- `proceso` (string) **REQUIRED** — process code
**GET /procesos/mipymes/articulos**
Returns the top 10 items most awarded to MSMEs and women-owned MSMEs.
- No parameters
**GET /procesos/mipymes/cuota_global**
Returns national-level MSME quota: awarded amounts and percentages for MSMEs and women-owned MSMEs.
- `año` (int) — year, default: current year
**GET /procesos/mipymes/cuota_institucion**
Returns institution-level MSME quota compliance: awarded amounts and percentages per purchasing unit.
- `unidad_compra` (int) — if omitted, returns all institutions
- `año` (int) — default: current year
---
### 🏢 PROVEEDORES — Suppliers/vendors
**GET /proveedores**
Returns all registered suppliers.
- `page` (int)
- `limit` (int) — default: 100
- `rpe` (int) — provider registry number
- `numero_documento` (string) — ID/RNC document number
- `estado` (string) — status (activo, suspendido, cancelado, etc.)
- `pais` (string) — country
- `region` (string) — region
- `provincia` (string) — province
- `municipio` (string) — municipality
**GET /proveedores/estadisticas-mujeres**
Returns statistical info on MSME and women participation in procurement.
- No parameters
**GET /proveedores/rubro**
Returns a supplier's business category (rubro).
- `rpe` (int) — provider RPE
- `rubro` (string) — UNSPSC FAMILY code used as category
- `page` (int)
- `limit` (int) — default: 100
---
### 📊 TABLAS — Bulk file downloads (xlsx/csv)
**GET /tablas/contratos**
Downloads contracts bulk table file.
- `Type` (string) — `xlsx` (default) or `csv`
**GET /tablas/contratos/articulos**
Downloads awarded items bulk table file.
- `semestre` (int) **REQUIRED** — semester (1 or 2)
- `año` (int) **REQUIRED** — year
- `Type` (string) — `xlsx` (default) or `csv`
**GET /tablas/procesos**
Downloads processes bulk table file.
- `Type` (string) — `xlsx` (default) or `csv`
**GET /tablas/procesos/articulos**
Downloads process line items bulk table file.
- `semestre` (int) **REQUIRED** — semester (1 or 2)
- `año` (int) **REQUIRED** — year
- `Type` (string) — `xlsx` (default) or `csv`
**GET /tablas/proveedores**
Downloads suppliers bulk table file.
- `inhabilitados` (boolean) — if true, returns disqualified suppliers instead
- `Type` (string) — `xlsx` or `csv`
---
### 🏛️ UNIDADES DE COMPRA — Purchasing units (institutions)
**GET /unidades_compra**
Returns all purchasing units (government institutions) registered in the system.
- `codigo_unidad_compra` (int) — specific unit code
- `limit` (int) — default: 100
- `page` (int)
---
## Key Concepts
| Term | Meaning |
|---|---|
| `proceso` | Procurement process code (e.g. `SENC-CCC-2024-0001`) |
| `rpe` | Provider Registry Number (Registro de Proveedores del Estado) |
| `unidad_compra` | Purchasing unit = government institution/entity |
| `OCID` | OCDS identifier format: `ocds-6550wx-CODIGO_PROCESO` |
| `PACC` | Annual Procurement Plan (`PACC-AÑO-UC-CODIGO`) |
| `mipyme` | Micro, Small & Medium Enterprise (MSME) |
| `UNSPSC` | Product classification: segmento > familia > clase > subclase |
| `modalidad` | Procurement modality (e.g., Licitación Pública, Comparación de Precios) |
## Notes
- All endpoints return paginated results. Use `page` + `limit` to traverse large datasets.
- Bulk `/tablas/*` endpoints return file downloads, not JSON.
- OCDS endpoints conform to the international Open Contracting Data Standard.
- Parameters marked **REQUIRED** will cause an error if omitted.
- Date formats throughout the API: `YYYY-MM-DD`
