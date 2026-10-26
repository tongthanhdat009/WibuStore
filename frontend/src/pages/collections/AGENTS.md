<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-10-22 | Updated: 2026-10-22 -->

# collections

## Purpose
Curated-collection landing pages: an index of every collection and one detail page per collection.

## Key Files
| File | Description |
|------|-------------|
| `index.astro` | Collection list. Static `src/data/collections.ts` plus a raw `GET /api/v1/collections`; cover images come from `getProductBySlug` on the first products of each collection |
| `[slug].astro` | Collection detail. `getStaticPaths()` unions the static slugs with the API slugs; the body comes from `GET /api/v1/collections/{slug}` + `getProductBySlug` |

## For AI Agents

### Working In This Directory
- These are the only pages that call `/api/v1/collections` with a raw `fetch` instead of going through `lib/catalog.ts`. Both files wrap the call in try/catch and fall back to `src/data/collections.ts`.
- The API stores collections as CMS pages with a `collection-` slug prefix, so both pages strip it with `.replace(/^collection-/, '')` before matching against the static slugs. The detail endpoint re-adds the prefix server-side.
- `index.astro` maps an API collection to a partial object: `productSlugs` is always `[]` and `tagline`/`description` come from the static file. The index only renders badge, title, tagline, description and up to 2 preview images.
- The index makes one `GET /api/v1/collections/{slug}` plus up to 2 `getProductBySlug` calls per collection — it is N+1 heavy at build time.
- `[slug].astro` throws `Collection not found: ${slug}` when the slug is in neither the static set nor the live payload. That is a build failure, not a 404 page.
- `getStaticPaths()` passes `props: { collection }` but the page body ignores `Astro.props` entirely — it re-reads `Astro.params.slug` and re-fetches. Changing the props contract alone has no effect.
- In `[slug].astro` the static products are resolved from `src/data/products.ts` by `collection.productSlugs`; live products are resolved with `getProductBySlug` and capped at 12 slugs.
- Two static `productSlugs` entries (`geto-suguru-figma-512`, `kaido-dragon-form-figuarts-zero`) have no matching product in `src/data/products.ts` — they only resolve when the API is up.
- The detail page passes `products={collectionProducts}` to `ProductGrid` with no `emptyMessage`, so an empty collection shows the default empty state.
- Astro 5 renders Markdown pages, so this `AGENTS.md` is itself prerendered as a public route (`/collections/AGENTS/`) with no layout or chrome around it.

### Testing Requirements
- `pnpm build` in `frontend/` with the backend up and down; both must produce the index and one page per slug.
- No test runner is configured.

## Dependencies

### Internal
- `../../data/collections.ts`, `../../data/products.ts` — static collections and their products
- `../../lib/catalog.ts` — `getProductBySlug`
- `../../components/catalog/ProductGrid.astro`, `../../components/ui/Breadcrumb.astro`, `../../components/ui/Button.astro`, `../../components/comic/StickerBadge.astro`
- `../../layouts/BaseLayout.astro`

### External
- Backend `/api/v1/collections` (CMS pages) and `/api/v1/products/{slug}` via `PUBLIC_API_URL` (default `http://localhost:8000`)

<!-- MANUAL: -->
