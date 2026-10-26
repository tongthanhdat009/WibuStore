<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-10-22 | Updated: 2026-10-22 -->

# docs

## Purpose
Backend documentation. Holds the API contract only — no prose docs, no ADRs.

## Key Files
| File | Description |
|------|-------------|
| `api/openapi.yaml` | OpenAPI 3.1 contract for the public API (see `api/AGENTS.md`) |

## Subdirectories
| Directory | Purpose |
|-----------|---------|
| `api/` | OpenAPI specification of `/api/v1` (see `api/AGENTS.md`) |

## For AI Agents

### Working In This Directory
- The root `Wibu_Store_Laravel_PostgreSQL_Backend_Architecture_Spec.md` is the prose source of truth for the backend; `docs/api/openapi.yaml` is the machine-readable contract.
- `openapi.yaml` is hand-maintained — it is not generated from the routes, so it drifts. Update it in the same change as any endpoint you add or change.
- The contract is partial: it covers the public/customer surface plus a few admin endpoints, not the full admin API. Do not read "absent from the spec" as "endpoint does not exist" — check `routes/api.php`.
- Money in the spec is integer VND (`MoneyVND` schema). The error body is `{error:{code,message}}` with the shared `Error` schema.

### Testing Requirements
- No tests run against this directory. Validate the YAML parses (e.g. any OpenAPI linter) after editing.

### Common Patterns
- One document per concern; add new docs as files, not as sections of an unrelated file.

## Dependencies

### Internal
- `routes/api.php` — the implementation the contract describes.
- `backend/AGENTS.md` — project-level conventions.

### External
- OpenAPI 3.1 tooling (linters, codegen consumers in `frontend/`).

<!-- MANUAL: -->
