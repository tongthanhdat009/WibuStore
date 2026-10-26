<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-10-22 | Updated: 2026-10-22 -->

# products

## Purpose
The product detail page (PDP) — gallery, price, stock status, pre-order deposit, reviews and related products. Prerendered, one page per slug.

## Key Files
| File | Description |
|------|-------------|
| `[slug].astro` | `getStaticPaths()` from `getAllSlugs()`; body from `getProductBySlug(slug)`, related products from `getCatalogProducts({per_page: 12})`. Also fetches the pre-order campaign and the first page of reviews |

## For AI Agents

### Working In This Directory
- `getStaticPaths()` returns `getAllSlugs()` — the live API slugs when reachable, otherwise the 12 slugs in `src/data/products.ts`. The page body then re-fetches by slug through `getProductBySlug`; if that throws, it falls back to the static product and throws `Product not found: ${slug}` when even that misses.
- The pre-order campaign is read with a **raw** `fetch` to `${PUBLIC_API_URL}/api/v1/preorders`, not through `lib/api.ts`. It matches on `?preorder=<id>` in the query string, else on `product.defaultVariantId === campaign.variant_id`. The whole block is wrapped in try/catch, so a dead API just hides the deposit box.
- Reviews are also raw-fetched: `GET /api/v1/products/{slug}/reviews?per_page=6`, with `meta.stats` supplying `{count, average}`. The SSR result is passed to the client through `window.__PDP_DATA__` via a `define:vars` script.
- Review submission eligibility is checked client-side against `GET /api/v1/products/{slug}/reviews/eligibility` — 401 shows the login prompt, a non-eligible customer sees the "must purchase first" notice. The body must be at least 10 characters.
- The add-to-cart button reads the campaign id from the wrapper's `data-preorder-campaign` attribute and calls `addToCartItem(product, qty, campaignId)`; `syncItemToServer` then pushes the item to `POST /api/v1/cart/items` when the product carries a `variantId`.
- The quantity input is `readonly` and driven by `#pdp-qty-dec` / `#pdp-qty-inc`; the maximum is `product.stockCount || 10`.
- The wishlist button uses the `.wishlist-btn` class and is handled by the document-level delegation in `components/layout/SiteHeader.astro` — it does nothing if the header is not mounted.
- Related products are the first 4 of `getCatalogProducts({per_page: 12})` filtered to the same `seriesSlug` or `category` and excluding the current product. On API failure that call falls back to the whole static catalogue.
- Gallery zoom opens `#pdp-zoom-modal` on click of the image wrapper (the wrapper is `role="button"` with `tabindex="0"` but has no keydown handler, so Enter does not open it); the thumbnails are `.pdp-thumb-btn`.
- The price is integer VND formatted by `lib/utils.ts` → `formatVND`; `getStatusBadgeInfo` supplies the stock pill.
- Astro 5 renders Markdown pages, so this `AGENTS.md` is itself prerendered as a public route (`/products/AGENTS/`) with no layout or chrome around it.

### Testing Requirements
- `pnpm build` in `frontend/` with the backend up and down; both must produce a page per slug.
- Add-to-cart, quantity, wishlist and review submission need a browser — check `/products/<slug>` in `pnpm dev`.
- No test runner is configured.

## Dependencies

### Internal
- `../../lib/catalog.ts` — `getAllSlugs`, `getProductBySlug`, `getCatalogProducts`
- `../../lib/cart.ts` — `addToCartItem`, `syncItemToServer`; `../../lib/api.ts` — `API_URL`, XSRF helper
- `../../lib/utils.ts` — `formatVND`, `getStatusBadgeInfo`
- `../../data/products.ts` — static fallback products
- `../../components/catalog/ProductCard.astro`, `../../components/ui/Button.astro`, `../../components/ui/Breadcrumb.astro`, `../../components/comic/StickerBadge.astro`
- `../../layouts/BaseLayout.astro`

### External
- Backend `/api/v1/products`, `/api/v1/preorders`, `/api/v1/products/{slug}/reviews`, `/api/v1/cart/items` via `PUBLIC_API_URL` (default `http://localhost:8000`)

<!-- MANUAL: -->
