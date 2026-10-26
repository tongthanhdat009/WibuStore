<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-10-22 | Updated: 2026-10-22 -->

# Api

## Purpose
Container for the versioned API tree. No files of its own — every controller lives under a version prefix so that `/api/v2` can be added beside `v1` without touching existing routes.

## Subdirectories
| Directory | Purpose |
|-----------|---------|
| `V1/` | The only live API version: auth, cart, admin and public controllers (see `V1/AGENTS.md`) |

## For AI Agents

### Working In This Directory
- Keep files directly in `Api/` out of it — a controller that is not under a version prefix has no route namespace to live in.
- When adding a version, copy the `V1/` layout rather than branching inside existing controllers; `routes/api.php` owns the version prefix (`Route::prefix('v1')`).

### Testing Requirements
- `make test` from the repo root (DB `wibu_store_test`); `make pint` is the style gate.

## Dependencies

### Internal
- `routes/api.php` — declares the `v1` prefix group and every route that reaches these controllers.

### External
- `laravel/framework:13`

<!-- MANUAL: -->
