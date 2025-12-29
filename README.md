## Kampus Room Booking

A Laravel 12 + Tailwind CSS web app that lets mahasiswa browse available rooms, submit booking requests, and track approvals while admins manage room inventory, approve or reject bookings, and review a calendar-style dashboard.

## Features

- **Role-based access** via Breeze auth + `RoleMiddleware` (`admin`, `mahasiswa`).
- **Mahasiswa portal**: room catalog, detail view, booking form, and booking history.
- **Admin console**: calendar dashboard with occupancy stats, CRUD for rooms, and booking approval flow.
- **Notifications** alert both parties when bookings are created or change state.
- **Responsive UI**: Blade templates, Tailwind CSS, and Alpine.js for interactivity.

Full route breakdown lives in `ROUTING.md`, and higher-level architecture notes live in `design.md`.

## Tech Stack

- Laravel 12, PHP 8.2, MySQL (or SQLite for local dev)
- Laravel Breeze authentication scaffolding
- Tailwind CSS, Vite, Alpine.js
- Notifications + queues ready (see `composer.json` `dev` script for queue listener)

## Requirements

| Tool | Version |
| --- | --- |
| PHP | 8.2+ with required Laravel extensions |
| Composer | 2.x |
| Node.js | 18+ |
| npm | 9+ |
| Database | MySQL 8 / MariaDB 10.5+ (or SQLite for quick start) |

## Getting Started

```bash
git clone <repo-url>
cd room-booking-1
composer install
cp .env.example .env   # update DB_* + MAIL_* creds
php artisan key:generate
npm install
```

### Database

1. Create a database and update `.env`.
2. Run migrations and seed default data:

```bash
php artisan migrate --seed
```

Seeded accounts:

| Role | Email | Password |
| --- | --- | --- |
| Admin | `admin@example.com` | `password` |
| Mahasiswa | `mahasiswa@example.com` | `password` |

Rooms sample data is inserted via `RoomSeeder`.

### Local Development

Use the bundled Composer script to run the HTTP server, queue worker, log tail, and Vite watcher together:

```bash
composer run dev
```

Or run them manually in separate terminals:

```bash
php artisan serve
php artisan queue:listen
npm run dev
```

For a production asset build:

```bash
npm run build
```

## Testing

Run the full test suite with:

```bash
composer run test
```

Add feature tests under `tests/Feature` to cover new booking flows, middleware, or admin behaviors.

## Project Docs

- `design.md` – architecture, user roles, and UX outline.
- `ROUTING.md` – every HTTP endpoint, middleware, and controller mapping.

Keep these docs updated whenever you change routes or high-level flows.

## Deployment Notes

1. Set `APP_ENV=production`, `APP_DEBUG=false`, and configure cache/mail drivers.
2. Run `php artisan migrate --force`.
3. Build assets: `npm run build`.
4. Configure a queue worker (`php artisan queue:work`) so notifications send instantly.

### Logs & Monitoring (Kubernetes + Loki)

In Kubernetes, prefer writing structured JSON logs to `stderr` so log collectors (Promtail) can scrape them and forward to Grafana Loki.

- Set `LOG_CHANNEL=loki` in your deployment environment (or use `stdout` if you prefer stdout).
- Promtail should be configured to tail container logs (e.g., `/var/log/containers/*room-booking-*.log`) and parse JSON lines. See `design.md` for an example Promtail `scrape_config` snippet.

Example:

```bash
# In your deployment manifest set:
# env:
#   - name: LOG_CHANNEL
#     value: "loki"
```

## License

This project inherits the Laravel MIT license. See `LICENSE` for details.
