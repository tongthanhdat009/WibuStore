<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-10-18 | Updated: 2026-10-18 -->

# backend

## Purpose
Laravel 13 REST API (`/api/v1`). Sanctum stateful session auth, per-permission admin (`perm:xxx`), integer VND money, `{error:{code,message}}` contract. Runs via `wibu-php:8.3` docker, Postgres shared.

## Key Files
| File | Description |
|------|-------------|
| `routes/api.php` | All routes: public catalog, cart/checkout, admin group |
| `composer.json` | Laravel 13 + Sanctum + S3 flysystem, PHP 8.3 |
| `docs/api/openapi.yaml` | API contract |
| `.env.example` | Env template (`.env` gitignored) |

## Subdirectories
| Directory | Purpose |
|-----------|---------|
| `app/` | Domain services, controllers, models (see `app/AGENTS.md`) |
| `config/` | 12 Laravel configs (db, sanctum, cors, queue...) |
| `database/` | 10 migrations + 4 seeders (see `database/AGENTS.md`) |
| `routes/` | `api.php`, `web.php`, `console.php` |
| `tests/` | Feature (Catalog/Checkout/Payment/Preorder/Rbac) + Unit |

## For AI Agents

### Working In This Directory
- Never run `php` locally; use root `make test/pint/queue/schedule/migrate/seed`.
- Throttle on login/checkout/lookup; webhooks skip CSRF (gateway verifies).
- Guest cart via `X-Guest-Token`; checkout idempotent via `IdempotencyKey`.

### Testing Requirements
- `make test` (db `wibu_store_test`); `make pint` style gate.

### Common Patterns
- Fat domain services (`app/Domain`), thin controllers; FormRequests validate; Resources shape JSON.

## Dependencies

### Internal
- Postgres (`pg_trgm`, `uuid-ossp`); optional Minio S3, Redis queue/cache.

### External
- `laravel/framework:13`, `sanctum:4`, `flysystem-aws-s3`

<!-- MANUAL: -->
