<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-10-22 | Updated: 2026-10-22 -->

# types

## Purpose
Shared TypeScript types for the storefront domain model. One file, no runtime code.

## Key Files
| File | Description |
|------|-------------|
| `index.ts` | `StockStatusType` (`in_stock` / `pre_order` / `sold_out` / `coming_soon`), `FigureCategoryType` (`scale` / `nendoroid` / `action` / `model_kit`), `Product`, `ProductVariant`, `HeroCampaign`, `CategoryInfo`, `AnimeSeries`, `Collection`, `CartItem`, `FilterOptions` |

## For AI Agents

### Working In This Directory
- Types only — no runtime values and no imports from `lib/` or `data/`. `data/*.ts` imports from here; the dependency never points back.
- `Product` is the storefront-facing shape. API payloads use different field names (`price_amount`, `series.name`, `variants[]`); the conversion happens once in `lib/api.ts` → `toStoreProduct`. Do not spread API shapes into components.
- `FigureCategoryType` is a closed union. API category slugs are translated into it by `CATEGORY_MAP` in `lib/api.ts` — extend that map rather than widening this union. The map now holds only the canonical slugs (`scale`, `scale-figures`, `nendoroid`, `action`, `action-figures`, `model_kit`, `model-kits`); the old seeded slugs were renamed by a backend migration, so there is one slug namespace.
- `StockStatusType` is a closed union. The API's `availability` covers three of the four values (`in_stock` / `sold_out` / `pre_order`); `coming_soon` is storefront-only and the API never sends it. `lib/api.ts` → `mapAvailability` folds the API value into the union (`sold_out`/`pre_order` pass through, anything else → `in_stock`), `lib/utils.ts` → `getStatusBadgeInfo` handles all four, and `ui/Badge.astro` colours only the three the API sends. Adding a fifth status means updating all three.
- `CartItem` is the localStorage shape and differs from `Product`: it carries `quantity`, `variantId`, `preorderCampaignId` and `isPreOrder`.
- `ProductVariant` is declared but is not what the API returns — the API variant shape is `ApiVariant` in `lib/api.ts`.
- `FilterOptions` describes the `/figures` filter UI. The actual filtering runs client-side over `ProductCard`'s `data-*` attributes, not from this type.

### Testing Requirements
- `pnpm build` in `frontend/` does not typecheck. `pnpm exec tsc --noEmit` catches errors in `.ts` files; `.astro` frontmatter is not covered by it.
- No test runner is configured.

### Common Patterns
- Add a domain type here, then update the fixture in `src/data/` and the mapper in `lib/api.ts` together.
- Keep unions narrow — `switch` statements over these unions are exhaustive by construction, which is what keeps badge colours consistent.

## Dependencies

### Internal
- `../data/*.ts` — typed static fixtures
- `../lib/api.ts` — `toStoreProduct` returns `Product`; `ApiProductItem`/`ApiProductDetail` are the input shapes
- `../lib/catalog.ts` — returns `Product[]`
- `../components/**` and `../pages/**` — consume these types

### External
- None.

<!-- MANUAL: -->
