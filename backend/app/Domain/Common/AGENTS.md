<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-10-22 | Updated: 2026-10-22 -->

# Common

## Purpose
Shared constants for every domain service: allowed status values, movement types, and the order transition map. Single source of truth for state vocabularies.

## Key Files
| File | Description |
|------|-------------|
| `Statuses.php` | Const status lists + `canTransition()` order state machine |

## For AI Agents

### Working In This Directory
- Plain `const` arrays, not PHP enums. No imports, no dependencies, no side effects — `final class`, static call only.
- Lists defined: `PRODUCT_STATUSES`, `VARIANT_STATUSES`, `ORDER_STATUSES`, `PAYMENT_STATUSES`, `FULFILLMENT_STATUSES`, `PAYMENT_RECORD_STATUSES`, `PAYMENT_KINDS`, `PAYMENT_METHODS`, `CAMPAIGN_STATUSES`, `RESERVATION_STATUSES`, `CMS_STATUSES`, `MEDIA_STATUSES`, `MOVEMENT_TYPES`.
- `PAYMENT_METHODS` = `cod`, `bank_transfer` (VNPay was removed).
- `ORDER_TRANSITIONS` maps a current order status to its allowed targets; `canTransition($from, $to)` is the only state-machine guard.
- Gotcha: `ORDER_TRANSITIONS` allows `shipped|delivered -> return_requested`, but `return_requested` is absent from `ORDER_STATUSES`. `canTransition()` only consults the transition map, so that target is reachable even though no status list declares it. Nothing in the codebase writes it yet.
- Gotcha: `MOVEMENT_TYPES` lists `receipt`, `preorder_hold`, `preorder_release`, but `InventoryService` only ever writes `reserve`, `release`, `ship`, `adjust`.
- Gotcha: `PAYMENT_STATUSES` (order level) includes `refunded` / `partially_refunded`, but `PaymentService::reconcile()` only ever writes `unpaid`, `partially_paid`, `paid`.
- There is no validator helper for the non-order lists — membership checks are done ad hoc in controllers/services. Add one here rather than scattering `in_array()` calls.

### Testing Requirements
- `make test` from repo root; `make pint` style gate.
- Changing `ORDER_TRANSITIONS` or any status list affects `Feature/CheckoutTest.php`, `Feature/PaymentTest.php`, `Feature/PreorderTest.php` — run them before committing.

### Common Patterns
- New status value → add it to the matching const list *and* to `ORDER_TRANSITIONS` if it is an order state.
- Services import `App\Domain\Common\Statuses` directly; never re-declare the same status strings in a service.

## Dependencies

### Internal
- None — leaf class, no model or service imports.

### External
- None — plain PHP.

<!-- MANUAL: -->
