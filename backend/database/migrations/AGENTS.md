<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-10-22 | Updated: 2026-10-22 -->

# migrations

## Purpose
Chronological Postgres schema for the whole store: identity/RBAC, catalog, media + inventory, cart + orders, payments + preorders, promo/CMS/platform, Sanctum tokens, product reviews, wishlists.

## Key Files
| File | Description |
|------|-------------|
| `0001_01_01_000000_create_users_table.php` | Laravel stock: `users`, `password_reset_tokens`, `sessions` |
| `0001_01_01_000001_create_cache_table.php` | Laravel stock: `cache`, `cache_locks` (used by `CACHE_STORE=database`) |
| `0001_01_01_000002_create_jobs_table.php` | Laravel stock: `jobs`, `job_batches`, `failed_jobs` (used by `QUEUE_CONNECTION=database`) |
| `2026_10_17_100000_create_identity_tables.php` | Adds `users.public_id`/`phone`/`avatar`/`status`/`last_login_at`; creates `roles`, `permissions`, `role_user`, `permission_role`, `user_addresses` |
| `2026_10_17_100100_create_catalog_tables.php` | `anime_series`, `manufacturers`, `categories`, `products`, `product_variants`, `product_categories` |
| `2026_10_17_100200_create_media_inventory_tables.php` | `media_assets`, `product_media`, `warehouses`, `inventory_levels`, `inventory_movements`; links `anime_series.cover_media_id` + `manufacturers.logo_media_id` |
| `2026_10_17_100300_create_cart_order_tables.php` | `carts`, `cart_items`, `orders`, `order_items`, `order_addresses`, `order_status_history` |
| `2026_10_17_100400_create_payment_preorder_tables.php` | `payments`, `payment_events`, `preorder_campaigns`, `preorder_reservations`; adds the `preorder_campaign_id` FK to `cart_items` and `order_items` |
| `2026_10_17_100500_create_promo_cms_platform_tables.php` | `coupons`, `coupon_redemptions`, `cms_pages`, `cms_page_revisions`, `activity_logs`, `settings`, `idempotency_keys`; links `cms_pages.current_published_revision_id` + `preorder_campaigns.terms_revision_id` |
| `2026_10_17_144133_create_personal_access_tokens_table.php` | Laravel stock: `personal_access_tokens` (Sanctum) |
| `2026_10_18_100600_create_product_reviews_table.php` | `product_reviews` — unique `(product_id, user_id)`, `status` default `pending`, `CHECK rating BETWEEN 1 AND 5` |
| `2026_10_19_100700_create_wishlists_table.php` | `wishlists` — unique `(user_id, product_id)`, index `(user_id, created_at)` |
| `2026_10_22_100800_unify_category_slugs.php` | Renames the seeded categories to the storefront's vocabulary (`scale-figures`, `nendoroid`, `action-figures`, `model-kits`) and folds the duplicate "Scale Figure" rows into one. `down()` is intentionally empty — the rename is not reversible |

## For AI Agents

### Working In This Directory
- Never edit a landed migration — `make fresh` and the seeders replay this exact history. Add a new migration file instead.
- Timestamps are `2026_10_XX_HHMMSS_*`; the date segment is a manual sequence, not a real clock. Continue it (next free slot after `100700`).
- Money columns are `bigInteger` VND (`price_amount`, `total_amount`, `paid_amount`, `deposit_per_unit_amount`, ...). Never introduce float/decimal money.
- Invariants live in the DB as raw `CHECK` constraints (`DB::statement('ALTER TABLE ... ADD CONSTRAINT ...')`): non-negative amounts, `reserved <= quota`, `deposit <= sale_price`, `reserved <= on_hand`, `quantity > 0`, `rating` 1..5. Prefer adding a constraint over app-only validation.
- Delete behaviour is deliberate per FK: `cascadeOnDelete` for owned rows (`product_reviews`, `wishlists`, `cart_items`), `nullOnDelete` for optional links (`user_id` on `orders`, `order_id` on `product_reviews`), `restrictOnDelete` where history or money depends on the row (`product_variants`, `payments` → `orders`, `preorder_campaigns`).
- `gen_random_uuid()` defaults on `products.public_id` and `orders.public_id` need Postgres — the `pg_trgm`/`uuid-ossp` extensions are created by `make db-create`, not by a migration.
- `preorder_campaign_id` on `cart_items`/`order_items` is a plain `unsignedBigInteger` first, promoted to a real FK by the payment/preorder migration — keep that ordering if you extend the chain.

### Testing Requirements
- `make migrate` for `wibu_store`, `make fresh` to reset + reseed, `make test` for `wibu_store_test` (`RefreshDatabase`).
- Any new table referenced by a Feature test must exist in `wibu_store_test` — run the migrations there before asserting.

### Common Patterns
- One migration = one logical group of tables. Anonymous class `return new class extends Migration` with `up()`/`down()`.
- `down()` drops in reverse creation order and removes the FKs it added to other tables first.

## Dependencies

### Internal
- `app/Models/*` — every table has a matching Eloquent model.
- `database/seeders/DemoSeeder.php` — seeds rows that depend on this schema.

### External
- Postgres (`gen_random_uuid()`, `CHECK` constraints, `jsonb`, `timestamptz`).

<!-- MANUAL: -->
