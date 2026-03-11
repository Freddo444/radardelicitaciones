# SECP Procurement Monitor — Master Plan

**Status: LOCKED**
**Last updated: 2026-03-11**

---

## Purpose

A single-user web application that polls the Dominican Republic DGCP (Dirección General de Contrataciones Públicas) API hourly, filters new procurement processes ("convocatorias") against a personal rubro watchlist, and sends instant notifications via Email and Telegram. No noise — only bids that match your codes.

---

## Configuration Decisions (FINAL)

| Decision | Value |
| --- | --- |
| Rubro codes available? | Not yet — UI must support searching the UNSPSC catalog |
| Notification channels | Email (primary) + Telegram |
| Poll frequency | Every 1 hour |
| Multi-user? | No — single operator, no login wall |
| Minimum amount filter | Off by default — UI toggle must exist |
| Email provider | Gmail — App Password already configured by user |
| Telegram bot | User creates bot — setup instructions in first-run guide |
| Deployment | This server — PHP + MySQL already present |
| UI language | Spanish (dev communication in English) |
| Currency | DOP primary, USD secondary |

---

## Tech Stack

| Layer | Technology | Justification |
| --- | --- | --- |
| Backend | Laravel 12 (PHP) | Server already has PHP/MySQL, scheduler + queue built in |
| Database | MySQL | Already running on this server |
| Frontend | Blade + Alpine.js + Tailwind CSS | No build pipeline, reactive enough for this scope |
| Notifications | Laravel Mail (Gmail SMTP) + Telegram Bot API | Email = record; Telegram = instant mobile push |
| Scheduler | Laravel Scheduler | Single cron entry manages all timed jobs |
| Queue | Laravel Queue (database driver) | Async notifications, no Redis needed |
| HTTP Client | Guzzle (via Laravel Http facade) | DGCP API calls + Telegram API calls |

---

## DGCP API Facts (confirmed from official Swagger)

- **Portal:** `https://datosabiertos.dgcp.gob.do`
- **Base URL:** `https://datosabiertos.dgcp.gob.do/api-dgcp/v1`
- **Swagger UI:** `https://datosabiertos.dgcp.gob.do/api-dgcp/docs/index.html`
- **Authentication:** None — fully public API, no tokens or keys required
- **Rate limits:** None documented — we stay conservative with delays anyway
- **No webhooks** — polling is the only option
- **Data refresh frequency:** Every 8 hours from SECP Data Warehouse — hourly polling ensures we catch each refresh window, maximum notification lag is 8 hours from SECP publication
- **Rubro system:** UNSPSC codes (8-digit), 4 levels: segmento / familia / clase / subclase
- **Currency:** DOP (primary) and USD

---

## API Endpoints Used

### Primary polling endpoint

```text
GET /procesos
  ?startdate={last_polled_at}
  &enddate={now}
  &page=1
  &limit=100
```

Returns all procurement processes published in the date window.
Paginate until `page * limit >= totalResults`.

### Articles (rubro data per process)

```text
GET /procesos/articulos
  ?proceso={process_code}
```

Returns UNSPSC-coded items for a specific process.
Used to match against the user's watchlist after fetching new processes.

### Catalog search (used in the rubros UI)

```text
GET /catalogo
  ?subclase={term_or_code}
  &familia={code}
  &clase={code}
  &page=1
  &limit=50
```

Used to power the typeahead search when adding new rubros.

### Planned purchases (bonus — future use)

```text
GET /pacc
  ?año={year}
```

Annual purchasing plans — useful for seeing upcoming tenders before they are formally published.
Not used in v1 polling, but stored as a future feature flag.

---

## Polling Strategy

Because `/procesos/articulos` does not support date filtering, we use a two-pass approach:

1. `GET /procesos?startdate=X&enddate=Y` — fetch all new processes in the window (paginated)
2. For each new process not already in DB: `GET /procesos/articulos?proceso={code}` — fetch its items
3. Match items against active rubros in-app
4. If any match: insert into `bids`, dispatch notification job
5. If no match: discard (do not store)

This is N+1 calls per poll cycle, but hourly polling in the DR market produces a manageable volume of new processes per hour.

---

## UNSPSC Code Structure

