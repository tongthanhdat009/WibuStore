<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-10-22 | Updated: 2026-10-22 -->

# infra

## Purpose
Optional local services for Wibu Store. The API and both Astro frontends run host-native via the root `Makefile`; Postgres is not managed here.

## Key Files
| File | Description |
|------|-------------|
| `compose.yaml` | Compose project `wibu-store`. `minio` (S3-compatible, `:9000` API / `:9001` console, volume `minio-data`) plus `redis` 7-alpine on `:6379` behind the `redis` profile |

## For AI Agents

### Working In This Directory
- Postgres is NOT in this compose file. It is a shared external container (`postgres-docker-postgres-1`) on `app@127.0.0.1:5432`, databases `wibu_store` / `wibu_store_test`. That is the source of truth — never move it in here.
- `docker compose -f infra/compose.yaml up -d` starts Minio only. Add `--profile redis` to start Redis.
- Redis sits behind a profile because the MVP uses the database driver for cache and queue. Set `CACHE_STORE` / `QUEUE_CONNECTION=redis` in `backend/.env` only when the profile is actually running.
- Minio credentials in the compose file are local dev placeholders (`local_minio_access_key` / `local_minio_secret_key`). Do not reuse them anywhere else and do not promote them to production defaults.
- The compose project name is `wibu-store`; use `-f infra/compose.yaml` from the repo root so relative paths resolve the same way as the `Makefile`.
- The backend talks to Minio through the Laravel `s3` disk in `backend/config/filesystems.php` (`AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_BUCKET`, `AWS_ENDPOINT`, `AWS_USE_PATH_STYLE_ENDPOINT`, and `FILESYSTEM_DISK` to select the disk).

### Testing Requirements
- `docker compose -f infra/compose.yaml up -d` then `docker compose -f infra/compose.yaml ps` to confirm Minio is healthy (it ships a `mc ready` healthcheck).
- `make status` from the repo root checks the three dev services; it does not cover Minio or Redis.

### Common Patterns
- Start Minio before exercising file upload or media features; the admin media pages talk to the backend's S3 disk.
- Leave Redis down until a queue/cache driver change is actually being tested — it is off by default on purpose.

## Dependencies

### Internal
- `backend/` — `s3` disk for media (`backend/config/filesystems.php`), optional Redis cache/queue driver
- `frontend-admin/` — media library pages upload through the backend

### External
- Docker + Compose v2, `minio/minio:latest` image, `redis:7-alpine` image
- Shared external Postgres container on `127.0.0.1:5432`

<!-- MANUAL: -->
