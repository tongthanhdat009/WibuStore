<!-- Generated: 2026-10-18 | Updated: 2026-10-18 -->

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

## Subdirectories
| Directory | Purpose |
|-----------|---------|
| `backend/` | Laravel 13 API, `/api/v1`, Sanctum session auth (see `backend/AGENTS.md`) |
| `frontend/` | Public Astro storefront, API-first + static fallback (see `frontend/AGENTS.md`) |
| `frontend-admin/` | Admin Astro SPA, mock-data UI pending API wiring (see `frontend-admin/AGENTS.md`) |
| `infra/` | Optional Minio/Redis compose (see `infra/AGENTS.md`) |

## For AI Agents

### Working In This Directory
- `make` from root only; never run `php artisan`/`pnpm` directly except inside foreground `make dev-*`.
- Backend runs via docker image `wibu-php:8.3`, no local PHP needed. DB is shared external Postgres (`postgres-docker-postgres-1`, `app@127.0.0.1:5432`).
- Money = integer VND everywhere; error contract `{error:{code,message}}`.
- Don't commit secrets; `backend/.env` gitignored.

### Testing Requirements
- `make test` (phpunit, `wibu_store_test`, 6 files) before backend changes. `make pint` for style.
- Frontend: `pnpm build` in respective dir; storefront build needs API up for full prerender.

### Common Patterns
- API-first with static fallback in storefront (`frontend/src/lib/catalog.ts`); admin currently mock-first (`frontend-admin/src/data/mockData.ts`).

## Dependencies

### Internal
- `frontend/` → `backend/` `/api/v1` via `PUBLIC_API_URL` (default `http://localhost:8000`)
- `frontend-admin/` → `backend/` `/api/v1/admin` (planned, currently mocked)

### External
- Postgres + `pg_trgm`/`uuid-ossp`, Node 20+pnpm, tmux, Docker

<!-- MANUAL: -->
