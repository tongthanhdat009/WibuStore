<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-10-22 | Updated: 2026-10-22 -->

# Orders

## Purpose
Checkout (cart → priced order) and the order lifecycle: status transitions, cancellation, and the unpaid-order expiry sweep.

## Key Files
| File | Description |
|------|-------------|
| `CheckoutService.php` | `preview()` / `checkout()` — pricing, coupon, stock reservation, order + payment creation, order-confirmation job |
| `OrderService.php` | `transition()`, `cancel()`, `expireUnpaid()` |

## For AI Agents

### Working In This Directory
- `CheckoutService::__construct` injects `InventoryService` then `PreorderService`; `OrderService` injects the same two. Constructor injection order is part of the contract — do not reshuffle.
- `checkout()` is idempotent through `IdempotencyKey` (scope `checkout`): if the key already resolves to an order, that order is returned and nothing is created. The key column is UNIQUE and the row is written **inside** the transaction, so two concurrent requests with the same key can both pass the pre-check and the loser dies on the unique index — wrap the insert if you need to make that graceful.
- `IdempotencyKey` rows carry `expires_at = now()->addDay()`; a daily scheduled job purges expired keys (`routes/console.php`).
- `preview()` calls `calculate(..., lock: false)` → no row locks and no stock check. `checkout()` calls it with `lock: true` → locks variants, inventory levels, the coupon and the campaign. Preview is advisory only.
- `calculate()`: empty cart → 422 `VALIDATION_ERROR`; more than one preorder campaign → 422 (MVP limit); variant and its product must both be `active` → 422 `VALIDATION_ERROR`.
- Pricing: regular unit price = `product_variants.price_amount`; preorder unit price = `preorder_campaigns.sale_price_per_unit_amount` only — the product's own price is never consulted for a preorder line. `subtotal = unit * qty`, `total = max(0, subtotal - discount + shipping)`.
- Shipping is a flat `30000` VND whenever `subtotal > 0` — hardcoded in `calculate()`.
- `deposit_due = campaign.deposit_per_unit_amount * sum(line qty)`; preorder orders create a `kind=deposit` payment for that amount, regular orders a `kind=full` payment for the total.
- Coupon: preorder checkouts require `coupon.rules['allow_preorder'] === true`; `Coupon::discountFor()` itself enforces active window, usage limit and minimum order, and clamps by `max_discount_amount`. A discount of 0 silently drops the coupon (no error).
- On success `checkout()` writes, in order: `orders` row → `order_items` (with price/name snapshots) → stock reservation (`inventory->reserve` for regular, `preorders->holdQuota` for preorder) → coupon redemption + `used_count` increment → one pending `payments` row → `order_addresses` → `order_status_history` (`null → pending`) → `idempotency_keys` → `activity_logs` (`order.created`) → `SendOrderConfirmation::dispatch($order->id)`.
- The job dispatch sits **inside** the order transaction, so the queued row is only written for orders that actually commit — a rolled-back checkout sends no mail.
- `orders.expires_at` is set to `now()+24h` **only** for `bank_transfer`; COD orders never expire automatically.
- `orders.lookup_token_hash = sha256(Str::random(48))`. The raw token is never stored or returned, so guest access via the `X-Order-Token` header is not actually reachable today; `/api/v1/orders/lookup` (order_number + email) returns `public_id` instead. Lookup-token checks live in `CartController`, not in `OrderService`.
- `OrderService::transition()` re-reads the order with `lockForUpdate()`. Same-status target → no-op (no history row, no activity log). Invalid target → 409 `INVALID_STATE`.
- Transition to `shipped` ships stock — but only for `checkout_type = 'regular'` and only for items with a `variant_id` — and sets `fulfillment_status = 'shipped'`; `delivered`/`completed` set `fulfillment_status = 'delivered'`. Every transition writes an `order_status_history` row and an `activity_logs` row (`order.transition`). A missing inventory level aborts the whole transition with 409 `INVALID_STATE` ("Chua ton kho cho variant nay").
- `cancel()` is allowed only from `pending`, `confirmed`, `processing`; `cancelled`/`expired`/`completed` are no-ops. Preorder orders release campaign quota (then the reservation status is overwritten to `cancelled`), regular orders release inventory. It writes `order_status_history` but **no** `activity_logs` row — asymmetric with `transition()`.
- `expireUnpaid($limit = 100)` runs every five minutes (`Schedule::call(...)->name('expire-unpaid')` in `routes/console.php`). It picks `pending` orders with `expires_at < now()`, skips any order with `paid_amount > 0`, and returns the number expired. It releases stock with the `expire:o{id}:v{variantId}` idempotency key, distinct from cancel's `release:o{id}:v{variantId}`.

### Testing Requirements
- `make test` from repo root (phpunit, db `wibu_store_test`); `make pint` style gate.
- `Feature/CheckoutTest.php` covers the cart→order flow and checkout idempotency; `Feature/PreorderTest.php` covers the preorder branch.
- Any pricing or transition change needs assertions on both `orders`/`order_items` and `inventory_levels` / `preorder_campaigns.reserved_quantity` — the side effects are part of the contract.

### Common Patterns
- Order ids: `orders.id` (internal) vs `orders.public_id` (uuid, used in URLs) vs `orders.order_number` (`WB-{Ymd}-{6 digits}`, unique, generated on create).
- Inventory idempotency keys are derived from order + variant, so re-running a transition is a no-op.
- Statuses live in `App\Domain\Common\Statuses` — use `Statuses::canTransition()` rather than hardcoding allowed pairs.
- Money columns are `bigInteger` VND with a DB CHECK that all order amounts are `>= 0`.

## Dependencies

### Internal
- `App\Models\Order`, `OrderItem`, `OrderAddress`, `OrderStatusHistory`, `Payment`, `Coupon`, `CouponRedemption`, `IdempotencyKey`, `InventoryLevel`, `ProductVariant`, `ActivityLog`
- `App\Domain\Inventory\InventoryService`, `App\Domain\Preorders\PreorderService`, `App\Domain\Common\Statuses`
- Callers: `App\Http\Controllers\Api\V1\CartController` (preview, checkout, cancel), `AdminOperationsController::transition` (admin `perm:orders.transition`), `routes/console.php` (expiry sweep)

### External
- `laravel/framework` — `DB`, `Str`
- Postgres — row locks, unique index on `idempotency_keys.key`, CHECK constraints on order amounts

<!-- MANUAL: -->
