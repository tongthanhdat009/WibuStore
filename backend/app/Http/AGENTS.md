<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-10-18 | Updated: 2026-10-22 -->

# Http

## Purpose
HTTP layer: versioned API controllers, JSON enforcement, permission gates, input validation and JSON shaping. No business rules live here.

## Subdirectories
| Directory | Purpose |
|-----------|---------|
| `Controllers/` | Abstract base + versioned API tree (see `Controllers/AGENTS.md`) |
| `Middleware/` | `ForceJson`, `RequirePermission` (`perm:*`) (see `Middleware/AGENTS.md`) |
| `Requests/` | FormRequest validation (see `Requests/AGENTS.md`) |
| `Resources/` | JsonResource response shaping (see `Resources/AGENTS.md`) |

## For AI Agents

### Working In This Directory
- Never return HTML from the API. `ForceJson` is appended to the whole `api` group in `bootstrap/app.php`, and `routes/api.php` defines a `/login` fallback that returns a JSON 401 so unauthenticated requests never get an HTML redirect.
- Admin endpoints need `perm:<slug>` declared in `routes/api.php`; the alias `perm` → `RequirePermission` is registered in `bootstrap/app.php`.
- Validation goes in `Http/Requests` (FormRequest) or inline `$request->validate()`. Responses are shaped by `Http/Resources` or an explicit `response()->json(['data' => ...])`.
- Success envelope: `['data' => ...]`, or `['data' => ..., 'meta' => ...]` for paginated hand-rolled responses. Failure envelope: `['error' => ['code' => ..., 'message' => ...]]`.
- Money = integer VND in this layer too — never format a price as a float.
- Gotcha: Laravel validation failures return the framework's default 422 body (`message` + `errors`), not the repo's `{error:{code,message}}` envelope. Only hand-raised errors follow the repo contract.

### Testing Requirements
- `make test` from the repo root (DB `wibu_store_test`); `make pint` is the style gate. Invoke PHP only through the root `make` targets, not `php artisan` directly. The runtime is host PHP (8.3+), not Docker — see `backend/AGENTS.md`.
- New endpoint → Feature test covering the happy path plus the 401/403 path.

### Common Patterns
- Controllers delegate to `App\Domain\*` services; a controller that computes totals or writes raw SQL is in the wrong layer.
- Guest requests use the `X-Guest-Token` header; guest order reads use `X-Order-Token`.

## Dependencies

### Internal
- `App\Domain\*` services, `App\Models\*`, `App\Http\Requests\*`, `App\Http\Resources\*`.

### External
- `laravel/framework:13`, `laravel/sanctum:4`, `barryvdh/laravel-dompdf`

<!-- MANUAL: -->
