<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-10-22 | Updated: 2026-10-22 -->

# components

## Purpose
Presentational `.astro` components for the storefront, split into five families: site chrome (`layout/`), product tiles (`catalog/`), comic/pop editorial blocks (`comic/`), the cart slide-over (`cart/`), and generic primitives (`ui/`).

## Key Files
| File | Description |
|------|-------------|
| `layout/AnnouncementBar.astro` | Top promo strip; hardcoded October promo, code `WIBUPOP` |
| `layout/SiteHeader.astro` | Sticky header — brand mark, desktop nav, anime megamenu, search, cart button. Reads `data/categories.ts` and `data/anime.ts` directly |
| `layout/Footer.astro` | Dark footer — brand block, category links, policy links, payment/social row |
| `catalog/ProductCard.astro` | Product tile. Emits `data-product-id`, `data-category`, `data-series`, `data-status`, `data-price` for the `/figures` filter script |
| `catalog/ProductGrid.astro` | Responsive grid (2/3/4 columns) plus the "no data" empty state; renders one `ProductCard` per product |
| `cart/CartDrawer.astro` | Global cart slide-over — overlay + panel, count badge, free-shipping progress, empty state. Populated by client JS from `lib/cart.ts` |
| `comic/*.astro` | Comic editorial blocks — see `comic/AGENTS.md` |
| `ui/Badge.astro` | Colour badge. Either `variant` (brand/yellow/mint/pink/ink/outline) or `status` (in_stock/pre_order/sold_out); `comicSticker` switches to the rotated sticker shape |
| `ui/Breadcrumb.astro` | `items: {label, href?}[]` trail, always prefixed with "Trang chủ"; the last crumb is rendered as `aria-current="page"` |
| `ui/Button.astro` | Renders an `<a>` when `href` is set, otherwise a `<button>`. `variant` primary/secondary/outline/yellow/ghost, `size` sm/md/lg |

## Subdirectories
| Directory | Purpose |
|-----------|---------|
| `layout/` | Site chrome mounted by `BaseLayout.astro` |
| `catalog/` | Product listing tiles and grid |
| `comic/` | Comic/pop editorial blocks and stickers (see `comic/AGENTS.md`) |
| `cart/` | Global cart drawer |
| `ui/` | Generic primitives reused across pages |

## For AI Agents

### Working In This Directory
- Props are typed in each component's frontmatter (`interface Props`); pass them in, don't reach for global state.
- Every component is server-rendered Astro. Interactivity lives in a `<script>` block at the bottom of the file and is wired to ids the component itself renders.
- Style with Tailwind 4 utilities using the `@theme` tokens in `src/styles/global.css`. Many components still hardcode the same hex values — keep new markup consistent with the file you are editing.
- Reuse `ui/` primitives before adding a new badge or button variant. Product status pills come from `ui/Badge.astro` (`status` prop) or `comic/StickerBadge.astro`, never from bespoke colour classes.
- `catalog/ProductCard.astro` and `catalog/ProductGrid.astro` are the only catalog renderers. The `/figures` filter script queries their `data-*` attributes, so renaming or removing those attributes silently breaks filtering and sorting.
- `layout/SiteHeader.astro` and `layout/Footer.astro` read `src/data/categories.ts` and `src/data/anime.ts` directly — they are static by design and do not follow the API-first catalog path.
- `checkout/` and `product/` exist on disk but are empty and untracked by git. They have no `AGENTS.md` by design — do not create one, and do not assume the old docs' claim that they hold components.

### Testing Requirements
- `pnpm build` in `frontend/`. Most styling is inline Tailwind, so check the affected route visually in `pnpm dev`.
- No test runner is configured.

### Common Patterns
- A large new page section belongs here as a component, not inline in a page.
- CTAs go through `ui/Button.astro` — it already handles `href` vs `button`, the focus ring and the disabled state.
- Money goes through `lib/utils.ts` → `formatVND` (vi-VN, zero decimals).

## Dependencies

### Internal
- `../types/index.ts` — `Product`, `AnimeSeries`, `CategoryInfo`, `Collection`, `HeroCampaign`
- `../lib/utils.ts` — `formatVND`, `getStatusBadgeInfo`
- `../lib/cart.ts` — read by `cart/CartDrawer.astro`
- `../data/categories.ts`, `../data/anime.ts` — read by `layout/SiteHeader.astro` and `layout/Footer.astro`
- `../layouts/BaseLayout.astro` — mounts the `layout/` and `cart/` components

### External
- `astro@^5.4.2`, `tailwindcss@^4.0.9`

<!-- MANUAL: -->
