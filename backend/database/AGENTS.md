<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-10-18 | Updated: 2026-10-22 -->

# database

## Purpose
Schema, seeders and factories. 12 migrations, 6 seeders, 1 factory. Postgres only — `pg_trgm` + `uuid-ossp` extensions are enabled when the databases are created.

## Key Files
| File | Description |
|------|-------------|
| `.gitignore` | Ignores `*.sqlite*` — `database.sqlite` is an untracked leftover, not the project DB |
| `database.sqlite` | 120 KB leftover from the Laravel skeleton install. Untracked and unused; the project runs on Postgres (`wibu_store` / `wibu_store_test`) |

## Subdirectories
| Directory | Purpose |
|-----------|---------|
| `migrations/` | 12 chronological migrations: identity → catalog → media/inventory → cart/order → payment/preorder → promo/cms → sanctum → reviews → wishlists (see `migrations/AGENTS.md`) |
| `seeders/` | 6 seeders: `DatabaseSeeder` → `RolePermissionSeeder`, `DemoSeeder`, `PortfolioImageSeeder`, `PortfolioEnrichSeeder`, `CollectionSeeder` (see `seeders/AGENTS.md`) |
| `factories/` | `UserFactory.php` only (see `factories/AGENTS.md`) |

## For AI Agents

### Working In This Directory
- Money columns are `bigInteger` holding VND — never float/decimal.
- Postgres extensions `pg_trgm` (trigram search) and `uuid-ossp` are created by `make db-create`; they are not in the migrations. If a fresh environment lacks them, re-run `make db-create` rather than adding a migration.
- `uuid` public IDs default to `gen_random_uuid()` via `DB::raw` defaults on `products.public_id` and `orders.public_id`.
- `database.sqlite` is dead weight from the skeleton. Do not migrate, seed or wire it into config — `DB_CONNECTION=pgsql` everywhere.
- Demo data lives in the seeders, not in migrations. Schema-only migrations must stay free of demo rows.

### Testing Requirements
- `make fresh` (migrate:fresh --seed) resets `wibu_store`; `make test` uses `wibu_store_test` with `RefreshDatabase`.
- The `testing` environment short-circuits `DatabaseSeeder` after roles/permissions — demo seeders do not run in tests.

### Common Patterns
- New tables: one migration, FK delete behaviour chosen deliberately (`cascadeOnDelete` for owned rows, `nullOnDelete` for optional links, `restrictOnDelete` for rows referenced by money/history records).
- Money and quantity invariants are enforced with raw `CHECK` constraints (`DB::statement('ALTER TABLE ... ADD CONSTRAINT ...')`), not application-only validation.
- Never edit a landed migration — `make fresh` and the seeders depend on the existing history. Add a new migration instead.

## Dependencies

### Internal
- `app/Models/*` — 32 Eloquent models mapped to these tables.
- `database/seeders/DemoSeeder.php` creates the rows the Feature tests assert against.

### External
- Postgres (`pg_trgm`, `uuid-ossp`), `laravel/framework:13` migration builder.

<!-- MANUAL: -->
