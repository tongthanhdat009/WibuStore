<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-10-18 | Updated: 2026-10-22 -->

# routes

## Purpose
Route definitions. `api.php` = whole API (93 registrations); `web.php`/`console.php` are minimal.

## Key Files
| File | Description |
|------|-------------|
| `api.php` | Entire `/api/v1` surface: public catalog, `/auth/*`, cart/checkout, webhooks, customer group, admin group |
| `web.php` | One route: `GET /` → `view('welcome')` |
| `console.php` | `inspire` command + scheduler: `expire-unpaid` (every 5 min), `purge-carts` (daily), `purge-idempotency` (daily) |

## For AI Agents

### Working In This Directory
- Structure of `api.php`:
  - Top-level `GET /api/login` returns a JSON 401 (`UNAUTHENTICATED`) so unauthenticated requests never get an HTML redirect.
  - `Route::prefix('v1')` group → everything below is `/api/v1/...`.
  - Public reads: health, products (+ slug, reviews, reviews eligibility/store), categories, anime-series, manufacturers, preorders, pages/home, collections.
  - `/auth/*`: register, login (`throttle:10,1`), forgot-password (`5,1`), reset-password (`5,1`), logout (`auth:sanctum`).
  - Cart/checkout: `/cart`, `/cart/items`, `/cart/items/{id}`, `/checkout/preview`, `POST /orders` (`throttle:20,1`), `/orders/{publicId}`, `/orders/{publicId}/invoice`, `/orders/{publicId}/cancel`, `/orders/lookup` (`throttle:10,1`).
  - `POST /webhooks/payments/{provider}` — outside any auth group; CSRF is exempted for `api/v1/webhooks/*` in `bootstrap/app.php`.
  - Customer group: `Route::middleware('auth:sanctum')` → `/me`, `/me/orders`, `/me/addresses`, `/wishlist`.
  - Admin group: `Route::prefix('admin')->middleware('auth:sanctum')` plus a per-route `perm:<slug>` middleware — permissions in use: `products.read`, `products.write`, `products.publish`, `inventory.adjust`, `orders.transition`, `payments.confirm`, `preorders.manage`, `cms.publish`, `users.manage`, `reports.read`, `settings.manage`.
- The `perm` alias is registered in `bootstrap/app.php` → `App\Http\Middleware\RequirePermission`. A route without `perm:` is reachable by any authenticated user.
- Put a new route in the correct group. Public vs customer vs admin is a security decision, not a convenience.
- Keep throttles on auth, checkout and lookup endpoints; they are the abuse surface.
- `Route::match(['get','post'], ...)` is used where one controller action serves both verbs (categories, anime-series, manufacturers, preorder-campaigns, media, pages, coupons, settings).
- `console.php` schedules are idempotent by design (`expireUnpaid`, delete expired carts, delete expired idempotency keys) — keep them safe to run repeatedly.

### Testing Requirements
- New endpoint → Feature test covering the happy path plus 401 (guest) and 403 (wrong permission) where applicable. `RbacTest` is the template for admin gates.
- Run `make test` from the repo root.

### Common Patterns
- Controllers are thin HTTP glue; business rules live in `app/Domain` services. Routes only bind URL → controller → middleware.

## Dependencies

### Internal
- `bootstrap/app.php` — middleware stack, `perm` alias, CSRF exemption for webhooks.
- `app/Http/Controllers/Api/V1/*` — controllers referenced by the route files.
- `database/seeders/RolePermissionSeeder.php` — creates the `perm:` slugs used here.

### External
- `laravel/framework:13` routing, `laravel/sanctum:4` stateful guard.

<!-- MANUAL: -->
