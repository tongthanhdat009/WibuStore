<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-10-22 | Updated: 2026-10-22 -->

# Domain

## Purpose
Business-logic layer: fat services, HTTP glue stays in controllers. Money is integer VND, writes are transactional, failures use the repo-wide `{error:{code,message}}` shape.

## Key Files
| File | Description |
|------|-------------|
| `Common/Statuses.php` | Shared status constants + order transition map |
| `Orders/CheckoutService.php` | Preview/calc + checkout, idempotency, coupon/shipping/discount |
| `Orders/OrderService.php` | Order status transitions, cancel, expire-unpaid sweep |
| `Inventory/InventoryService.php` | Reserve/release/ship/adjust stock, movement ledger |
| `Payments/PaymentService.php` | Confirm/reject, webhook dedupe, order reconcile, preorder sync, Odoo push (post-commit) |
| `Preorders/PreorderService.php` | Campaign window, quota hold, deposit/balance lifecycle |
| `Odoo/OdooHookService.php` | Outbound "order paid" webhook to the Odoo connector |

## Subdirectories
| Directory | Purpose |
|-----------|---------|
| `Common/` | Status constants shared by every service (see `Common/AGENTS.md`) |
| `Inventory/` | Stock levels + movement ledger (see `Inventory/AGENTS.md`) |
| `Odoo/` | Fire-and-forget paid-order hook (see `Odoo/AGENTS.md`) |
| `Orders/` | Checkout + order lifecycle (see `Orders/AGENTS.md`) |
| `Payments/` | Payment lifecycle + reconciliation (see `Payments/AGENTS.md`) |
| `Preorders/` | Preorder campaigns + reservations (see `Preorders/AGENTS.md`) |

## For AI Agents

### Working In This Directory
- New rule → a service method in `Domain/`; controllers only validate/authorise/serialize.
- Every write path wraps `DB::transaction` and records `ActivityLog::record(action, subjectType, subjectId, before, after, actorId)`.
- Money = integer VND only: no floats, no decimals; cast with `(int)` at the boundary.
- Domain errors abort with `abort(response()->json(['error' => ['code' => 'X', 'message' => '...']], $status))` — keep that shape (409 `INVALID_STATE` / `INSUFFICIENT_STOCK`, 422 `VALIDATION_ERROR`).
- Row locks: `lockForUpdate()` on orders, payments, inventory levels, campaigns. Lock in a stable order to avoid deadlocks.
- Third-party HTTP calls happen **after** the `DB::transaction` closure closes, never inside it — the Odoo POST used to hold the payment row lock for up to its 5s timeout.
- `InventoryService` and `PreorderService` open no transaction of their own — they run inside the caller's.
- `checkout_type=preorder` and `regular` are mutually exclusive stock paths: campaign quota vs inventory reserve.
- Status transitions are table-driven (`Statuses::canTransition`) — extend the table, do not inline status checks.

### Testing Requirements
- `make test` from repo root (phpunit, db `wibu_store_test`); `make pint` style gate. Never invoke `php artisan` directly — the root `Makefile` owns the PHP binary and `pdo_pgsql` setup.
- Assert domain rules through the service, not only through HTTP (see `backend/tests/AGENTS.md`).
- Suites touching this layer: `Feature/CheckoutTest.php`, `Feature/PaymentTest.php`, `Feature/PreorderTest.php`, `Feature/OdooHookTest.php`.

### Common Patterns
- Idempotency keys: `idempotency_keys` (checkout scope), `inventory_movements.idempotency_key`, `payments.idempotency_key`, `payment_events (provider, event_id)` — all backed by unique indexes.
- Prices are snapshotted on `order_items` (`sku_snapshot`, `product_name_snapshot`, `unit_price_amount`); never re-read product pricing for a placed order.
- Order lifecycle writes `order_status_history` rows; state changes also write `activity_logs`.

## Dependencies

### Internal
- `App\Models\*` — `Order`, `OrderItem`, `OrderAddress`, `OrderStatusHistory`, `Payment`, `PaymentEvent`, `InventoryLevel`, `InventoryMovement`, `PreorderCampaign`, `PreorderReservation`, `Coupon`, `ActivityLog`
- Sibling services — `InventoryService`, `PreorderService`, `OrderService`, `PaymentService`, `OdooHookService`

### External
- `laravel/framework` — `DB`, `Http`, `Log`, `Str`
- Postgres (row locks, unique indexes, CHECK constraints)

<!-- MANUAL: -->
