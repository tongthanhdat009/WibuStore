<!-- Parent: ../../AGENTS.md -->
<!-- Generated: 2026-10-22 | Updated: 2026-10-22 -->

# Public

## Purpose
Storefront reads: catalog, taxonomies, preorder campaigns, CMS pages and collections, plus product reviews. Most routes are anonymous; the review write and the eligibility check are not.

## Key Files
| File | Description |
|------|-------------|
| `CatalogController.php` | health, product list/detail, categories, anime series, manufacturers, preorders, home page, collections |
| `ReviewController.php` | Public review list with stats, customer eligibility check, review submission |

## For AI Agents

### Working In This Directory
- `CatalogController::index` returns `Product::published()` only — a product needs `status = 'active'` **and** `published_at <= now()` to be listed. `AdminCatalogController::publish` is what sets `published_at`.
- List filters: `q`, `category`, `anime_series`, `manufacturer`, `min_price`, `max_price`, and `sort` (`newest` default, plus `price_asc`, `price_desc`, `name_asc`). `per_page` is clamped to 1..48.
- `category` matches `categories.slug` — the SAME slugs the storefront uses (`scale-figures`, `nendoroid`, `action-figures`, `model-kits`). There is one slug namespace; `2026_10_22_100800_unify_category_slugs.php` migrated the seeded categories into it.
- `index` and `show` eager-load `activeVariants.inventoryLevels` and `activeVariants.openPreorderCampaigns` so `Product::availability()` can be computed without an N+1. It returns `pre_order` (open campaign with quota) → `in_stock` (any active variant has stock) → `sold_out`.
- `CatalogController::home` returns the published revision content of the CMS page with slug `home`; `collections` lists pages whose slug starts with `collection-`, and `collection($slug)` loads `collection-<slug>`.
- `CatalogController::preorders` lists `PreorderCampaign::open()` campaigns with a computed `available` = `quota - reserved_quantity`.
- `ReviewController::index` returns approved reviews only, 10 per page, plus `meta.stats` = `{count, average}` where `average` is the mean rating rounded to 1 decimal.
- `ReviewController::store` requires `auth:sanctum`: rating 1..5, body 10..2000 chars, optional title ≤255. A second review by the same user → 409 `INVALID_STATE`; no qualifying purchase → 403 `FORBIDDEN`. New reviews are stored with `status = 'pending'` and need admin moderation (`PATCH /admin/reviews/{id}`).
- The purchase check accepts an order whose `payment_status` is `paid|partially_paid` or whose `order_status` is `delivered|completed`, excluding `cancelled|expired`, and matching any variant of the product.
- `ReviewController::eligibility` also requires `auth:sanctum` — it lives under `Public/` but is not anonymous.
- Search is Postgres `ilike` — case-insensitive, Postgres-only.

### Testing Requirements
- `make test` from the repo root (DB `wibu_store_test`); `make pint` is the style gate. Invoke PHP only through the root `make` targets, not `php artisan` directly. The runtime is host PHP (8.3+), not Docker — see `backend/AGENTS.md`.
- `Feature/CatalogTest.php` covers the published-only listing, the detail/filters, the category filter against the storefront slug, and the availability states.

### Common Patterns
- Public endpoints return `['data' => ...]`; hand-rolled paginated ones add `meta` (`total`, `page` or `pagination`), while `Resource::collection(...)` keeps Laravel's paginator envelope — check which one the client reads before changing a controller.
- Product payloads expose `public_id` as `id`; the numeric PK stays internal.

## Dependencies

### Internal
- `App\Http\Resources\ProductResource`, `App\Http\Resources\ProductDetailResource`, `App\Models\Product`, `App\Models\ProductReview`, `App\Models\Order`, `App\Models\CmsPage`.

### External
- `laravel/framework:13`

<!-- MANUAL: -->