All rubro codes are 8-digit UNSPSC codes:

| Level | Field | Example | Meaning |
| --- | --- | --- | --- |
| Segmento | `segmento` | `78000000` | Transportation and Storage |
| Familia | `familia` | `78100000` | Mail and cargo transport |
| Clase | `clase` | `78101800` | Freight transport |
| Subclase | `subclase` | `78101801` | Road freight transport |

Users watch at any level. Matching logic: a process matches if any of its article codes **starts with** a watched code (so watching a familia matches all its clases and subclases automatically).

---

## Database Schema

### `settings`

```text
key            | varchar  | PK
value          | text
updated_at     | timestamp
```

Keys: `last_polled_at`, `poll_interval_minutes`, `smtp_host`, `smtp_port`, `smtp_user`,
`smtp_password`, `smtp_from`, `notification_email`, `telegram_bot_token`, `telegram_chat_id`,
`min_amount_filter`, `min_amount_value`, `min_amount_currency`

---

### `rubros`

```text
id             | bigint   | PK
code           | varchar  | UNIQUE — 8-digit UNSPSC code
name           | varchar  | human-readable label from catalog
level          | enum(segmento, familia, clase, subclase)
active         | boolean  | default true
notes          | text     | nullable
created_at     | timestamp
```

---

### `bids`

```text
id                 | bigint        | PK
process_code       | varchar       | UNIQUE — DGCP process identifier
ocid               | varchar       | nullable — OCDS ID if available
title              | varchar
buyer_name         | varchar
buyer_code         | varchar       | unidad_compra code
procurement_method | varchar       | modalidad
status             | varchar       | estado
amount_estimated   | decimal(15,2) | nullable
currency           | varchar(10)   | DOP or USD
published_at       | datetime      | nullable
tender_deadline    | datetime      | nullable
matched_rubros     | json          | array of matched UNSPSC codes and names
secp_url           | varchar       | direct portal link
raw_data           | json          | full API response
notified_at        | datetime      | nullable
created_at         | timestamp
```

---

### `notification_log`

```text
id             | bigint  | PK
bid_id         | bigint  | FK -> bids.id
channel        | enum(email, telegram)
status         | enum(sent, failed)
error_message  | text    | nullable
created_at     | timestamp
```

---

## Module Breakdown

### Module 1 — DgcpApiClient

- No authentication needed
- Wraps all HTTP calls to `datosabiertos.dgcp.gob.do/api-dgcp/v1`
- Conservative delay between requests (no documented rate limit, but we respect the server)
- Handles pagination automatically
- Methods:
  - `fetchNewProcesses(DateTime $from, DateTime $to): Collection`
  - `fetchProcessArticles(string $processCode): Collection`
  - `searchCatalog(string $term, ?string $familia, ?string $clase): Collection`

### Module 2 — PollCommand (`artisan secp:poll`)

- Reads `last_polled_at` from settings
- Calls `DgcpApiClient::fetchNewProcesses()`
- For each new process: calls `fetchProcessArticles()` then runs `RubroFilter`
- Matching processes upserted into `bids` table (dedup by `process_code`)
- Dispatches `SendBidNotification` job for each new match
- Updates `last_polled_at`
- Logs poll summary (total checked, matched, notified)

### Module 3 — RubroFilter

- Loads all active rubros from DB
- For each article code: checks if it starts with any watched code
- Returns true/false + array of which rubros matched

### Module 4 — SendBidNotification (queued job)

- Receives a `Bid` model
- Sends email via `BidNotificationMailable` (Gmail SMTP)
- Sends Telegram message via Bot API
- Logs each channel to `notification_log`
- Sets `bids.notified_at`

### Module 5 — Scheduler

Single cron entry on the server:

```bash
* * * * * cd /var/www/html/SECP && php artisan schedule:run >> /dev/null 2>&1
```

Laravel scheduler runs `secp:poll` hourly via `routes/console.php`.

---

## Dashboard — Routes & Views

