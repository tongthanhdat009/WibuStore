<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-10-22 | Updated: 2026-10-22 -->

# seeders

## Purpose
Demo and platform seed data. `DatabaseSeeder` is the entry point; `RolePermissionSeeder` always runs, the demo seeders only run outside the `testing` environment.

## Key Files
| File | Description |
|------|-------------|
| `DatabaseSeeder.php` | Entry point: calls `RolePermissionSeeder`, then returns early in the `testing` environment, then calls the four demo seeders |
| `RolePermissionSeeder.php` | 11 permissions + 7 roles (`super_admin`, `store_manager`, `catalog_editor`, `order_staff`, `inventory_staff`, `content_editor`, `customer`) and the admin user `admin@wibustore.vn` / `Admin@123` with `super_admin` |
| `DemoSeeder.php` | Warehouse `default`, series `jujutsu-kaisen`, maker `good-smile`, category `scale-figures`, 3 products (Gojo 8.9M, Megumi 4.2M, Nobara 4.6M VND) with variants + 20 on-hand, one open pre-order campaign, coupon `WIBU10`, published `home` CMS page with `hero_comic` section |
| `PortfolioImageSeeder.php` | Reads `SEED_IMAGE_DIR` (default `database/seeders/data/images`), uploads JPEGs to the `s3` disk and creates products/variants/media. Skips with a warning when the directory is missing |
| `PortfolioEnrichSeeder.php` | Fills `anime_series_id`, `manufacturer_id`, descriptions and `attributes` on every product whose slug ends in `-seed` |
| `SeedProductNamingSeeder.php` | Renames the 51 image-seed products to their real names (vision-identified), fixes series + category; idempotent, runs after enrich |
| `CollectionSeeder.php` | 4 published CMS collection pages (`collection-shibuya-incident`, `collection-chibi-world`, `collection-cosplay-zone`, `collection-scale-showcase`) |

## For AI Agents

### Working In This Directory
- `DatabaseSeeder` returns right after `RolePermissionSeeder` when `app()->environment('testing')` — so demo data never lands in `wibu_store_test`. Feature tests seed `DemoSeeder` explicitly in `setUp()`.
- Seeders are idempotent by design: they use `firstOrCreate` / `syncWithoutDetaching`, so re-running them does not duplicate rows. `CollectionSeeder` is the exception — it creates a new CMS revision per run (`version_number = max + 1`) on purpose.
- `RolePermissionSeeder` is the RBAC source of truth: the permission slugs it creates (`products.read`, `products.write`, `products.publish`, `inventory.adjust`, `orders.transition`, `payments.confirm`, `preorders.manage`, `cms.publish`, `users.manage`, `reports.read`, `settings.manage`) are exactly the `perm:` slugs used in `routes/api.php`.
- `DemoSeeder` product slugs and SKUs (`GSC-GOJO-17-STD`, `GSC-MEGUMI-18-STD`, `GSC-NOBARA-17-STD`) are asserted by the Feature tests — do not rename them without updating `tests/Feature/*`.
- `PortfolioImageSeeder` needs `SEED_IMAGE_DIR` mounted read-only and a reachable S3/Minio disk; it warns and returns when the dir is absent (the normal local case).
- The category slugs are the storefront's vocabulary — `scale-figures`, `nendoroid`, `action-figures`, `model-kits`, plus `cosplay` (added by `2026_10_26_000001_create_cosplay_category`, which also moved the `cosplay-*-seed` products there) — and there is exactly ONE slug namespace shared with `frontend/src/data/categories.ts`. `2026_10_22_100800_unify_category_slugs.php` migrated the old seeded slugs (`scale-figure`, `scale-figure-seed`, `chibi-seed`, `set-figure-seed`, `cosplay-seed`) into them. Do not introduce a second vocabulary.
- `PortfolioEnrichSeeder` only touches products whose slug ends with `-seed`, i.e. the ones created by `PortfolioImageSeeder`. Run it after that seeder.
- Seeders use `firstOrCreate` on business keys (slug, sku, code) — keep those keys stable.

### Testing Requirements
- `make seed` (dev DB), `make fresh` (migrate:fresh + seed), `make test` (test DB).
- Any seeder change must keep `CatalogTest`, `CheckoutTest`, `PaymentTest` and `PreorderTest` passing — they assert on demo rows.

### Common Patterns
- Keep demo data in seeders, never in migrations.
- Money literals in seeders are plain integer VND (e.g. `8900000`).

## Dependencies

### Internal
- `app/Models/*` (`User`, `Role`, `Permission`, `Product`, `ProductVariant`, `InventoryLevel`, `Warehouse`, `PreorderCampaign`, `Coupon`, `CmsPage`, `CmsPageRevision`, `MediaAsset`, `ProductMedia`, `Category`, `AnimeSeries`, `Manufacturer`).
- `config/filesystems.php` — `s3` disk for `PortfolioImageSeeder`.

### External
- `laravel/framework:13` seeder + `Illuminate\Support\Facades\Storage`.

<!-- MANUAL: -->
