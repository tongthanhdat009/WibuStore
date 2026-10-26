<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-10-22 | Updated: 2026-10-22 -->

# Models

## Purpose
32 Eloquent models — the whole persistence layer. Models hold relations, casts, scopes and small helpers; transactions and cross-model workflows live in `App/Domain` services.

## Key Files
| File | Description |
|------|-------------|
| `ActivityLog.php` | Admin audit trail (`activity_logs`); `ActivityLog::record(...)` static helper |
| `AnimeSeries.php` | Anime franchise taxonomy (`anime_series`), `active` scope |
| `Cart.php` | Cart container — member (`user_id`) or guest (`guest_token_hash`), `cart_type` regular/preorder |
| `CartItem.php` | Cart line: variant + optional preorder campaign + quantity |
| `Category.php` | Self-referencing product category tree, `active` scope |
| `CmsPage.php` | CMS page (`cms_pages`); `current_published_revision_id` points at the live revision |
| `CmsPageRevision.php` | Versioned page content (`content` cast to array) |
| `Coupon.php` | Discount rules; `isUsable()` / `discountFor()` compute the integer VND discount |
| `CouponRedemption.php` | One row per coupon use on an order |
| `IdempotencyKey.php` | Cached checkout response keyed by `key` (scope `checkout`) |
| `InventoryLevel.php` | Per warehouse/variant `on_hand` + `reserved`; `available` accessor |
| `InventoryMovement.php` | Append-only stock movement ledger |
| `Manufacturer.php` | Product manufacturer |
| `MediaAsset.php` | File record; `url()` builds a public URL or a 30-minute signed S3 URL |
| `Order.php` | Order header: status trio, integer VND totals, `public_id` + `order_number` |
| `OrderAddress.php` | Order shipping/billing address snapshot (`order_addresses`) |
| `OrderItem.php` | Order line with price/name/SKU snapshots + preorder reservation |
| `OrderStatusHistory.php` | Order state-change log (table `order_status_history`) |
| `Payment.php` | Payment record: kind (`full|deposit|balance|refund`), method, status, amount |
| `PaymentEvent.php` | Provider webhook events, deduped on `(provider, event_id)` |
| `Permission.php` | Permission rows; `slug` is the value used by `perm:*` in routes |
| `PreorderCampaign.php` | Preorder window + quota + deposit/price; `open` scope, `available` accessor |
| `PreorderReservation.php` | Deposit/balance lifecycle for one preorder order line |
| `Product.php` | Product root: status, slug, attributes; soft deletes, `published` scope, `availability()` |
| `ProductMedia.php` | Product ↔ media link with sort order and primary flag (table `product_media`) |
| `ProductReview.php` | Customer reviews; `approved()` scope filters `status = 'approved'` |
| `ProductVariant.php` | SKU row: `price_amount`, `compare_at_amount`, `status`; `preorderCampaigns()` / `openPreorderCampaigns()` |
| `Role.php` | Role; pivots `role_user` and `permission_role` |
| `Setting.php` | Key/value settings; `value` cast to array |
| `User.php` | Authenticatable user; `roles()` pivot `role_user`, `hasPermission()` |
| `UserAddress.php` | Saved customer address (ward/district/province) |
| `Warehouse.php` | Warehouse code/name/active flag |

## For AI Agents

