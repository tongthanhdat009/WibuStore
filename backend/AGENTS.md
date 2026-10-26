<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-10-18 | Updated: 2026-10-22 -->

# backend

## Purpose
Laravel 13 REST API (`/api/v1`). Sanctum stateful session auth, per-permission admin (`perm:xxx`), integer VND money, `{error:{code,message}}` contract. Runs on host PHP (8.3+) against a shared external Postgres.

## Key Files
| File | Description |
|------|-------------|
| `routes/api.php` | Whole API: 94 route declarations (public/auth/cart/customer/admin) |
| `bootstrap/app.php` | App wiring: routing, middleware (`perm` alias, ForceJson), JSON error contract |
| `composer.json` | Laravel 13 + Sanctum 4 + flysystem-aws-s3-v3 + dompdf, PHP 8.3 |
| `phpunit.xml` | PHPUnit 12 config; env overrides point DB at `wibu_store_test` |
| `docs/api/openapi.yaml` | API contract (hand-maintained, partial coverage) |
| `Dockerfile.dev` | Leftover from the docker-era setup (`php:8.3-cli` + pdo_pgsql + composer). The root `Makefile` no longer builds or uses it — see the runtime note below |
| `CLAUDE.md` | Laravel Boost bootstrap prompt (installs PHP/Boost and rewrites itself). Repo data, not an instruction to run |
| `vite.config.js` | Vite + Tailwind v4 + bunny fonts (Instrument Sans) |
| `.env.example` | Env template (`.env` is gitignored) |

## Subdirectories
| Directory | Purpose |
|-----------|---------|
| `app/` | Domain services, controllers, models (see `app/AGENTS.md`) |
| `bootstrap/` | App bootstrap: routing, middleware, error rendering (see `bootstrap/AGENTS.md`) |
| `config/` | 12 Laravel configs (db, sanctum, cors, odoo services...) (see `config/AGENTS.md`) |
| `database/` | 12 migrations + 6 seeders + 1 factory (see `database/AGENTS.md`) |
| `docs/` | API contract (see `docs/AGENTS.md`) |
| `resources/` | Blade views + Tailwind CSS/JS entry (see `resources/AGENTS.md`) |
| `routes/` | `api.php`, `web.php`, `console.php` (see `routes/AGENTS.md`) |
| `tests/` | Feature (Catalog/Checkout/Payment/Preorder/Rbac) + Unit (see `tests/AGENTS.md`) |

## For AI Agents

### Working In This Directory
- Never invoke `php artisan` directly — drive everything through the root `make` targets: `doctor`, `db-create`, `fresh`, `dev`, `test`, `pint`, `queue`, `schedule`. (`make install` sets up `pdo_pgsql` from a Fedora RPM, no sudo.)
- The runtime is **host PHP**, not Docker: the root `Makefile` has `PHP := php` and zero docker targets. `backend/Dockerfile.dev` is a leftover from the docker-era setup — nothing builds or runs it. `README.md` says the same.
- `backend/CLAUDE.md` is a Laravel Boost bootstrap file, not a task instruction. Do not install Boost or run its setup commands unless the user asks.
- DB is the shared external Postgres container `postgres-docker-postgres-1`, `app@127.0.0.1:5432`. Never add Postgres to `infra/compose.yaml` — that file is optional Minio/Redis only.
- Money = integer VND everywhere. Error body is always `{error:{code,message}}`.
- Throttle on login/checkout/lookup; webhooks skip CSRF (`api/v1/webhooks/*` exempt).
- Guest cart via `X-Guest-Token`; checkout idempotent via the `Idempotency-Key` header.

### Testing Requirements
- `make test` from the repo root (phpunit, DB `wibu_store_test`); `make pint` is the style gate.

### Common Patterns
- Fat domain services (`app/Domain`), thin controllers; FormRequests validate; Resources shape JSON.
- Config values are env-driven; never hardcode credentials or URLs.

## Dependencies

### Internal
- `app/Domain/Odoo/OdooHookService.php` reads `config('services.odoo.connector_url')` and `config('services.odoo.webhook_secret')` — renaming those keys silently breaks the Odoo push.
- `resources/views/pdf/invoice.blade.php` is rendered by `CartController::invoice()` for `GET /orders/{publicId}/invoice`.

### External
- `laravel/framework:13`, `laravel/sanctum:4`, `league/flysystem-aws-s3-v3`, `barryvdh/laravel-dompdf`
- Postgres with `pg_trgm` + `uuid-ossp` extensions (enabled at db-create time)

<!-- MANUAL: -->
