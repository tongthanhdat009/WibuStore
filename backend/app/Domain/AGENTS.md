<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-10-18 | Updated: 2026-10-18 -->

# Domain

## Purpose
Fat business-logic services; controllers only HTTP glue. All money integer VND, all writes transactional.

## Key Files
| File | Description |
|------|-------------|
| `Orders/CheckoutService.php` (202 lines) | Preview/calc + checkout, idempotency via `IdempotencyKey`, coupon/ship/discount |
| `Orders/OrderService.php` (117) | Status transitions, cancel, lookup-token auth |
| `Inventory/InventoryService.php` (98) | Reserve/release/adjust, `InventoryLevel` + movements |
| `Payments/PaymentService.php` (82) | Confirm/reject COD/transfer, webhook handling |
| `Preorders/PreorderService.php` (54) | Campaign open/close, reservations |
| `Common/Statuses.php` (48) | Order/payment/fulfillment status enums |

## For AI Agents
- New rule → service method in `DB::transaction`, log to `ActivityLog`. Never put logic in controllers.
- Checkout touches Inventory+Preorders — keep constructor injection order.

## Dependencies
- Internal: `App\Models/*`, `ActivityLog`
- External: `DB`, `Str`

<!-- MANUAL: -->
