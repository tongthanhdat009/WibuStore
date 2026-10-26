<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-10-22 | Updated: 2026-10-22 -->

# Inventory

## Purpose
Stock accounting: `inventory_levels` (on_hand / reserved per warehouse+variant) plus an append-only `inventory_movements` ledger. Every mutation writes one movement row.

## Key Files
| File | Description |
|------|-------------|
| `InventoryService.php` | `defaultWarehouseId`, `reserve`, `release`, `ship`, `adjust` |

## For AI Agents

### Working In This Directory
- No `DB::transaction` inside the service — it runs inside the caller's transaction (checkout, order cancel, order expiry). The docblock on `reserve()` states the contract: rows must already be locked in a stable order.
- Every mutator takes an `$idempotencyKey`. If a movement with that key already exists the call is a silent no-op; `inventory_movements.idempotency_key` is UNIQUE, so a concurrent duplicate fails at the DB, not at the `exists()` pre-check.
- `reserve()`: needs an existing `inventory_levels` row; missing row or `on_hand - reserved < qty` → 409 `INSUFFICIENT_STOCK`. Increments `reserved` only (`delta_on_hand = 0`).
- `release()`: missing level row → silent return, no error (asymmetric with `reserve`/`ship`). Decrements `reserved` by `min($qty, $level->reserved)` but writes the full `-$qty` into `delta_reserved` → the ledger can drift from the level when the clamp kicks in.
- `ship()`: missing level row → 409 `INVALID_STATE` via the repo-wide contract `abort(response()->json(['error' => ['code' => 'INVALID_STATE', 'message' => 'Chua ton kho cho variant nay']], 409))`. Clamps both `reserved` and `on_hand` with `min()` but records the full `-$qty` deltas.
- `adjust()`: `firstOrCreate` the level, re-read it with `lockForUpdate()`, then validate `on_hand + delta >= 0` and `reserved <= on_hand + delta` → else 409 `INVALID_STATE`. Service allows `delta = 0`; the admin controller rejects it with `not_in:0`.
- `defaultWarehouseId()` looks up the warehouse with `code = 'default'` and **creates it** (`code=default`, `name=Kho chinh`) when the row is missing. Lookup and insert are two statements on purpose: the `(int)` cast binds tighter than `??`, so the old one-expression form `(int) $id ?? insertGetId(...)` turned `(int) null` into `0` and the insert branch was unreachable — the method returned `0` instead of creating the warehouse. The insert runs inside the caller's transaction.
- DB invariants (CHECK constraints, `inventory_levels`): `on_hand >= 0 AND reserved >= 0 AND reserved <= on_hand`. A bad write surfaces as a Postgres error, not a domain 409 — services must validate before writing.
- `inventory_levels` is UNIQUE on `(warehouse_id, variant_id)`; `available` is the accessor `max(0, on_hand - reserved)`.
- Movement `type` values written by this service: `reserve`, `release`, `ship`, `adjust` only.

### Testing Requirements
- `make test` from repo root (phpunit, db `wibu_store_test`); `make pint` style gate.
- `Feature/CheckoutTest.php` covers the reserve path; `Feature/RbacTest.php` gates the admin `inventory.adjust` endpoint.
- Any change to clamping, deltas, or the CHECK-constraint assumptions needs a focused assertion on `inventory_levels` + `inventory_movements`.

### Common Patterns
- Idempotency key convention used by callers: `reserve:o{orderId}:v{variantId}`, `release:o{orderId}:v{variantId}`, `expire:o{orderId}:v{variantId}`, `ship:o{orderId}:v{variantId}`, `adj:{uuid}`.
- Reference columns: `reference_type` (`order`, `adjustment`) + `reference_id`; `actor_user_id` records who did it.
- Read-only views of the ledger live in the admin controller (`/api/v1/admin/inventory`, `/inventory/movements`), not here.

## Dependencies

### Internal
- `App\Models\InventoryLevel`, `App\Models\InventoryMovement`
- `App\Domain\Orders\CheckoutService` / `OrderService` (callers), `App\Domain\Common\Statuses` (`MOVEMENT_TYPES`)

### External
- `laravel/framework` — `DB`, query builder
- Postgres — row locks, unique index on `idempotency_key`, CHECK constraints

<!-- MANUAL: -->
