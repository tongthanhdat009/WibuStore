<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-10-22 | Updated: 2026-10-22 -->

# anime

## Purpose
Series landing pages — one prerendered page per anime franchise, with a banner and the products tagged to that series.

## Key Files
| File | Description |
|------|-------------|
| `[slug].astro` | `getStaticPaths()` unions the API taxonomy slugs with `src/data/anime.ts`; the page reads `fetchTaxonomy('/anime-series')` + `getCatalogProducts({anime_series: slug, per_page: 48})` and renders a `ProductGrid` |

## For AI Agents

### Working In This Directory
- The slug set is the union of `fetchTaxonomy('/anime-series')` and `src/data/anime.ts`, so a page exists for every API series and every static series — the page must build with the backend up and down.
- `fetchTaxonomy` is called once in `getStaticPaths` plus once again in every page body — N+1 API calls per build, with no caching between them.
- The title resolves API-first (`foundSeries.name` → static `title` → the raw slug), but the banner image, Japanese title and long description come **only** from `src/data/anime.ts`. A series that exists in the API but not in the static data renders with no banner image (the halftone fallback), no Japanese title and a generated description.
- The product query uses `anime_series: slug` — the API matches it against the *series* slug, not the storefront category slug.
- `getCatalogProducts` falls back to the whole static catalogue (`src/data/products.ts`) whenever the query yields no rows — the API being unreachable *or* the series having no products. The grid then renders all 12 fallback products, unfiltered by series.
- Astro 5 renders Markdown pages, so this `AGENTS.md` is itself prerendered as a public route (`/anime/AGENTS/`) with no layout or chrome around it. The same applies to every `AGENTS.md` under `src/pages/`.
- The `StickerBadge` label is hardcoded to `OFFICIAL FRANCHISE`; only the title, title suffix and description vary per series.
- The banner image is dimmed with `opacity-45` behind a gradient, so a bright source image still keeps the white heading legible.

### Testing Requirements
- `pnpm build` in `frontend/` with the backend up and down; both must produce a page per slug.
- No test runner is configured.

## Dependencies

### Internal
- `../../lib/catalog.ts` — `fetchTaxonomy`, `getCatalogProducts`
- `../../data/anime.ts` — static banner, Japanese title, description
- `../../components/catalog/ProductGrid.astro`, `../../components/ui/Breadcrumb.astro`, `../../components/comic/StickerBadge.astro`
- `../../layouts/BaseLayout.astro`

### External
- Backend `/api/v1/anime-series` and `/api/v1/products` via `PUBLIC_API_URL` (default `http://localhost:8000`)

<!-- MANUAL: -->
