<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-10-18 | Updated: 2026-10-18 -->

# database

## Purpose
Schema + demo data. 10 migrations (identity→catalog→media/inventory→cart/order→payment/preorder→promo/cms), 4 seeders.

## Subdirectories
| Directory | Purpose |
|-----------|---------|
| `migrations/` | Chronological schema; pg_trgm + uuid-ossp enabled in db-create |
| `seeders/` | `DatabaseSeeder` → `RolePermissionSeeder`, `DemoSeeder`, Portfolio* |
| `factories/` | `UserFactory.php` only |

## For AI Agents
- New tables: one migration, FK with cascade thought through; update `DemoSeeder` if demo needs it.
- Never edit landed migrations that fresh/seed depend on — add new one.

<!-- MANUAL: -->
