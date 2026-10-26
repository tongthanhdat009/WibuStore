<!-- Generated: 2026-10-18 | Updated: 2026-10-22 -->

# WibuStore

## Purpose
Mono-repo anime figure/collectibles e-commerce: Astro storefront (`frontend/`, :4321) + Astro admin (`frontend-admin/`, :4322) + Laravel 13 REST API (`backend/`, :8000, PostgreSQL). Single specs per part: `Wibu_Store_*.md` at root.

## Key Files
| File | Description |
|------|-------------|
| `Makefile` | Only orchestration: doctor/db-create/fresh/dev/test/pint/queue/schedule |
| `README.md` | Quickstart (3 services, demo login `admin@wibustore.vn`/`Admin@123`) |
| `Wibu_Store_Laravel_PostgreSQL_Backend_Architecture_Spec.md` | Backend contract source of truth |
| `Wibu_Store_Anime_Pop_Comic_Light_Theme_Spec.md` | Storefront theme spec |
| `Wibu_Store_Admin_Astro_Tailwind_Design_Spec.md` | Admin design spec |
| `.gitignore` | Ignores `backend/.env`, `node_modules`, `dist`, `/.playwright-mcp`, `/.freebuff` |

## Subdirectories
| Directory | Purpose |
|-----------|---------|
| `backend/` | Laravel 13 API, `/api/v1`, Sanctum session auth (see `backend/AGENTS.md`) |
| `frontend/` | Public Astro storefront, API-first + static fallback (see `frontend/AGENTS.md`) |
| `frontend-admin/` | Admin Astro SPA, SSR-rendered against `/api/v1/admin` (see `frontend-admin/AGENTS.md`) |
| `infra/` | Optional Minio/Redis compose (see `infra/AGENTS.md`) |

## For AI Agents

### Working In This Directory
- `make` from root only; never run `php artisan`/`pnpm` directly except inside foreground `make dev-*`.
- Backend runs on **host PHP** (8.3+; local is 8.5.8) — `make install` fetches `pdo_pgsql`/`pgsql` as a Fedora RPM into `~/.local/lib/php-ext`, no sudo. The Makefile has no Docker targets; `backend/Dockerfile.dev` is a leftover from the docker-era setup. `README.md` says the same.
- DB is shared external Postgres on `127.0.0.1:5432` (user `app`, db `wibu_store` / `wibu_store_test`). It is NOT in `infra/compose.yaml`.
- Money = integer VND everywhere; error contract `{error:{code,message}}`.
- Don't commit secrets; `backend/.env` gitignored.
- Odoo integration is one-way: backend → Odoo connector (`ODOO_CONNECTOR_URL`, default `http://localhost:8090`) via `Domain/Odoo/OdooHookService`, signed with `ODOO_WEBHOOK_SECRET`. Fire-and-forget — failures are logged, never thrown.

### Testing Requirements
- `make test` (phpunit, `wibu_store_test`, 19 tests) before backend changes. `make pint` for style.
- Frontend: `pnpm build` in respective dir; storefront build needs API up for full prerender.

### Common Patterns
- API-first with static fallback in storefront (`frontend/src/lib/catalog.ts`); admin is SSR-only, no mock layer on pages (`frontend-admin/src/lib/api.ts`).
- Admin SPA forwards the browser cookie + `Origin`/`Referer` on every SSR fetch — that is what makes Sanctum session auth work server-side. Dropping those headers silently turns every page into a 401.

## Dependencies

### Internal
- `frontend/` → `backend/` `/api/v1` via `PUBLIC_API_URL` (default `http://localhost:8000`)
- `frontend-admin/` → `backend/` `/api/v1/admin` via `PUBLIC_API_URL` (Sanctum + `perm:*`)
- `backend/` → Odoo connector `/ingest/wibu` (optional, config-gated)

### External
- Postgres + `pg_trgm`/`uuid-ossp` (external, shared), Node 20+pnpm, tmux, PHP 8.3+ with `pdo_pgsql`
- Docker only for the optional `infra/compose.yaml` services (Minio S3-compat, Redis 7)

<!-- MANUAL: -->
