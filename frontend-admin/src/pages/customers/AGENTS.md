<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-10-22 | Updated: 2026-10-22 -->

# customers

## Purpose
The collector (customer) list and the per-customer profile: contact data, lifetime spend and the recent order history.

## Key Files
| File | Description |
|------|-------------|
| `index.astro` | Customer list. `GET /admin/customers?per_page=20&page=&q=` with a search form and CSV export. |
| `[id].astro` | Customer profile. `GET /admin/customers/{id}` → `{user, stats, orders}`; shows addresses, lifetime spend and the order history. |

## For AI Agents

### Working In This Directory
- Both endpoints are gated by `perm:orders.transition` — there is no dedicated customer permission. A 403 here means the account cannot manage orders.
- The list endpoint returns only `{id, public_id, name, email, phone, status}` — spend, tier and order count are **not** in the list payload. `status` is the raw account status (`active`/`blocked`), rendered through `ui/Badge.astro` as "Hoạt động" or the raw value.
- The `q` search matches name and email server-side (`ilike`). The input placeholder also advertises phone search — phone is not searched.
- Detail: `stats.total_spent` sums `total_amount` over all orders except `cancelled` and `expired`; `stats.orders_count` and the `orders` array are capped at 20 by the backend.
- Detail renders order status with a raw `ui/Badge.astro` (completed → success, cancelled → danger, everything else → warning) instead of `domain/OrderStatusBadge.astro`. That is an inconsistency with the rest of the admin — do not copy it into new code.
- The CSV export issues its own client-side fetch of `?per_page=100`, so it ignores the active search and page and is capped at 100 rows. It is the shared client-side export pattern (`id="export-csv-btn"` → `fetch` with `credentials: 'include'` → `downloadCSV` from `../../lib/export.ts`), also used by `/orders`, `/products`, `/analytics`, `/analytics/products` and `/settings/audit-logs`. `formatDate` is imported in both files but never used.

### Testing Requirements
- `pnpm build` in `frontend-admin/`.
- Runtime: backend up, then open `/customers` and a profile from the list.
- No test runner is configured.

## Dependencies

### Internal
- `../../lib/api.ts` — `apiGet`, `currentPage`, `requireStaff`
- `../../lib/formatters.ts` — `formatCurrency` (money is integer VND)
- `../../lib/export.ts` — `downloadCSV`
- `../../components/ui/Pagination.astro`, `../../components/ui/Button.astro`, `../../components/ui/Badge.astro`
- `../../layouts/AdminLayout.astro`

### External
- Backend REST API `/api/v1/admin` at `PUBLIC_API_URL` (default `http://localhost:8000`) — Sanctum session auth + `perm:*` middleware

<!-- MANUAL: -->
