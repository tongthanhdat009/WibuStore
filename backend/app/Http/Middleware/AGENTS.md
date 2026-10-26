<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-10-22 | Updated: 2026-10-22 -->

# Middleware

## Purpose
Two API middleware: force JSON on every API request, and enforce per-permission access on admin routes.

## Key Files
| File | Description |
|------|-------------|
| `ForceJson.php` | Sets `Accept: application/json` on the request so Laravel answers with JSON instead of HTML |
| `RequirePermission.php` | `perm:<slug>` guard — 401 when unauthenticated, 403 when the permission is missing |

## For AI Agents

### Working In This Directory
- Both are wired in `bootstrap/app.php`: `ForceJson` is appended to the whole `api` middleware group, `RequirePermission` is registered under the alias `perm`.
- `ForceJson` only rewrites the `Accept` header. The `{error:{code,message}}` body shape comes from the exception renderers in `bootstrap/app.php`, not from this middleware.
- `RequirePermission` checks `User::hasPermission($slug)`; a `super_admin` role short-circuits to true, otherwise the user needs the permission through one of their roles.
- The middleware returns its own JSON 401/403 responses — it never throws, so it bypasses the global exception renderers.
- Because `perm` is an alias, the permission slug itself lives in `routes/api.php`, not in this class. Add new slugs to the route, never to the middleware.
- `RequirePermission` returns 401 for a missing user even though admin routes already run `auth:sanctum` first — it is a fallback, not the primary gate.

### Testing Requirements
- `make test` from the repo root (DB `wibu_store_test`); `make pint` is the style gate. Invoke PHP only through the root `make` targets, not `php artisan` directly. The runtime is host PHP (8.3+), not Docker — see `backend/AGENTS.md`.
- `Feature/RbacTest.php` asserts admin routes require auth and the matching permission.

### Common Patterns
- Error body is always `{error:{code,message}}` — `UNAUTHENTICATED` (401), `FORBIDDEN` (403).
- Admin route declaration pattern: `auth:sanctum` on the group plus `perm:<slug>` per route.

## Dependencies

### Internal
- `App\Models\User` (`hasPermission()`).

### External
- `laravel/framework:13`

<!-- MANUAL: -->