| Route | View | Purpose |
| --- | --- | --- |
| `GET /` | `dashboard` | Bid feed — paginated, filterable matched bids |
| `GET /rubros` | `rubros.index` | Manage UNSPSC watchlist |
| `POST /rubros` | — | Add a rubro by code or name search |
| `DELETE /rubros/{id}` | — | Remove a rubro |
| `PATCH /rubros/{id}/toggle` | — | Activate / deactivate |
| `GET /rubros/search` | JSON | Search UNSPSC catalog (AJAX typeahead) |
| `GET /configuracion` | `settings` | OAuth, SMTP, Telegram, poll interval, amount filter |
| `POST /configuracion` | — | Save settings |
| `POST /sondeo/manual` | — | Trigger immediate poll |
| `GET /sondeo/estado` | JSON | Last poll time, next poll, last count |
| `GET /registros` | `logs` | Notification delivery log |

---

## Bid Card — Displayed Fields

- Título del proceso (links to SECP portal)
- Institución compradora
- Código de unidad de compra
- Modalidad de contratación
- Estado del proceso
- Monto estimado + moneda (DOP / USD)
- Fecha de publicación
- Fecha límite de ofertas
- Rubros coincidentes (UNSPSC badges con nombre)
- Fecha de notificación
- Estado de entrega: email / telegram

---

## Notification Formats

### Email

- **Asunto:** `[SECP] {title} — {buyer_name}`
- **Cuerpo:** HTML en español — todos los campos + enlace directo al portal SECP + rubros resaltados

### Telegram

```text
🔔 Nueva Convocatoria SECP

📋 {title}
🏢 {buyer_name}
💰 {currency} {amount_estimated}
📅 Cierre de ofertas: {tender_deadline}
🏷 Rubros: {matched_codes}

🔗 {secp_url}
```

---

## Settings Page — Configurable Fields

### SECP / DGCP API

- No credentials needed — connection test only
- [ Probar conexión ] button

### Sondeo

- Intervalo en minutos — default 60
- Último sondeo (read-only)
- Próximo sondeo (read-only)
- [ Sondear ahora ] button

### Email (Gmail)

- SMTP Host — prefilled: `smtp.gmail.com`
- SMTP Port — prefilled: `587`
- Usuario (Gmail address)
- App Password
- Dirección de destino (recipient)
- [ Enviar correo de prueba ] button

### Telegram

- Bot Token
- Chat ID
- [ Enviar mensaje de prueba ] button

### Filtro de monto mínimo

- Toggle ON/OFF
- Monto mínimo
- Moneda (DOP / USD)

---

## First-Run Setup Flow

1. Run `composer install` in project directory
2. Copy `.env.example` to `.env`, set `APP_KEY` and DB credentials (`secp_monitor` database)
3. Run `php artisan migrate`
4. Configure nginx vhost pointing to `/var/www/html/SECP/public`
5. Open `/configuracion` in browser
6. Test DGCP API connection
7. Enter Gmail address + App Password → Enviar correo de prueba
8. Create Telegram bot via @BotFather, get token; get Chat ID via @userinfobot → Enviar mensaje de prueba
9. Open `/rubros` → search and add your UNSPSC rubro codes when assigned
10. Click "Sondear ahora" to run first poll
11. Add cron entry to server

---

## Deployment — This Server

- **Path:** `/var/www/html/SECP/`
- **Web root:** `/var/www/html/SECP/public/`
- **Nginx:** new server block (local port or local hostname)
- **DB:** existing MySQL instance, new database `secp_monitor`
- **Queue worker:** `php artisan queue:work` via supervisor or cron fallback

---

## Out of Scope — v1

- Multi-user authentication
- WhatsApp notifications
- Historical data import (monitors from go-live forward only)
- Mobile app
- PACC (planned purchases) monitoring
- Charts or analytics

---

## Build Order (locked sequence)

1. Laravel install + migrations + `.env` scaffold + nginx vhost
2. `DgcpApiClient` (core API layer, no auth)
3. `PollCommand` + `RubroFilter` (polling engine)
4. `SendBidNotification` job (email + telegram)
5. Configuración page — credentials entered before anything runs
6. Rubros management page — UNSPSC search + add + toggle
7. Bid feed dashboard
8. Notification logs page
9. Scheduler registration + cron entry
10. End-to-end test with real data

---

*This document is the single source of truth. Plan is LOCKED. No scope changes without explicit supervisor approval.*
