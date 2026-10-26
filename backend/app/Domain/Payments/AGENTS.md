<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-10-22 | Updated: 2026-10-22 -->

# Payments

## Purpose
Payment record lifecycle: manual confirm/reject by staff, provider webhooks with replay protection, and reconciliation of the order's `payment_status` from the payment ledger.

## Key Files
| File | Description |
|------|-------------|
| `PaymentService.php` | `confirm()`, `reject()`, `handleWebhook()`, `syncPreorder()`, `reconcile()`, `fireOdooIfPaid()` |

## For AI Agents

### Working In This Directory
- `confirm(int $paymentId, ?int $actorId, ?string $providerRef = null): Payment` — one `DB::transaction`, `lockForUpdate()` on the payment row.
  - Already `confirmed` → returns the row immediately (idempotent): no reconcile, no preorder sync, no Odoo push, no activity log.
  - Not `pending` → 409 `INVALID_STATE` ("Payment khong o trang thai pending").
  - Duplicate `provider_reference` for the same provider → 409 `INVALID_STATE` ("Trung provider reference").
  - Then sets `status`, `confirmed_at`, `confirmed_by`, `provider_reference`, and calls `reconcile()`, `syncPreorder()` and `ActivityLog::record('payment.confirmed', ...)`, in that order.
  - The Odoo push happens **after** the transaction commits, via `fireOdooIfPaid($payment->order_id)` — the HTTP POST never holds the payment row lock.
- Gotcha: the duplicate-reference check is `Payment::where('provider', $payment->provider)->where('provider_reference', $providerRef)`. `provider` is nullable and `CheckoutService` never sets it, so Eloquent turns it into `provider IS NULL` and a second NULL-provider payment carrying the same reference is rejected — even though the DB index `payments_provider_ref_uidx` is partial (`WHERE provider IS NOT NULL AND provider_reference IS NOT NULL`) and would allow it.
- The Odoo push fires only when the **order** reaches `payment_status === 'paid'` after reconcile. That check lives in the protected helper `fireOdooIfPaid(int $orderId): void` — the single place that calls `OdooHookService::firePaid()`. A deposit confirmation leaves the order `partially_paid` → no push.
- `reject(int $paymentId, ?int $actorId, string $reason): Payment` — only from `pending`; sets `status='failed'` + `reject_reason`; logs `payment.rejected`. It does not reconcile the order.
- `handleWebhook(string $provider, string $eventId, array $payload): array` — one transaction; dedupes on `(provider, event_id)` via `payment_events` (UNIQUE index). A replay returns `['deduped' => true, 'event_id' => ...]`.
  - Stores a **redacted** payload: only `status` and `amount` are persisted, never the raw body.
  - When `status === 'paid'` and a `payment_id` is present, it confirms a `pending` payment inline (sets `status`, `confirmed_at`, `provider_reference = $eventId`) and runs `reconcile()` + `syncPreorder()`. A missing payment id is a silent no-op (`first()`, not `firstOrFail()`).
  - `handleWebhook()` writes an `ActivityLog::record('payment.webhook', 'payment_event', $event->event_id, [], ['provider' => ..., 'payment_id' => ..., 'status' => ...])` row for every non-deduped event, and pushes to Odoo after the transaction closes — but only when the event actually confirmed a payment. A deduplicated replay pushes nothing.
  - Gotcha: the webhook audit entry is `payment.webhook`, not `payment.confirmed` — the inline confirmation inside `handleWebhook()` still writes no `payment.confirmed` row.
- `syncPreorder(Payment $payment): void` — `kind === 'deposit'` → `PreorderService::markDepositPaid($order_id)`; `kind === 'balance'` → `markBalancePaid($order_id)`. Resolved through the container (`app(PreorderService::class)`) rather than constructor injection — that is what keeps Payments and Preorders free of a circular DI dependency. Keep it that way.
- `reconcile(int $orderId): void` — sums confirmed payments of kind `full|deposit|balance`, subtracts refunded payments of kind `refund`; `net = max(0, paid - refunded)`. Writes `orders.paid_amount = net` and `orders.payment_status`: `unpaid` when `net <= 0`, `paid` when `net >= total_amount`, otherwise `partially_paid`.
  - Gotcha: `PAYMENT_STATUSES` also lists `refunded` / `partially_refunded`, but `reconcile()` never writes them.
- Two different status vocabularies: the **order** status (`unpaid|partially_paid|paid|...`) and the **payment row** status (`pending|confirmed|failed|refunded`). Do not mix them.
- Domain errors use the repo contract: `abort(response()->json(['error' => ['code' => 'INVALID_STATE', 'message' => '...']], 409))`.

### Testing Requirements
- `make test` from repo root (phpunit, db `wibu_store_test`); `make pint` style gate.
- `Feature/PaymentTest.php` covers confirm/reject and webhook handling.
- A webhook change needs a replay case (same `provider` + `event_id` twice) and an assertion that the stored `payment_events.payload_redacted` contains only `status` and `amount`. The replay must also not push to Odoo a second time (`Feature/OdooHookTest.php`).
- Reconcile changes need assertions on `orders.paid_amount` and `orders.payment_status` for the deposit / balance / refund paths.

### Common Patterns
- `payments.idempotency_key` is UNIQUE; `CheckoutService` writes `pay:o{orderId}:{kind}`, `PreorderService::markBalanceDue` writes `pay:o{orderId}:balance`.
- `payments.amount` has a DB CHECK `amount >= 0`; amounts are integer VND.
- Every mutating method ends with `return $payment->fresh()` so callers get post-update state.
- Admin entry points are `POST /api/v1/admin/payments/{id}/confirm` and `/reject`, both behind `perm:payments.confirm`. Provider webhooks arrive on `POST /api/v1/webhooks/payments/{provider}` (no `perm:` gate).

## Dependencies

### Internal
- `App\Models\Payment`, `PaymentEvent`, `Order`, `ActivityLog`
- `App\Domain\Odoo\OdooHookService` (paid-order hook), `App\Domain\Preorders\PreorderService` (deposit/balance sync), `App\Domain\Common\Statuses` (`PAYMENT_STATUSES`, `PAYMENT_KINDS`, `PAYMENT_RECORD_STATUSES`)
- Callers: `App\Http\Controllers\Api\V1\Admin\AdminOperationsController` (confirm, reject, webhook)

### External
- `laravel/framework` — `DB`
- Postgres — row locks, partial unique index on `(provider, provider_reference)`, unique `idempotency_key`

<!-- MANUAL: -->
