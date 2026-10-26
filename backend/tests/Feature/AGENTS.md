<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-10-22 | Updated: 2026-10-22 -->

# Feature

## Purpose
HTTP-level tests against the real `/api/v1` routes and a migrated Postgres database. 8 files, 19 test methods.

## Key Files
| File | Description |
|------|-------------|
| `CatalogTest.php` | Listing shows only active+published products; detail, filters, categories and the home CMS page; category filter matches the storefront slug; availability reflects stock and pre-order campaigns |
| `CheckoutTest.php` | Checkout reserves stock and snapshots SKU; oversell rejected; cancel releases the reservation exactly once |
| `PaymentTest.php` | Payment confirm is idempotent; webhook replay is deduped |
| `PreorderTest.php` | Pre-order quota enforced; physical stock never touched |
| `RbacTest.php` | Admin routes require auth + permission; guest order lookup without proof leaks nothing |
| `InventoryTest.php` | Default warehouse is created when missing and reused when present; `ship()` returns the `{error:{code,message}}` contract |
| `OdooHookTest.php` | Confirm and webhook both push the paid order to Odoo; replay is deduped and does not push twice; a 5xx answer is logged |
| `ExampleTest.php` | Laravel scaffolding — `GET /` returns 200 (no `RefreshDatabase`) |

## For AI Agents

### Working In This Directory
- All 5 domain tests share the same shape: `RefreshDatabase` + `setUp()` seeding `RolePermissionSeeder` and `DemoSeeder`. `RbacTest` seeds only `RolePermissionSeeder`.
- What each asserts, precisely:
  - `CatalogTest::test_listing_shows_only_active_published` — flips the first product to `status=draft` / `published_at=null`, then asserts its slug is absent from `GET /api/v1/products` and the list is non-empty.
  - `CatalogTest::test_detail_and_filters` — `GET /api/v1/products?anime_series=jujutsu-kaisen&sort=price_asc&per_page=2` returns exactly 2 items; `GET /api/v1/products/{slug}` returns that slug; `GET /api/v1/categories` is 200; `GET /api/v1/pages/home` returns `data.sections.0.type == hero_comic`.
  - `CheckoutTest::test_checkout_reserves_stock_and_snapshots` — `POST /api/v1/orders` with `Idempotency-Key: k-checkout-1` returns 201, `order_status=pending`, `inventory_levels.reserved == 1`, and the order item carries the SKU snapshot. Replaying the same key returns the same `order_number` and does not reserve twice.
  - `CheckoutTest::test_oversell_rejected_and_cancel_releases_once` — ordering 21 units against 20 on hand returns 409 `INSUFFICIENT_STOCK`; a 2-unit order succeeds; cancelling twice still returns `reserved` to 0 (released once, not twice).
  - `PaymentTest::test_confirm_idempotent_and_webhook_replay_deduped` — `PaymentService::confirm()` twice leaves `payment_status=paid` and `paid_amount == total_amount`; `handleWebhook('testpay','evt-1',...)` returns `deduped=false` first, `deduped=true` on the replay.
  - `PreorderTest::test_quota_enforced_and_no_physical_stock_touched` — quota 2, first reservation of 2 succeeds (`checkout_type=preorder`, payment `kind=deposit`); the second reservation returns 409 `INSUFFICIENT_STOCK`; `inventory_levels.reserved` stays 0 and exactly 1 `preorder_reservation` row exists.
  - `RbacTest::test_admin_routes_require_auth_and_permission` — guest gets 401; a `customer`-role user gets 403; a `super_admin` user gets 200 on `GET /api/v1/admin/dashboard/overview`.
  - `RbacTest::test_guest_lookup_without_proof_leaks_nothing` — `POST /api/v1/orders/lookup` with a non-matching email returns 404, not data.
  - `CatalogTest::test_category_filter_matches_the_storefront_slug` — takes the first slug from `GET /api/v1/categories`, filters `GET /api/v1/products?category={slug}`, and asserts the total is > 0 and every returned product really belongs to that category. Guards the storefront/API slug namespaces staying in sync.
  - `CatalogTest::test_availability_reflects_stock_and_preorder_campaigns` — the Gojo product (open pre-order campaign) is `pre_order`, Megumi is `in_stock`, and draining its `inventory_levels` row flips it to `sold_out`.
  - `InventoryTest::test_default_warehouse_is_created_when_missing` — renames the `default` warehouse to `default-old` (it cannot be deleted: `inventory_levels` restricts on `warehouse_id`), then asserts `defaultWarehouseId()` creates it and returns a real id. Regression guard for the `(int)`-before-`??` precedence bug.
  - `InventoryTest::test_ship_without_inventory_level_returns_error_contract` — catches the `HttpResponseException` and asserts status 409 with `error.code == INVALID_STATE`.
  - `OdooHookTest` — `confirm()` and `handleWebhook()` each push exactly one request to `http://odoo.test/ingest/wibu` carrying the `X-Signature` header and the order number in the body; a replayed webhook returns `deduped=true` and sends nothing more; a 500 answer is asserted via `Log::shouldHaveReceived('warning')`.
- Fixtures come from `DemoSeeder`: SKUs `GSC-GOJO-17-STD` / `GSC-MEGUMI-18-STD`, 20 units on hand per variant, coupon `WIBU10`, open pre-order campaign on the Gojo variant.
- `RbacTest` is the only place `User::factory()` is used — it attaches roles from `RolePermissionSeeder` and acts via `actingAs()` (stateful Sanctum session).

### Testing Requirements
- `make test` from the repo root. `wibu_store_test` must exist (`make db-create`).
- Assert on the JSON envelope: success data lives under `data`, failures under `error.code`.

### Common Patterns
- Build the request body inline via a small `payload()` helper (see `CheckoutTest`) rather than repeating arrays.
- Use a distinct `Idempotency-Key` per checkout in a test — keys are unique per scope.

## Dependencies

### Internal
- `backend/routes/api.php` — endpoints under test.
- `backend/database/seeders/DemoSeeder.php`, `RolePermissionSeeder.php` — fixtures.
- `app/Domain/Payments/PaymentService.php` — payment confirm + webhook dedupe under test.

### External
- `phpunit/phpunit:12`, `laravel/framework:13` testing helpers (`RefreshDatabase`, `actingAs`).

<!-- MANUAL: -->
