<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-10-22 | Updated: 2026-10-22 -->

# data

## Purpose
Static fixture datasets that let the storefront build and render when the Laravel API is offline. Each file exports one typed array.

## Key Files
| File | Description |
|------|-------------|
| `products.ts` | `products: Product[]` — 12 seeded figures with integer-VND prices, images, specifications, tags, stock status |
| `categories.ts` | `categories: CategoryInfo[]` — 4 panels (`scale-figures`, `nendoroid`, `action-figures`, `model-kits`) with accent colours and item counts. These slugs are also the API category slugs |
| `anime.ts` | `animeSeriesList: AnimeSeries[]` — 4 series entries (`jujutsu-kaisen`, `one-piece`, `demon-slayer`, `spy-x-family`) linking to `/anime/{slug}`. Chỉ giữ series có sản phẩm trong DB; `chainsaw-man`/`evangelion` đã bỏ vì DB chưa có hàng. Slug phải khớp `anime_series.slug` của API (VD `spy-x-family`, không phải `spy-family`) nếu không trang series trắng |
| `collections.ts` | `collections: Collection[]` — 3 editorial collections, each holding `productSlugs` |
| `heroCampaigns.ts` | `heroCampaigns: HeroCampaign[]` — 4 hero slides, each with `productSlug`, panel colour and CTA links |

## For AI Agents

### Working In This Directory
- These arrays are the fallback tier, not the source of truth — the API wins whenever it is reachable. Edit them to keep the offline build coherent, not to change live behaviour.
- Every record is typed against `../types/index.ts`; add the field to the type before adding it to a fixture.
- Cross-file slug references must stay consistent: `collections.ts` → `productSlugs` → `products.ts` `slug`, and `heroCampaigns.ts` → `productSlug` → `products.ts` `slug`. A dangling slug yields an empty collection strip, not a crash (the collection pages filter and catch).
- Two collection slugs currently have no matching product in `products.ts`: `geto-suguru-figma-512` and `kaido-dragon-form-figuarts-zero`. They only resolve when the API is up.
- `categories.ts` slugs are storefront URLs (`/categories/{slug}`) **and** the API category slugs. The backend migration `2026_10_22_100800_unify_category_slugs.php` renamed the seeded categories to this vocabulary, so `CATEGORY_MAP` in `lib/api.ts` maps exactly these strings and a category page's API query now matches.
- The fixtures' `status` uses the API's `availability` vocabulary (`in_stock`, `pre_order`, `sold_out`) — the same values `lib/api.ts` → `mapAvailability` derives from the API.
- Prices are integer VND (e.g. `5850000`), never preformatted strings — formatting happens at render time via `lib/utils.ts` → `formatVND`.
- Images are remote Unsplash URLs with width/quality params baked into the query string.
- `heroCampaigns.ts` campaign ids are `campaign-01` … `campaign-04`; `ComicCoverHero.astro` reads `campaigns[0]` at render time, so the first entry always drives the hero.

### Testing Requirements
- `pnpm build` in `frontend/` with the backend stopped — that is the only way to exercise this tier.
- No test runner is configured.

### Common Patterns
- Adding a product: copy an existing entry in `products.ts`, keep `id` and `slug` unique, and set `status`, `price`, `images` and `specifications`.
- Adding a category or series: append to the array — `SiteHeader.astro`, `Footer.astro` and the homepage read these arrays directly.

## Dependencies

### Internal
- `../types/index.ts` — `Product`, `CategoryInfo`, `AnimeSeries`, `Collection`, `HeroCampaign`
- Consumers: `lib/catalog.ts` (fallback), `components/layout/SiteHeader.astro`, `components/layout/Footer.astro`, `pages/index.astro`, `pages/figures.astro`, `pages/wishlist.astro`, `pages/collections/index.astro`, `pages/collections/[slug].astro`, `pages/anime/[slug].astro`, `pages/categories/[slug].astro`

### External
- None at build time; images are fetched at runtime from the Unsplash CDN.

<!-- MANUAL: -->
