<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-10-22 | Updated: 2026-10-22 -->

# domain

## Purpose
Domain-specific admin widgets: the status badges for order, payment and fulfillment state, the inventory level badge, and the KPI metric card. Every one of them is a thin wrapper over `ui/Badge.astro` or a styled card, and they are the only sanctioned way to show a status in the admin.

## Key Files
| File | Description |
|------|-------------|
| `OrderStatusBadge.astro` | Props `status: OrderStatus`, `size`. Maps the value to a Vietnamese label and a `ui/Badge` variant over exactly the 8 backend statuses (`pending`/`confirmed` → info, `processing`/`shipped` → warning, `delivered`/`completed` → success, `cancelled` → danger, `expired` → neutral). The map is a `Record<string, …>` with a `config[status] \|\| {label: status}` fallback, so an unknown status still renders as a neutral badge showing the raw string |
| `PaymentStatusBadge.astro` | Props `status: PaymentStatus`, `size`. `unpaid` → danger, `partially_paid` → warning, `paid` → success, `refunded`/`partially_refunded` → neutral. The union covers the ORDER's `payment_status` only — `pending`/`failed` are payment-RECORD statuses (`Statuses::PAYMENT_RECORD_STATUSES`) and are not in it |
| `FulfillmentStatusBadge.astro` | Props `status: FulfillmentStatus`, `size`. `unfulfilled` → neutral, `preparing` → info, `shipped` → warning, `delivered` → success, `returned` → danger. The backend sends `preparing` (not `picking`/`packed`), so the union and the map must both use it or the badge renders the raw string |
| `InventoryLevelBadge.astro` | Props `status: 'ok' \| 'low_stock' \| 'out_of_stock'` and `available: number`. Renders a coloured dot plus the count ("Còn hàng (n)", "Sắp hết (n)", "Hết hàng (0)") |
| `MetricCard.astro` | KPI tile. Props `title`, `value`, `subtext`, `trend` (`{value, positive}`), `link`, `badge`, `badgeVariant` (brand/warning/danger/success) |

## For AI Agents

### Working In This Directory
- Use these components for any status or KPI display. Inline colour classes for a status are a regression — the backend can return values the pages have never seen, and the badge config is the single place that maps them.
- Each badge declares its own `config` map and falls back to a neutral badge showing the raw value when the key is missing. `PaymentStatusBadge`/`FulfillmentStatusBadge` type theirs as `Record<Status, {label, variant}>`; `OrderStatusBadge` uses `Record<string, …>` so a status the union does not cover still renders. Extend the map rather than special-casing a value in a page.
- `OrderStatusBadge`, `PaymentStatusBadge` and `FulfillmentStatusBadge` import their union type from `../../types/index.ts`. A new backend status means widening that union first, then adding the map entry.
- `InventoryLevelBadge` is the one component that takes a number alongside the status — it prints `available` in the label, so the page must pass the available count, not the on-hand count.
- `MetricCard` takes pre-formatted strings. Format money with `lib/formatters.ts` → `formatCurrency` before passing it as `value`; the card does no formatting of its own.

### Testing Requirements
- `pnpm build` in `frontend-admin/`.
- Badge changes are visual — check a page that renders that status (e.g. `/orders` for order/payment badges, `/inventory` for the level badge).
- No test runner is configured.

### Common Patterns
- Page renders `<OrderStatusBadge status={order.orderStatus} size="sm" />` — pass the raw API value, never a pre-labelled string.
- One card per KPI on the dashboard: `<MetricCard title="Doanh thu" value={formatCurrency(x)} trend={{value: '+12%', positive: true}} />`.
- Need a colour that no badge covers? Add a variant to `ui/Badge.astro` rather than styling here.

## Dependencies

### Internal
- `../ui/Badge.astro` — the pill every badge delegates to
- `../../types/index.ts` — `OrderStatus`, `PaymentStatus`, `FulfillmentStatus`
- `../../lib/formatters.ts` — formats values before they reach `MetricCard`

### External
- `astro@^5.4.2`, `tailwindcss@^4.0.9`

<!-- MANUAL: -->
