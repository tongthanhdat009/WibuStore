<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-10-18 | Updated: 2026-10-22 -->

# tests

## Purpose
PHPUnit 12 suites. Run via root `make test` against the Postgres database `wibu_store_test`.

## Key Files
| File | Description |
|------|-------------|
| `TestCase.php` | Abstract base extending `Illuminate\Foundation\Testing\TestCase` — no custom bootstrap, no `CreatesApplication` (Laravel 11+ style) |

## Subdirectories
| Directory | Purpose |
|-----------|---------|
| `Feature/` | 8 HTTP-level test files: Catalog, Checkout, Payment, Preorder, Rbac, Inventory, OdooHook, Example (see `Feature/AGENTS.md`) |
| `Unit/` | 1 pure PHPUnit test (see `Unit/AGENTS.md`) |

## For AI Agents

### Working In This Directory
- Never run `php artisan` directly — use `make test` from the repo root (phpunit, DB `wibu_store_test`).
- `phpunit.xml` sets the whole test environment: `APP_ENV=testing`, `DB_CONNECTION=pgsql`, `DB_HOST=127.0.0.1`, `DB_PORT=5432`, `DB_DATABASE=wibu_store_test`, `DB_USERNAME=app`, `CACHE_STORE=array`, `SESSION_DRIVER=array`, `QUEUE_CONNECTION=sync`, `MAIL_MAILER=array`, `BCRYPT_ROUNDS=4`. `DB_PASSWORD` is not in the file — it falls back to `backend/.env` / the environment and is never committed.
- The DB must exist and be migrated before a run: `make db-create` creates `wibu_store` and `wibu_store_test`; `RefreshDatabase` migrates the test DB on the fly.
- Every Feature test except `ExampleTest` uses `RefreshDatabase` and seeds `RolePermissionSeeder` (RBAC) plus `DemoSeeder` (catalog rows) in `setUp()`. `RbacTest` seeds only `RolePermissionSeeder`.
- Tests assert on demo fixtures by slug/SKU: `GSC-MEGUMI-18-STD`, `GSC-GOJO-17-STD`, product slugs `gojo-satoru-unlimited-void-1-7` etc. Renaming those in `DemoSeeder` breaks these suites.
- Domain rules are asserted through the domain service (`PaymentService::confirm()`, `handleWebhook()`), not only over HTTP.
- `InventoryTest` and `OdooHookTest` are regression guards for fixes that were silent bugs: the `(int)`-before-`??` cast bug in `InventoryService::defaultWarehouseId()`, the bare `abort()` in `ship()`, the Odoo push running inside the payment transaction, webhook confirmations never reaching Odoo, and a rejected Odoo payload vanishing without a log line.
- `Http::fake()` appends stubs and the FIRST match wins — a second `Http::fake()` for the same URL is shadowed by the first. `OdooHookTest` therefore registers one stub in `setUp()` and varies the status through `$this->odooStatus`.

### Testing Requirements
- `make test` (all suites) and `make pint` (style gate, `./vendor/bin/pint --test`) before handing work back.
- New endpoint → Feature test with happy path + 401/403. New domain rule → assert via the service.

### Common Patterns
- `tests/Feature/*` uses the JSON API (`getJson`/`postJson`) and asserts on the `data` envelope or `error.code`.
- `tests/Unit/*` extends `PHPUnit\Framework\TestCase` directly (no Laravel container).

## Dependencies

### Internal
- `backend/app/Domain/*` services under test, `backend/database/seeders/*` for fixtures.
- `backend/phpunit.xml` — suite definitions and env overrides.

### External
- `phpunit/phpunit:12`, `fakerphp/faker`, `mockery/mockery`, `nunomaduro/collision` (dev dependencies).

<!-- MANUAL: -->
