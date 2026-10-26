<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-10-22 | Updated: 2026-10-22 -->

# data

## Purpose
Legacy fixture data from before the admin was wired to the API. It is kept as a type reference for the admin domain, but no page imports it.

## Key Files
| File | Description |
|------|-------------|
| `mockData.ts` | ~900 lines of fixtures, all typed against `../types/index.ts`. Exports `mockOrders`, `mockProducts`, `mockInventory`, `mockAdjustments`, `mockPreorders`, `mockCMSSections`, `mockMediaAssets`, `mockCustomers`, `mockPromotions`, `mockReviews`, `mockTeamMembers`, `mockAuditLogs`, `mockTasks`, `mockCategories`, `mockSeries`, `mockBrands`, `mockCollections` |

## For AI Agents

### Working In This Directory
- `mockData.ts` is imported by zero pages. The admin is API-wired: 32 of the 35 pages read `/api/v1/admin` through `lib/api.ts`. Do not add a mock import to a page — wire the endpoint instead.
- The file's real value now is as a shape reference: it shows what a fully-populated `Order`, `AdminProduct`, `InventoryItem`, `PreorderBatch`, `Customer`, `Review`, `Promotion`, `MediaAsset`, `TeamMember`, `AuditLog` or `TaskItem` looks like.
- Every fixture is typed against `../types/index.ts`. If you change a shared type, this file is where the breakage shows up first — it is a useful compile-time check on the type changes.
- The fixture order statuses follow the backend union: `mockOrders` use `pending`, `processing`, `completed` and `cancelled`. `new` was dropped when `OrderStatus` was corrected — the API never sends it.
- Values are illustrative and inconsistent with live data (fake ids, VND amounts, fixed timestamps). Never copy a value from here into production behaviour.
- If the file is eventually deleted, the admin types it depends on must still be referenced from `../types/index.ts` — that file is the durable contract.

### Testing Requirements
- `pnpm build` in `frontend-admin/` — Astro only bundles what is imported, so an unreferenced fixture file does not affect the build.
- `pnpm exec tsc --noEmit` will typecheck it, which is useful as a canary for `types/index.ts` changes.
- No test runner is configured.

### Common Patterns
- Need an example of a populated entity → read `mockData.ts` for the shape, then use the real API response.
- Need a new entity type → add it to `../types/index.ts`, then (optionally) seed a fixture here.

## Dependencies

### Internal
- `../types/index.ts` — the only import; every fixture is typed against it
- `../pages/**` — no page imports this directory today

### External
- None.

<!-- MANUAL: -->
