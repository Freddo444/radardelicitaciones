# SECP Monitor

Procurement monitoring application for the Dominican Republic DGCP (Dirección General de Contrataciones Públicas). Polls the public DGCP API, matches new procurement processes against a configurable UNSPSC rubro watchlist, and delivers instant notifications via Email and Telegram.

## What it does

### Core Monitoring
- **Polls the DGCP API** on a configurable interval (default: hourly) for new procurement processes
- **Matches by UNSPSC code** at familia, clase, or subclase level — watching a familia automatically catches all classes and subclasses beneath it
- **Notifies instantly** via Gmail SMTP and/or Telegram bot when a match is found, or batches them into a configurable digest
- **Filters noise** — skip processes below/above an amount threshold, exclude modalities you don't care about, or ignore processes whose tender deadline has already passed
- **Auto-cleans** expired and closed processes from the feed
- **Local UNSPSC catalog** — 18,000+ codes stored locally for instant code lookup when adding rubros

### Tablero (Kanban Board)
- **Kanban workflow** — track offers across 7 stages: Oportunidades → En proceso → Lista → Entregada → Adjudicada / Perdida / Impugnación
- **Calendar view** — monthly calendar showing offer deadlines and events
- **Filters** — by entity, deadline range, and estimated amount
- **.ics export** — download calendar events for bids and offers

### Inteligencia (Market Intelligence)
- **Contratos** — browse awarded contracts synced from the DGCP API
- **Adjudicados** — awarded articles by rubro with pricing data
- **PACC** — annual purchase plans and planned acquisitions with UNSPSC parsing
- **Proveedores** — searchable provider directory (124k+ suppliers)
- **Instituciones** — purchasing unit directory

### Document Management
- **Prellenado** — reusable document packages assembled from company vault, personnel, equipment, and project records
- **Documentos generados** — auto-filled DGCP forms (SNCC series) from company data
- **Vault** — centralized document storage with versioning, expiry alerts, and categorization

### Administration
- **User roles** — admin/user roles with protected routes
- **Collapsible sidebar** — persistent collapse state via localStorage
- **Breadcrumbs** — auto-generated from route names

## Stack

| Layer | Technology |
|---|---|
| Backend | Laravel 12 (PHP 8.3) |
| Database | MySQL |
| Frontend | Blade + Alpine.js + Tailwind CSS v4 |
| Queue | Laravel Queue (database driver) |
| Worker | Supervisor |
| Notifications | Gmail SMTP + Telegram Bot API |
| API | DGCP Datos Abiertos (public, no auth) |

## Setup

### Requirements

- PHP 8.2+
- MySQL 8+
- Composer
- Node.js + npm
- Supervisor (for queue worker)

### Installation

```bash
git clone https://github.com/Freddo444/SECPMonitor.git
cd SECPMonitor

composer install
npm install && npm run build

cp .env.example .env
php artisan key:generate
```

Configure your `.env`:

```env
DB_DATABASE=secp_monitor
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your@gmail.com
MAIL_PASSWORD=your_app_password
MAIL_FROM_ADDRESS=your@gmail.com
```

Run migrations and seed the catalog:

```bash
php artisan migrate
php artisan secp:import-catalog
```

### Scheduler

Add to server crontab:

```bash
* * * * * cd /path/to/SECPMonitor && php artisan schedule:run >> /dev/null 2>&1
```

Scheduled commands:

| Command | Frequency | Purpose |
|---|---|---|
| `secp:poll` | Hourly | Fetch new procurement processes |
| `secp:sync-providers` | Weekly | Sync provider directory |
| `secp:sync-contracts` | Monthly | Sync contracts and awarded articles |
| `secp:sync-pacc` | Monthly | Sync annual purchase plans |
| Digest notifications | Configurable | Batch email notifications |

### Queue Worker (Supervisor)

A ready-to-use Supervisor config is included at `storage/nginx-secp.conf`. Configure Supervisor to run:

```bash
php artisan queue:work database --sleep=3 --tries=3 --max-time=3600
```

## First Run

1. Open `/configuracion`
2. Test DGCP API connection
3. Enter Gmail address + App Password → send test email
4. Create a Telegram bot via @BotFather, get Chat ID via @userinfobot → send test message
5. Go to `/rubros` → enter UNSPSC codes to monitor
6. Click "Sondear ahora" to run the first poll
7. Run initial data syncs:

```bash
php artisan secp:sync-providers
php artisan secp:sync-contracts
php artisan secp:sync-pacc
```

## Polling Strategy

Uses an articles-first approach for efficiency:

1. For each active rubro, fetch `/procesos/articulos` filtered by code since last poll
2. Collect matched process codes
3. Direct lookup per new process via `/procesos?proceso=CODE`
4. Apply filters (amount, modality, deadline)
5. Save matches, dispatch notifications

This scales well for watchlists with many rubros without fetching all processes in a date window.

## License

Private — all rights reserved.
