<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-10-22 | Updated: 2026-10-22 -->

# Preorders

## Purpose
Preorder campaigns (quota + pricing) and per-order reservations, including the deposit → balance → completed lifecycle.

## Key Files
| File | Description |
|------|-------------|
| `PreorderService.php` | `campaignForCheckout`, `holdQuota`, `markDepositPaid`, `markBalanceDue`, `markBalancePaid`, `releaseQuota` |

## For AI Agents

### Working In This Directory
- No `DB::transaction` inside the service — it runs inside the caller's transaction (checkout, order cancel, payment sync).
- `campaignForCheckout(int $campaignId, bool $lock): PreorderCampaign` — loads with the `variant` relation and optionally `lockForUpdate()`. Requires `status === 'open'` AND `now >= opens_at` AND `now <= closes_at` (the close check is `now()->gt($closes_at)`, so exactly `closes_at` still counts as open). A NULL `opens_at` / `closes_at` means unbounded. Otherwise 409 `INVALID_STATE` ("Chien dich pre-order khong mo").
- `holdQuota(int $campaignId, OrderItem $item, int $qty): PreorderReservation` — locks the campaign; `quota - reserved_quantity < qty` → 409 `INSUFFICIENT_STOCK` ("Het quota pre-order"). Otherwise increments `reserved_quantity` and creates the reservation with `status = 'awaiting_deposit'`, `deposit_required_amount = deposit_per_unit_amount * qty`, `balance_due_amount = max(0, sale_price_per_unit_amount * qty - deposit)`.
- Gotcha: `deposit_deadline_at` is hardcoded to `now() + 24 hours`. The campaign's own `balance_due_days` column is not used here.
- `markDepositPaid(int $orderId): void` — all reservations of the order's items (`lockForUpdate`), `awaiting_deposit` → `deposit_paid` + `deposit_paid_at`.
- `markBalanceDue(int $reservationId, ?int $actorId, int $dueDays = 14): PreorderReservation` — reservation must be `deposit_paid` or `balance_due`, else 409 `INVALID_STATE` ("Reservation chua coc xong"). Creates the balance payment with `Payment::firstOrCreate(['order_id', 'kind' => 'balance'], [...])`: method `bank_transfer`, `amount = order.total_amount - order.paid_amount`, `status = 'pending'`, `idempotency_key = 'pay:o{orderId}:balance'`. Then sets `status = 'balance_due'`, `balance_deadline_at = now() + $dueDays`, and logs `preorder.balance_due`.
  - Gotcha: `firstOrCreate` means an existing balance payment is never re-priced — if the order total changes, the balance payment keeps its old amount.
  - Gotcha: `$dueDays` comes from the admin request (`balance-due` endpoint, clamped to `>= 1`), not from `preorder_campaigns.balance_due_days`.
- `markBalancePaid(int $orderId): void` — reservations in `balance_due` **or** `deposit_paid` → `completed`. `preorder_reservations` has no `completed_at` column, so completion carries no timestamp.
- `releaseQuota(PreorderReservation $r): void` — no-op for `expired` / `cancelled` / `completed`; otherwise decrements `campaign.reserved_quantity` by `min($r->quantity, $campaign->reserved_quantity)` and sets the reservation to `expired`. `OrderService::cancel()` calls it and then overwrites the status to `cancelled`.
- Reservation statuses come from `Statuses::RESERVATION_STATUSES`: `awaiting_deposit`, `deposit_paid`, `balance_due`, `completed`, `expired`, `cancelled`.
- DB constraints that raise Postgres errors instead of domain 409s when the data is bad: `preorder_quota` (`quota >= 0`, `0 <= reserved_quantity <= quota`) and `preorder_deposit` (`0 <= deposit_per_unit_amount <= sale_price_per_unit_amount`).
- `PreorderCampaign::available` is the accessor `max(0, quota - reserved_quantity)`; `scopeOpen()` is the model-level open-campaign filter (`status = 'open'` and inside the time window). A NULL `opens_at` / `closes_at` is unbounded — the same rule as `campaignForCheckout()`; the old `where('opens_at', '<=', now())` silently excluded campaigns with a NULL `opens_at`. The scope differs from `campaignForCheckout()` only at the `closes_at` boundary (scope uses `closes_at > now()`, so exactly `closes_at` is already closed). It is used by `ProductVariant::openPreorderCampaigns()` and by `CatalogController::preorders()`.
- Domain errors use the repo contract: `abort(response()->json(['error' => ['code' => ..., 'message' => ...]], 409))`.

### Testing Requirements
- `make test` from repo root (phpunit, db `wibu_store_test`); `make pint` style gate.
- `Feature/PreorderTest.php` covers the campaign reserve flow; `Feature/CheckoutTest.php` covers the preorder branch of checkout.
- A change to quota or deposit math needs assertions on `preorder_campaigns.reserved_quantity`, `preorder_reservations.deposit_required_amount` / `balance_due_amount`, and the created `payments` row.

### Common Patterns
- A preorder checkout creates one reservation per order item. `OrderItem::reservation()` is a `hasOne`, but `preorder_reservations.order_item_id` has no unique index — the relation assumes a single row per item.
- Preorder and regular checkout never mix: the branch is decided once by `checkout_type` in `CheckoutService`.
- Money fields (`deposit_per_unit_amount`, `sale_price_per_unit_amount`, `deposit_required_amount`, `balance_due_amount`) are integer VND `bigInteger`.
- Admin entry points: `POST /api/v1/admin/preorder-campaigns/{id}/open|close`, `POST /api/v1/admin/preorder-reservations/{id}/balance-due` — all behind `perm:preorders.manage`.

## Dependencies

### Internal
- `App\Models\PreorderCampaign`, `PreorderReservation`, `OrderItem`, `Order`, `Payment`, `ActivityLog`
- `App\Domain\Common\Statuses` (`RESERVATION_STATUSES`, `CAMPAIGN_STATUSES`)
- Callers: `App\Domain\Orders\CheckoutService` / `OrderService`, `App\Domain\Payments\PaymentService` (deposit/balance sync), `App\Http\Controllers\Api\V1\Admin\AdminOperationsController`

### External
- `laravel/framework` — `DB` (via model queries), Eloquent `lockForUpdate`
- Postgres — row locks, CHECK constraints on campaign quota and deposit

<!-- MANUAL: -->
