<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-10-18 | Updated: 2026-10-22 -->

# V1

## Purpose
Versioned API surface (`/api/v1`): customer auth and account, cart + checkout + orders, and the admin tree. Anonymous storefront reads live under `Public/`.

## Key Files
| File | Description |
|------|-------------|
| `AuthController.php` | register/login/logout, me/updateMe, forgot/reset password, addresses, wishlist |
| `CartController.php` | Cart CRUD, checkout preview + checkout, order show/invoice/cancel/lookup, my orders |

## Subdirectories
| Directory | Purpose |
|-----------|---------|
| `Admin/` | Back-office endpoints, every route gated by `perm:*` (see `Admin/AGENTS.md`) |
| `Public/` | Anonymous catalog reads and reviews (see `Public/AGENTS.md`) |

## For AI Agents

### Working In This Directory
- `AuthController::register` creates the user, attaches the `customer` role if that role row exists, then `Auth::login()` + `session()->regenerate()`. Auth is session-based (Sanctum stateful), not token-based.
- `CartController::currentCart()` is the single cart resolver: signed-in user → `user_id` cart, otherwise `sha256(X-Guest-Token)` → `guest_token_hash` cart. A guest without the header gets 422 `VALIDATION_ERROR`.
- Cart types: `regular` and `preorder` are separate carts (`cart_type` column); `addItem` picks the type from the payload, `show` from the `cart_type` query param.
- `checkout` reads the `Idempotency-Key` header and falls back to a random hash — a client that omits the header gets no idempotency. Always send the header.
- Guest order reads (`showOrder`, `invoice`) require `X-Order-Token` matching `orders.lookup_token_hash`; the signed-in owner bypasses it. `lookup` matches on `order_number` + `email` and returns only `public_id` + statuses.
- `invoice()` renders `resources/views/pdf/invoice.blade.php` through dompdf and downloads a PDF — it is the only non-JSON response under `/api/v1`.
- `AuthController::wishlist`/`storeWishlist`/`destroyWishlist` use the `wishlists` table through raw `DB::table(...)` — there is no `Wishlist` model.
- `AuthController::ensureHash()` was dead code and has been deleted.
- Rate limits live in `routes/api.php`, not in the controllers: login 10/min, forgot/reset password 5/min, checkout 20/min, order lookup 10/min.

### Testing Requirements
- `make test` from the repo root (DB `wibu_store_test`); `make pint` is the style gate. Invoke PHP only through the root `make` targets, not `php artisan` directly. The runtime is host PHP (8.3+), not Docker — see `backend/AGENTS.md`.
- Relevant suites: `Feature/CheckoutTest.php` (cart → order, stock reserve, idempotency, oversell), `Feature/PaymentTest.php`, `Feature/RbacTest.php` (guest lookup leaks nothing).

### Common Patterns
- Customer endpoints live in the `auth:sanctum` group in `routes/api.php` and read the current user via `$request->user()`.
- The `profile()` helper is the single shape for the current user: `id` (public_id), `name`, `email`, role slugs, permission slugs.

## Dependencies

### Internal
- `App\Domain\Orders\CheckoutService`, `App\Domain\Orders\OrderService`, `App\Http\Requests\StoreCheckoutRequest`, `App\Http\Resources\OrderResource`, `App\Models\*`.

### External
- `laravel/sanctum:4`, `barryvdh/laravel-dompdf`

<!-- MANUAL: -->
