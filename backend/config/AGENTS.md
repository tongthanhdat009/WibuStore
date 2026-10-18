<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-10-18 | Updated: 2026-10-18 -->

# config

## Purpose
12 stock Laravel configs. Touched ones: `database.php` (pgsql), `sanctum.php` (stateful), `cors.php` (Origin+X-XSRF-TOKEN), `queue.php`/`cache.php` (DB drivers, redis optional), `filesystems.php` (S3/Minio).

## For AI Agents
- Env-driven; never hardcode. Redis/Minio changes need `infra/compose.yaml` in sync.

<!-- MANUAL: -->
