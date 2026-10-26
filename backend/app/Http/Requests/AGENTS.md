<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-10-22 | Updated: 2026-10-22 -->

# Requests

## Purpose
FormRequest classes that validate and normalise request payloads before a controller action runs.

## Key Files
| File | Description |
|------|-------------|
| `StoreCheckoutRequest.php` | Rules for `POST /orders` — items, customer block, payment method, coupon, addresses |
| `StoreProductRequest.php` | Rules for `POST /admin/products` — product fields, category ids, variant rows |

## For AI Agents

### Working In This Directory
- Both classes override only `rules()`. There is no `authorize()` override, so Laravel's default applies — add `authorize()` only when a route needs a per-object check.
- `$request->validated()` returns only the validated keys; `CartController::checkout` passes it straight to `CheckoutService`.
- `StoreCheckoutRequest` requires `customer.name|email|phone`, `payment_method` in `cod|bank_transfer`, and at least one item with `variant_id` + `quantity` (1..99). `addresses` is optional, but each entry needs `type` (`shipping|billing`), `recipient_name`, `phone` and `line1`.
- `StoreProductRequest` allows `status` in `draft|active|archived`. `slug` is optional — `AdminCatalogController::storeProduct` generates one from the name when absent.
- Money rules are `integer` with `min:0` — never `numeric` or `decimal`.
- Gotcha: a Laravel validation failure returns the framework's default 422 body (`message` + `errors`), not the repo's `{error:{code,message}}` envelope. `bootstrap/app.php` only sets JSON content negotiation; it does not reshape validation errors.
- Several other endpoints validate inline with `$request->validate(...)` instead of using a FormRequest. That is the existing pattern — do not migrate them piecemeal without a reason.

### Testing Requirements
- `make test` from the repo root (DB `wibu_store_test`); `make pint` is the style gate. Invoke PHP only through the root `make` targets, not `php artisan` directly. The runtime is host PHP (8.3+), not Docker — see `backend/AGENTS.md`.
- `Feature/CheckoutTest.php` exercises the checkout request rules end to end.

### Common Patterns
- Nested arrays use wildcard rules: `items.*.field`, `variants.*.field`.
- Keep the rules next to the single endpoint that uses them; a shared base FormRequest is not warranted at two call sites.

## Dependencies

### Internal
- None — the rules reference table names and Laravel validation primitives only.

### External
- `laravel/framework:13`

<!-- MANUAL: -->
