<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-10-22 | Updated: 2026-10-22 -->

# analytics

## Purpose
The three read-only report screens of the admin: business overview (sales), product performance and inventory health. All of them are pure SSR reads — no writes, no pagination.

## Key Files
| File | Description |
|------|-------------|
| `index.astro` | Sales overview. `GET /admin/reports/sales`. Four `MetricCard` KPIs (net collected, GMV, order count, AOV) computed in frontmatter + a daily table + CSV export. |
| `products.astro` | Product performance. `GET /admin/products?per_page=50`. Table of name, series/maker, `min_price`, `stock.available`. CSV export writes `product-performance.csv`. |
| `inventory.astro` | Inventory health. `GET /admin/inventory?per_page=200`. Hand-rolled stat cards (tracked SKUs, low ≤3, out of stock) + a link to `/inventory`. |

## For AI Agents

### Working In This Directory
- Endpoints: `/admin/reports/sales` and `/admin/dashboard/*` are gated by `perm:reports.read`; `/admin/products` by `perm:products.read`; `/admin/inventory` by `perm:inventory.adjust`. A 403 here means the staff account lacks the permission.
- `/admin/reports/sales` accepts `days` (default 30, clamped to 1–90). The table uses the default 30 days; the CSV button fetches `?days=90` — the export therefore contains three times the rows shown on screen.
- Money is integer VND. `formatCurrency` is applied to `gmv`, `collected` and `aov`; `aov = round(gmv / orders)` is computed in the frontmatter, not by the API. Rows are `{day, orders, gmv, collected}` and `day` is rendered with a raw `toLocaleDateString('vi-VN')` instead of `formatDate`.
- `products.astro`'s "Xuất danh sách" button (`id="export-csv-btn"`) now exports: a `<script>` fetches `GET /admin/products?per_page=100` with `credentials: 'include'` and writes `product-performance.csv` (`San pham`, `Series`, `Hang`, `Gia tu (VND)`, `Ton kha dung`) through `downloadCSV`. It is the same client-side export pattern as `/customers`, `/orders`, `/products` and `/analytics` — see `../../lib/export.ts`. On failure it alerts `Xuat that bai. Kiem tra dang nhap.` `inventory.astro` still has no export at all, and the low-stock threshold there is hardcoded (`available <= 3`), not read from `/admin/settings`.
- `analytics/inventory.astro` imports `formatCurrency` but never uses it; its stat cards are hand-rolled rather than `domain/MetricCard.astro`.
- None of the three pages paginate — each fetches a fixed `per_page` and is truncated server-side at 50 (products) / 100 (inventory, backend cap).

### Testing Requirements
- `pnpm build` in `frontend-admin/`.
- Runtime: backend up (`make dev-backend`), then `make dev-admin`; sign in as `admin@wibustore.vn` / `Admin@123`.
- No test runner is configured.

## Dependencies

### Internal
- `../../lib/api.ts` — `apiGet`, `requireStaff`
- `../../lib/formatters.ts` — `formatCurrency`
- `../../lib/export.ts` — `downloadCSV` (`index.astro`, `products.astro`)
- `../../components/domain/MetricCard.astro`, `../../components/ui/Button.astro`
- `../../layouts/AdminLayout.astro`

### External
- Backend REST API `/api/v1/admin` at `PUBLIC_API_URL` (default `http://localhost:8000`) — Sanctum session auth + `perm:*` middleware

<!-- MANUAL: -->
