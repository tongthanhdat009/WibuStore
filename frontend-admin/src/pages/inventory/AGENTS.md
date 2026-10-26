<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-10-22 | Updated: 2026-10-22 -->

# inventory

## Purpose
The stock screens: the current level per SKU with the adjustment modal, and the read-only movement ledger that records every stock change.

## Key Files
| File | Description |
|------|-------------|
| `index.astro` | Stock levels. `GET /admin/inventory?per_page=20&page=`; adjust via `POST /admin/inventory/adjustments`. |
| `adjustments.astro` | Movement ledger. `GET /admin/inventory/movements?per_page=20&page=` — read-only. |

## For AI Agents

### Working In This Directory
- Both endpoints are gated by `perm:inventory.adjust`.
- The level rows are `{id, warehouse_id, variant_id, sku, label, product_name, on_hand, reserved, available}`. `available` is computed server-side as `max(0, on_hand - reserved)`.
- The adjustment payload is `{variant_id, delta_on_hand, reason}`. The backend requires `delta_on_hand != 0` and a non-empty `reason`; `warehouse_id` is optional and defaults to the primary warehouse. Every adjustment writes an inventory movement and an `inventory.adjusted` activity log.
- The modal's variant `<select>` is built from the rows on the **current page only** (20 of them). A SKU that is not on this page cannot be adjusted from here.
- The low-stock badge is derived in the page (`available <= 0` → `out_of_stock`, `<= 3` → `low_stock`), and the threshold is hardcoded — it does not read `low_stock_threshold` from `/admin/settings`.
- The ledger is append-only from the admin's point of view: there is no undo or reversal endpoint, only new adjustments. Its delta column shows `delta_reserved` when `delta_on_hand === 0`, and the "Lý do" column prints `reference_type:reference_id` verbatim.
- `adjustments.astro` uses `formatDateTime`; `index.astro` imports `formatDateTime` but does not use it.

### Testing Requirements
- `pnpm build` in `frontend-admin/`.
- Runtime: backend up; adjust a SKU, then confirm the new row in the ledger.
- No test runner is configured.

## Dependencies

### Internal
- `../../lib/api.ts` — `apiGet`, `currentPage`, `requireStaff`, `mutate`
- `../../lib/formatters.ts` — `formatDateTime`
- `../../components/domain/InventoryLevelBadge.astro` — the only sanctioned stock-level renderer
- `../../components/ui/Pagination.astro`, `../../components/ui/Button.astro`, `../../components/ui/Badge.astro`
- `../../layouts/AdminLayout.astro`

### External
- Backend REST API `/api/v1/admin` at `PUBLIC_API_URL` (default `http://localhost:8000`) — Sanctum session auth + `perm:*` middleware

<!-- MANUAL: -->
