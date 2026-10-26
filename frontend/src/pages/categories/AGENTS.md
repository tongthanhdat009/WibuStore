<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-10-22 | Updated: 2026-10-22 -->

# categories

## Purpose
Category landing pages — one prerendered page per figure category, with an accent-coloured header and the products in that category.

## Key Files
| File | Description |
|------|-------------|
| `[slug].astro` | `getStaticPaths()` unions the API taxonomy slugs with `src/data/categories.ts`; the page reads `fetchTaxonomy('/categories')` + `getCatalogProducts({category: slug, per_page: 48})` and renders a `ProductGrid` |

## For AI Agents

### Working In This Directory
- The slug set is the union of `fetchTaxonomy('/categories')` and `src/data/categories.ts`, so a page exists for every API category and every static category — the build must pass with the backend up and down.
- `fetchTaxonomy` is called once in `getStaticPaths` plus once again in every page body — N+1 API calls per build, with no caching between them.
- `accentColor`, `badge` and `englishTitle` come **only** from `src/data/categories.ts`; the API taxonomy returns just `name` and `slug`. A category that exists only in the API renders with the `#FFD44A` default accent and the `CATALOG` / `CATEGORY` placeholders.
- The accent colour is applied as `background-color: ${accentColor}30` — a `30` alpha suffix appended to the 6-digit hex from `src/data/categories.ts` to make an 8-digit hex.
- There is exactly ONE category slug namespace. The backend migration `2026_10_22_100800_unify_category_slugs.php` renamed the seeded categories to the storefront vocabulary and folded the duplicate "Scale Figure" rows into one, so the API category slugs are `scale-figures`, `nendoroid`, `action-figures`, `model-kits` — the same strings as `src/data/categories.ts`. `GET /api/v1/categories` returns them ordered by `sort_order` (1..4), and `CATEGORY_MAP` in `lib/api.ts` maps exactly these. The old seeded slugs (`scale-figure`, `scale-figure-seed`, `chibi-seed`, `cosplay-seed`, `set-figure-seed`) are gone.
- `getCatalogProducts` falls back to the static catalogue ONLY when the API is unreachable (`fetchProducts` returned `null`). A reachable API that returns zero rows renders an empty grid, and the offline fallback itself is filtered with the same `category` param, so it shows only that category's fixtures.
- The header count is `categoryProducts.length`, so it matches the grid: the number of products in that category, in both the live and the offline path.
- Astro 5 renders Markdown pages, so this `AGENTS.md` is itself prerendered as a public route (`/categories/AGENTS/`) with no layout or chrome around it.

### Testing Requirements
- `pnpm build` in `frontend/` with the backend up and down; both must produce a page per slug.
- No test runner is configured.

## Dependencies

### Internal
- `../../lib/catalog.ts` — `fetchTaxonomy`, `getCatalogProducts`
- `../../data/categories.ts` — accent colour, badge, English title
- `../../components/catalog/ProductGrid.astro`, `../../components/ui/Breadcrumb.astro`, `../../components/comic/StickerBadge.astro`
- `../../layouts/BaseLayout.astro`

### External
- Backend `/api/v1/categories` and `/api/v1/products` via `PUBLIC_API_URL` (default `http://localhost:8000`)

<!-- MANUAL: -->
