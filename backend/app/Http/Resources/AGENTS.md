<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-10-22 | Updated: 2026-10-22 -->

# Resources

## Purpose
JsonResource classes that shape list and detail payloads so the storefront and admin clients get a stable JSON shape.

## Key Files
| File | Description |
|------|-------------|
| `OrderResource.php` | Order header, integer VND totals, items, payments, addresses |
| `ProductResource.php` | Product card: id, min active-variant price, series, manufacturer, image, availability |
| `ProductDetailResource.php` | Product page: description, attributes, categories, variants, gallery, availability |

## For AI Agents

### Working In This Directory
- `OrderResource` exposes the order `public_id` as `id`; the numeric PK stays internal. `items`, `payments` and `addresses` are emitted through `whenLoaded()` — eager-load the relations or the keys disappear from the payload.
- Order money fields (`subtotal_amount`, `discount_amount`, `shipping_amount`, `total_amount`, `paid_amount`) are integer VND.
- Order lines read the snapshot columns on `order_items` (`sku_snapshot`, `product_name_snapshot`, `variant_label_snapshot`) — never re-read current product data for a placed order.
- `ProductResource` derives `price_amount` from `min('price_amount')` over `activeVariants`, so it returns `null` for a product with no active variant. `currency` is hardcoded `'VND'`.
- `availability` is computed by `Product::availability()` — `'pre_order'` when an active variant has an open pre-order campaign with quota left, `'sold_out'` when no active variant has stock left, otherwise `'in_stock'`. Both `ProductResource` and `ProductDetailResource` expose it; `ProductDetailResource` previously had no `availability` field at all. The method needs `activeVariants.inventoryLevels` and `activeVariants.openPreorderCampaigns` eager-loaded — `CatalogController::index()` and `::show()` do that.
- `ProductDetailResource` maps the `media` relation into `gallery`; `ProductResource` uses `primaryMedia.asset` for `image`. Both need the relation eager-loaded to avoid N+1.
- Image URLs come from `MediaAsset::url()`, which returns a 30-minute signed temporary URL for private S3 assets and a plain URL for public ones.

### Testing Requirements
- `make test` from the repo root (DB `wibu_store_test`); `make pint` is the style gate. Invoke PHP only through the root `make` targets, not `php artisan` directly. The runtime is host PHP (8.3+), not Docker — see `backend/AGENTS.md`.
- `Feature/CatalogTest.php` and `Feature/CheckoutTest.php` assert the resource output shape.

### Common Patterns
- `Resource::collection($paginator)` keeps Laravel's paginator envelope (`data` + `meta`/`links`); a hand-rolled `response()->json(['data' => ...])` produces a different envelope. Check which one the client reads before changing a controller.
- Resources are read-only projections — never write to the model from a resource.

## Dependencies

### Internal
- `App\Models\Order`, `App\Models\OrderItem`, `App\Models\Payment`, `App\Models\Product`, `App\Models\ProductVariant`, `App\Models\MediaAsset`.

### External
- `laravel/framework:13` (`JsonResource`)

<!-- MANUAL: -->
