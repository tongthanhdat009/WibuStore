<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-10-18 | Updated: 2026-10-18 -->

# infra

## Purpose
Optional local services. Postgres is external shared container; API + frontends run host-native via `Makefile`.

## Key Files
| File | Description |
|------|-------------|
| `compose.yaml` | Minio (S3-compat :9000/:9001) + Redis 7 (profile `redis`) |

## For AI Agents

### Working In This Directory
- `docker compose -f infra/compose.yaml up -d` (+ `--profile redis` when needed).
- MVP uses DB queue/cache driver; set `CACHE_STORE`/`QUEUE_CONNECTION=redis` only when scaling.
- Never move Postgres into this compose — shared container is source of truth.

<!-- MANUAL: -->
