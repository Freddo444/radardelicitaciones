# SECP Monitor

Procurement monitoring application for the Dominican Republic DGCP (Dirección General de Contrataciones Públicas). Polls the public DGCP API, matches new procurement processes against a configurable UNSPSC rubro watchlist, and delivers instant notifications via Email and Telegram.

## What it does

- **Polls the DGCP API** on a configurable interval (default: hourly) for new procurement processes
- **Matches by UNSPSC code** at familia, clase, or subclase level — watching a familia automatically catches all classes and subclasses beneath it
- **Notifies instantly** via Gmail SMTP and/or Telegram bot when a match is found
- **Filters noise** — skip processes below/above an amount threshold, exclude modalities you don't care about, or ignore processes whose tender deadline has already passed
- **Auto-cleans** expired and closed processes from the feed
- **Local UNSPSC catalog** — 18,000+ codes stored locally for instant code lookup when adding rubros

## Stack

| Layer | Technology |
|---|---|
| Backend | Laravel 12 (PHP 8.3) |
| Database | MySQL |
| Frontend | Blade + Tailwind CSS v4 |
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
