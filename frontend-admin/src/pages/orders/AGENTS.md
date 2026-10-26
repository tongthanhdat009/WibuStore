<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-10-22 | Updated: 2026-10-22 -->

# orders

## Purpose
The order list (filterable, tabbed, exportable) and the order detail screen with status transitions, payment confirmation and the invoice link.

## Key Files
| File | Description |
|------|-------------|
| `index.astro` | Order list. `GET /admin/orders` with `order_status`, `payment_status`, `fulfillment_status`, `checkout_type`, `q`; CSV export. |
| `[id].astro` | Order detail. `GET /admin/orders/{id}`; `POST /admin/orders/{id}/transition`; `POST /admin/payments/{id}/confirm`; invoice link `${API_URL}/api/v1/admin/orders/{id}/invoice`. |

## For AI Agents

### Working In This Directory
- All order endpoints are gated by `perm:orders.transition`; payment confirmation needs `perm:payments.confirm`. A 403 on the transition call means the account lacks that permission, not that the transition is invalid.
- The list status columns use `domain/OrderStatusBadge`, `domain/PaymentStatusBadge` and `domain/FulfillmentStatusBadge`. Do not replace them with raw badges.
- The tab row is plain links to `?status=…`; the "Tất cả" tab count is the number of rows on the current page, not the total. The CSV export does its own client-side fetch of `?per_page=100`, so it ignores the active filters and is capped at 100 rows.
- The detail screen builds its transition buttons from a client-side `NEXT` map (`pending → confirmed|cancelled|expired`, `confirmed → processing|cancelled`, `processing → shipped|cancelled`, `shipped → delivered`, `delivered → completed`). That map is a UI whitelist only — the backend owns the real rules — and the page also confirms before moving an unpaid order to `shipped`/`delivered`/`completed`.
- Order status and payment status are independent: approving money is the "Duyệt" button on the payment row (`POST /admin/payments/{id}/confirm`), changing the order is a header button.
- The invoice is a direct link to the backend (`${API_URL}/api/v1/admin/orders/{id}/invoice`), not an admin page — the browser must carry the Sanctum session cookie to the API origin.
- `types/index.ts` declares `OrderStatus` as the backend's 8 real statuses — `pending | confirmed | processing | shipped | delivered | completed | cancelled | expired` (source of truth: `App\Domain\Common\Statuses::ORDER_STATUSES`). `new` and `on_hold` are never sent by the API and are in neither the union nor the badge map any more; an unrecognised status still renders as a neutral badge showing the raw value.

### Testing Requirements
- `pnpm build` in `frontend-admin/`.
- Runtime: backend up; exercise a transition and a payment confirmation against a seeded order.
- No test runner is configured.

## Dependencies

### Internal
- `../../lib/api.ts` — `apiGet`, `currentPage`, `requireStaff`, `mutate`, `API_URL`
- `../../lib/formatters.ts` — `formatCurrency` (integer VND), `formatDateTime`
- `../../lib/export.ts` — `downloadCSV`
- `../../components/domain/OrderStatusBadge.astro`, `PaymentStatusBadge.astro`, `FulfillmentStatusBadge.astro`
- `../../components/ui/Pagination.astro`, `../../components/ui/Button.astro`, `../../components/ui/Badge.astro`
- `../../layouts/AdminLayout.astro`

### External
- Backend REST API `/api/v1/admin` at `PUBLIC_API_URL` (default `http://localhost:8000`) — Sanctum session auth + `perm:*` middleware

<!-- MANUAL: -->
