<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-10-18 | Updated: 2026-10-22 -->

# app

## Purpose
Application code split by layer: fat domain services (`Domain/`), HTTP glue (`Http/`), Eloquent models (`Models/`), queued mail (`Jobs/`), service providers (`Providers/`). 32 models, 9 controllers, 2 middleware, 2 form requests, 3 resources, 1 job.

## Subdirectories
| Directory | Purpose |
|-----------|---------|
| `Domain/` | Business logic: Orders, Inventory, Payments, Preorders, Odoo, Common (see `Domain/AGENTS.md`) |
| `Http/` | Controllers, middleware, form requests, API resources (see `Http/AGENTS.md`) |
| `Models/` | 32 Eloquent models (see `Models/AGENTS.md`) |
| `Jobs/` | Queued order-confirmation mail (see `Jobs/AGENTS.md`) |
| `Providers/` | `AppServiceProvider` only (see `Providers/AGENTS.md`) |

## For AI Agents

### Working In This Directory
- New business rule → a service in `Domain/`; controllers only validate, authorise, delegate, serialise.
- Money = integer VND everywhere. Cast with `(int)` at the boundary; never floats or decimals.
- Error body is always `{error:{code,message}}` for hand-raised errors; domain services `abort()` with `response()->json(['error' => ['code' => 'X', 'message' => '...']], $status)`.
- Every admin mutation records `ActivityLog::record(action, subjectType, subjectId, before, after, actorId)`.
- `Models/` holds relations/casts/scopes and small helpers only; workflows that span models belong in `Domain/`.
- App wiring lives in `bootstrap/app.php` (routing, middleware, exception renderers) — not in `Providers/`.

### Testing Requirements
- `make test` from the repo root (phpunit, DB `wibu_store_test`); `make pint` is the style gate. Invoke PHP only through the root `make` targets, not `php artisan` directly. The runtime is host PHP (8.3+), not Docker — see `backend/AGENTS.md`.
- Layer suites: `Feature/CatalogTest.php`, `Feature/CheckoutTest.php`, `Feature/PaymentTest.php`, `Feature/PreorderTest.php`, `Feature/RbacTest.php`.

### Common Patterns
- Layering: `Domain/` service ← `Http/Controllers` → `Http/Resources`. Controllers must not hold business rules.
- Error codes in use: 401 `UNAUTHENTICATED`, 403 `FORBIDDEN`, 404 `NOT_FOUND`, 409 `INVALID_STATE`, 422 `VALIDATION_ERROR`.
- Postgres-only SQL appears in places (`ilike`, `date_trunc`) — the app is Postgres-only, not portable to MySQL/SQLite.

## Dependencies

### Internal
- `App\Domain\*` services, `App\Http\*` controllers/middleware/requests/resources, `App\Models\*`.

### External
- `laravel/framework:13`, `laravel/sanctum:4`, `barryvdh/laravel-dompdf`, `league/flysystem-aws-s3-v3`

<!-- MANUAL: -->
