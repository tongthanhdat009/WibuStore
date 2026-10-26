<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-10-22 | Updated: 2026-10-22 -->

# catalog

## Purpose
The two product-listing primitives: the tile (`ProductCard`) and the responsive grid with the empty state (`ProductGrid`). Every catalog surface renders through them.

## Key Files
| File | Description |
|------|-------------|
| `ProductCard.astro` | Product tile. Props `product: Product`, `className?`. Emits `data-product-id`, `data-category`, `data-series`, `data-status`, `data-price` for the `/figures` filter script |
| `ProductGrid.astro` | Props `products: Product[]`, `emptyMessage?`, `className?`. 2/3/4-column grid (`grid-cols-2 md:grid-cols-3 xl:grid-cols-4`) or the "KHÔNG CÓ DỮ LIỆU" empty state linking to `/figures` |

## For AI Agents

### Working In This Directory
- `data-category` holds the `FigureCategoryType` id (`scale`, `nendoroid`, `action`, `model_kit`), **not** the storefront slug (`scale-figures`); `data-series` holds `seriesSlug`. `/figures` filters and sorts on these attributes plus `data-price` — renaming or dropping one silently breaks the catalog.
- The card has no `<script>` of its own. Its `.quick-add-btn` (carrying `data-product-json`) and `.wishlist-btn` are handled by the document-level delegation in `../layout/SiteHeader.astro`; without that header the buttons do nothing.
- `.quick-add-btn` serialises the whole product into `data-product-json`; the header parses it, calls `addToCartItem(product, 1)` and opens the cart drawer. Sold-out cards render a "Hết hàng" label instead of the button.
- The wishlist toggle repaints by adding/removing `text-[#D6284F]` and `bg-[#FFE6ED]` on the button and flipping `fill` on the `.heart-icon` svg.
- Price typography is container-query driven: the root is `@container` and the price steps at `@min-[270px]` / `@min-[330px]`.
- Status pills use `../comic/StickerBadge.astro`, not `../ui/Badge.astro`. `getStatusBadgeInfo` is imported in the card but its result is never rendered — dead code.
- `ProductGrid` ignores `className` in the empty-state branch.
- `getCatalogProducts` returns `{ products, live }`, but no page reads `live` — they destructure `products` only. The grid therefore cannot tell a live result from the fallback, and the fallback list is the **unfiltered** static catalogue. Do not assume "same products, different source".

### Testing Requirements
- `pnpm build` in `frontend/`; after touching the `data-*` attributes, re-check `/figures` filtering, sorting and pagination.

## Dependencies

### Internal
- `../types/index.ts` — `Product`
- `../lib/utils.ts` — `formatVND` (integer VND, zero decimals)
- `../comic/StickerBadge.astro` — status / NEW / discount stickers
- `../layout/SiteHeader.astro` — owns the delegated quick-add and wishlist handlers

### External
- `astro@^5.4.2`, `tailwindcss@^4.0.9`

<!-- MANUAL: -->
