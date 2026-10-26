<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-10-22 | Updated: 2026-10-22 -->

# types

## Purpose
Shared TypeScript types for the admin domain model. One file, no runtime code.

## Key Files
| File | Description |
|------|-------------|
| `index.ts` | Status unions (`OrderStatus`, `PaymentStatus`, `FulfillmentStatus`, `ProductPublishStatus`, `PreorderStatus`, `DepositStatus`) and entities: `Order`, `OrderLineItem`, `OrderTimelineEvent`, `AdminProduct`, `ProductVariantDetail`, `InventoryItem`, `InventoryAdjustment`, `PreorderBatch`, `PreorderBuyer`, `CMSSection`, `MediaAsset`, `Customer`, `Promotion`, `Review`, `TeamMember`, `AuditLog`, `TaskItem` |

## For AI Agents

### Working In This Directory
- Types only — no runtime values, no imports from `lib/` or `data/`. `data/mockData.ts` imports from here; the dependency never points back.
- These are the admin-facing shapes. The API returns different field names and envelopes — pages map API rows into their own local row types (e.g. `OrderRow`, `SaleRow`, `StaffRow` declared in each page's frontmatter) and only use `types/index.ts` where a shared contract exists.
- The status unions are closed. `components/domain/PaymentStatusBadge.astro` and `FulfillmentStatusBadge.astro` build a `Record<Status, {label, variant}>` from them, so a new backend status breaks the badge compile until the union and the map are both widened. `OrderStatusBadge.astro` types its map as `Record<string, …>` instead, so an order status the union does not cover still renders (neutral badge, raw label) rather than crashing.
- All three unions mirror the backend's `App\Domain\Common\Statuses` constants exactly. `OrderStatus` = `ORDER_STATUSES` (`pending`, `confirmed`, `processing`, `shipped`, `delivered`, `completed`, `cancelled`, `expired`) — `new` and `on_hold` are never sent by the API. `PaymentStatus` = `PAYMENT_STATUSES` (`unpaid`, `partially_paid`, `paid`, `refunded`, `partially_refunded`); `pending`/`failed` are payment-RECORD statuses (`PAYMENT_RECORD_STATUSES`), a different column, so they are not in the union. `FulfillmentStatus` = `FULFILLMENT_STATUSES` (`unfulfilled`, `preparing`, `shipped`, `delivered`, `returned`) — the backend sends `preparing`, not `picking`/`packed`. Widen the union AND the badge map together when the backend adds a status.
- `InventoryAdjustment.type` is a Vietnamese union (`'nhập' | 'điều chỉnh tăng' | 'điều chỉnh giảm' | 'hỏng' | 'kiểm kê'`) — the API stores movement types in Vietnamese, not English codes.
- Money fields are integer VND (`totalAmount`, `finalAmount`, `price`, `basePrice`, `depositAmount`, …). Format them with `lib/formatters.ts`.
- `CMSSection.settings` is `Record<string, any>` — it holds the freeform CMS payload for the homepage sections, so it is the one place without compile-time safety.

### Testing Requirements
- `pnpm build` in `frontend-admin/` does not typecheck. `pnpm exec tsc --noEmit` covers this file and `lib/*.ts`; `.astro` frontmatter is not covered.
- No test runner is configured.

### Common Patterns
- Add a domain entity here, then extend `data/mockData.ts` fixtures and the corresponding page row type together.
- Keep unions narrow — the exhaustiveness of the badge config maps is what keeps status colours consistent.

## Dependencies

### Internal
- `../data/mockData.ts` — the only consumer that still uses these types wholesale
- `../components/domain/*.astro` — status unions
- `../lib/formatters.ts` — formats the integer-VND fields

### External
- None.

<!-- MANUAL: -->