### Working In This Directory
- Money columns are integers (`price_amount`, `compare_at_amount`, `total_amount`, `paid_amount`, `quota`-independent `deposit_*`/`balance_due_amount`) — never cast to float.
- `User` declares fillable/hidden with the PHP attributes `#[Fillable([...])]` and `#[Hidden([...])]`; every other model still uses the classic `protected $fillable` array. Follow the file you are editing, not the other way round.
- `User` casts `password` to `hashed`, so a plain-text password passed to `User::create` is hashed automatically.
- `User::hasPermission($slug)` returns true for any user holding the `super_admin` role; otherwise the permission must come through one of the user's roles (`role_user` → `roles` → `permission_role` → `permissions`).
- `ActivityLog::record(action, subjectType, subjectId, before, after, actorId)` fills `actor_user_id` from `auth()->id()` when the actor is omitted, and stamps `request_id` from the `X-Request-ID` header plus the request IP. `before_redacted` / `after_redacted` are cast to arrays.
- `ActivityLog`, `CouponRedemption`, `IdempotencyKey`, `InventoryMovement`, `OrderAddress`, `OrderItem`, `OrderStatusHistory` and `PaymentEvent` all have `$timestamps = false` — set `created_at` explicitly.
- `Product` uses `SoftDeletes` (`products.deleted_at` via `softDeletesTz()`), so a soft-deleted product stays out of `published()` but its rows remain.
- `OrderStatusHistory` and `ProductMedia` set `$table` explicitly (`order_status_history`, `product_media`) because Laravel's pluralizer would guess `...histories` / `...medias`. Every other model relies on default pluralization.
- `Product::published()` = `status = 'active'` **and** `published_at <= now()`. `Product::active()` is only the status half.
- `Product::availability(): string` returns `'pre_order'` when an active variant has an open pre-order campaign with quota left, `'sold_out'` when no active variant has stock left, otherwise `'in_stock'`. It expects `activeVariants.inventoryLevels` and `activeVariants.openPreorderCampaigns` to be eager-loaded — `CatalogController::index()` / `::show()` do that. There is no `availability` column on `products`.
- Gotcha: `availability()` counts stock across **every** warehouse (`inventoryLevels` is one row per warehouse+variant). A variant with `track_inventory = false` is never stock-checked at checkout, so `availability()` short-circuits to `'in_stock'` for it.
- `Coupon::discountFor($orderAmount)` computes a percent discount with `intdiv($amount * $value, 100)` — integer math only, no rounding to 2 decimals. `isUsable()` enforces the active flag, date window, usage limit and minimum order.
- `MediaAsset::url()` returns a 30-minute `temporaryUrl` for private S3 assets, a plain base+root URL for public ones, and `/storage/<key>` otherwise.
- `InventoryLevel` and `PreorderCampaign` expose an `available` accessor: `max(0, on_hand - reserved)` and `max(0, quota - reserved_quantity)`.
- `PreorderCampaign::scopeOpen()` treats a NULL `opens_at` / `closes_at` as unbounded, matching `PreorderService::campaignForCheckout()`. The old `where('opens_at', '<=', now())` silently dropped campaigns with a NULL `opens_at`.
- `IdempotencyKey` looks up by `(key, scope)` but the DB unique index is on `key` alone — one key per table row, regardless of scope.
- Gotcha: there is no `Wishlist` model. The `wishlists` table is queried through raw `DB::table('wishlists')` in `AuthController`.
- Gotcha: `Warehouse` has no relations. `InventoryLevel` and `InventoryMovement` only relate to the variant, not to the warehouse.
- `ProductVariant` has two pre-order relations: `preorderCampaigns()` (all campaigns for the variant) and `openPreorderCampaigns()` (only campaigns open for ordering, via `PreorderCampaign::scopeOpen`). The old `availableIn($warehouseId)` helper is gone — stock availability comes from `InventoryLevel::available` / `InventoryService`.

### Testing Requirements
- `make test` from the repo root (DB `wibu_store_test`); `make pint` is the style gate. Invoke PHP only through the root `make` targets, not `php artisan` directly. The runtime is host PHP (8.3+), not Docker — see `backend/AGENTS.md`.
- Model-level rules are asserted through the services in `Feature/CheckoutTest.php`, `Feature/PaymentTest.php` and `Feature/PreorderTest.php`, not by poking the models directly.

### Common Patterns
- Relations are plain methods, not attributes; scopes are `scopeX($q)` on the model.
- `booted()` hooks seed `public_id` (uuid), `slug` / `order_number` / `status` defaults on create.
- A new model needs a matching migration table, a `$fillable` list, and casts for every datetime/array/json column.

## Dependencies

### Internal
- `App\Domain\*` services read and write these models; `App\Http\Controllers\*` map them to JSON.

### External
- `laravel/framework:13` (`Model`, `SoftDeletes`, `HasApiTokens`), `laravel/sanctum:4`

<!-- MANUAL: -->
