# Internal

**Parent context:** `../AGENTS.md`
**Generated:** 2026-10-30 · **Updated:** 2026-10-30

## Purpose
Server-to-server Odoo sync surface (`middleware('source')`, `X-Source-Token`): catalog import in, paid-order export out. No auth:sanctum, no `perm:*`.

## Key Files
| File | Description |
|------|-------------|
| `CatalogImportController.php` | `POST /api/v1/internal/import/catalog` (max 2000 rows): match-or-create variant by SKU, absolute `on_hand` set via `InventoryService::adjust`, never below `reserved` (`max(qty, reserved)`) |
| `ExportController.php` | `GET /api/v1/internal/export/orders?since=<iso>`: paid orders only, unified payload via `OdooOrderPayload::build`, `cursor` = max `updated_at` |

## For AI Agents

### Working In This Directory
- `stock` is ABSOLUTE `on_hand`, omitted = untouched. Per-row try/catch returns `{created, price_updated, stocked, errors}` — partial success, never whole-batch fail.
- Export is read-only; `since` parse failure = full export (no filter), never 422.
- Routes live in `routes/api.php` (`/internal/*` + `source` middleware), not in controllers.

### Testing Requirements
- `make test` from repo root (DB `wibu_store_test`); suite touching this layer: `Feature/OdooHookTest.php`.

## Dependencies

### Internal
- `App\Domain\Inventory\InventoryService`, `App\Domain\Odoo\OdooOrderPayload`, `App\Models\{Product, ProductVariant, InventoryLevel, Order}`

## Manual Notes
