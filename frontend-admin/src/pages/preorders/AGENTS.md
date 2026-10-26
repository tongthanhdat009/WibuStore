<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-10-22 | Updated: 2026-10-22 -->

# preorders

## Purpose
Pre-order campaign management: create a campaign, open/close the deposit window, and track the buyers who reserved a slot.

## Key Files
| File | Description |
|------|-------------|
| `index.astro` | Campaign cards. `GET /admin/preorder-campaigns?per_page=20` plus `GET /admin/inventory?per_page=200` for the variant picker; create `POST /admin/preorder-campaigns`; open/close `POST /admin/preorder-campaigns/{id}/open` and `/close`. |
| `[id].astro` | Campaign detail. `GET /admin/preorder-campaigns/{id}`; open/close; `POST /admin/preorder-reservations/{id}/balance-due`. |

## For AI Agents

### Working In This Directory
- All endpoints are gated by `perm:preorders.manage`.
- The create payload is `{variant_id, quota, deposit_per_unit_amount, sale_price_per_unit_amount}` — integers in VND, no decimals. The backend also accepts `opens_at`, `closes_at`, `expected_release_at`, `expected_arrival_at`, but the modal does not send them. The campaign is created in `draft` status; opening it is a separate action.
- The variant `<select>` is populated from `GET /admin/inventory?per_page=200`, so only SKUs present in the first 200 inventory rows can be chosen.
- Campaign status labels come from a local `statusConfigs` map in `index.astro` (`draft`, `open`, `closed`, `arrived`, `cancelled`). There is no domain badge for pre-order status — `domain/` does not cover it.
- `open` sets `opens_at` to now when it is empty; `close` sets `closes_at = now()`.
- Detail returns `reservations[]` (capped at 100 server-side) with `order_id`, `order_number`, `customer_name`, `deposit_required_amount` and `balance_due_amount`.
- "Báo thu phần còn lại" calls `POST /admin/preorder-reservations/{id}/balance-due`. The backend accepts `due_days` (default 14) but the page does not send it.
- The reservation status badge is a raw `ui/Badge.astro` (deposit_paid/completed → success, awaiting_deposit → danger, else warning).

### Testing Requirements
- `pnpm build` in `frontend-admin/`.
- Runtime: backend up; create a campaign, open it, then check a reservation on the detail screen.
- No test runner is configured.

## Dependencies

### Internal
- `../../lib/api.ts` — `apiGet`, `requireStaff`, `mutate`
- `../../lib/formatters.ts` — `formatCurrency` (integer VND), `formatDate`
- `../../components/ui/Button.astro`, `../../components/ui/Badge.astro`
- `../../layouts/AdminLayout.astro`

### External
- Backend REST API `/api/v1/admin` at `PUBLIC_API_URL` (default `http://localhost:8000`) — Sanctum session auth + `perm:*` middleware

<!-- MANUAL: -->
