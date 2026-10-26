<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-10-22 | Updated: 2026-10-22 -->

# Odoo

## Purpose
Outbound integration: notifies the Odoo connector that an order is fully paid. Fire-and-forget — never blocks or fails the caller.

## Key Files
| File | Description |
|------|-------------|
| `OdooHookService.php` | `firePaid(Order $order): void` — builds, signs and POSTs the payload |

## For AI Agents

### Working In This Directory
- Single static method `firePaid(Order $order): void`. No constructor, no DI, no interface — call it as `OdooHookService::firePaid($order)`.
- Payload: `event_id` = `orders.order_number`, and `order` = `{source:'wibu', source_order_id, customer_name, customer_email, customer_phone, address, lines[], discount_vnd, shipping_vnd, paid_vnd, payment_method, provider_ref, origin_note}`.
- `origin_note` = `"wibu:orders:{$order->id}"` — the numeric order id, not the order number.
- `address` is the shipping `line1` only (no district/province). Empty string when the order has no shipping address.
- `lines[]` come from `order_items` snapshots, never from live product data: `sku_snapshot`→`sku`, `product_name_snapshot`→`name`, `quantity`→`qty`, `unit_price_amount`→`unit_price_vnd`; `is_service` is hardcoded `false`.
- `payment_method` = `method` of the latest confirmed payment (sorted by `confirmed_at` desc), default `cod`. `provider_ref` = that payment's `provider_reference`, `''` when null.
- `discount_vnd` / `shipping_vnd` / `paid_vnd` are cast `(int)` from `orders.discount_amount` / `shipping_amount` / `paid_amount`.
- Signature: `hash_hmac('sha256', $json, config('services.odoo.webhook_secret'))` computed over the **raw** encoded JSON string, sent as header `X-Signature`. The receiver must recompute over the exact raw body — re-encoding the array can change key order/whitespace and break verification.
- Transport: `Http::withBody($json, 'application/json')->withHeaders(['X-Signature' => $sig])->timeout(5)->post(rtrim(config('services.odoo.connector_url'), '/').'/ingest/wibu')` — body is the raw JSON, `Content-Type: application/json`, 5s timeout.
- Config (`config/services.php`, key `odoo`): `connector_url` = `env('ODOO_CONNECTOR_URL', 'http://localhost:8090')`, `webhook_secret` = `env('ODOO_WEBHOOK_SECRET', 'change-me')`. The default secret is a placeholder — set a real one in every non-local environment.
- Failures are swallowed: `catch (\Throwable $e)` → `Log::warning('OdooHook failed: '.$e->getMessage(), ['order' => $order->id ?? null])`. It never throws, so it can never break checkout.
- A non-2xx answer is logged, not thrown: `if (! $res->successful()) Log::warning('OdooHook rejected: HTTP '.$res->status(), ['order' => ...])`. Laravel's HTTP client does not throw on 4xx/5xx without `->throw()`, so this check is what keeps a rejected payload from vanishing without a trace.
- Gotcha: there is no queue, no retry, and no persisted outbound log. A missed delivery is visible only in `laravel.log` — nothing replays it.
- Call sites: `PaymentService::confirm()` and `PaymentService::handleWebhook()`, both through the protected helper `PaymentService::fireOdooIfPaid(int $orderId)` — the single place that checks `order->payment_status === 'paid'` before pushing. A webhook-confirmed paid order now reaches Odoo too.
- The HTTP POST runs **after** the `DB::transaction` closure closes, so it holds neither the payment row lock nor an open transaction. `->timeout(5)` is the only bound: a slow connector delays the HTTP response, not the DB transaction.
- Do not move the call back inside a transaction without updating `Payments/AGENTS.md`.

### Testing Requirements
- `make test` from repo root (phpunit, db `wibu_store_test`); `make pint` style gate.
- `Feature/OdooHookTest.php` covers this class end to end: `Http::fake()` on `odoo.test/*` plus overridden `services.odoo.connector_url` / `services.odoo.webhook_secret`. It asserts the confirm path, the webhook path, that a replayed webhook does not push twice, and that a 5xx answer logs `OdooHook rejected`.
- `Feature/PaymentTest.php` calls `PaymentService::confirm()` without faking HTTP — it attempts a real POST to `http://localhost:8090`; the failure is swallowed, so it only shows up as a `OdooHook failed` warning in the log.
- If you touch the payload shape, assert the body and `X-Signature` explicitly (recompute the HMAC over the raw body), and use `Http::fake()` plus overridden `services.odoo.connector_url` / `services.odoo.webhook_secret` so the test never hits a real connector.

### Common Patterns
- `event_id` is the `order_number`, so re-firing the same order reuses the event id — the connector must dedupe on it (receiver-side behaviour unknown).
- Money fields are always integer VND (`*_vnd` suffix in the payload).
- Payload key names are part of the connector contract — renaming one is a breaking change for Odoo.

## Dependencies

### Internal
- `App\Models\Order` and its `items`, `payments`, `addresses` relations
- `config('services.odoo.connector_url')`, `config('services.odoo.webhook_secret')`
- Caller: `App\Domain\Payments\PaymentService`

### External
- `laravel/framework` — `Http` client, `Log` facade
- Odoo connector HTTP API — `POST {connector_url}/ingest/wibu`

<!-- MANUAL: -->
