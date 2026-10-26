<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-10-22 | Updated: 2026-10-22 -->

# layout

## Purpose
Site chrome mounted by `layouts/BaseLayout.astro`: the promo strip, the sticky header (brand, nav, anime megamenu, search, cart/wishlist counters, mobile drawer) and the dark footer.

## Key Files
| File | Description |
|------|-------------|
| `AnnouncementBar.astro` | Static promo strip — no props, no script. Hardcoded October promo, code `WIBUPOP` |
| `SiteHeader.astro` | Sticky header. Desktop nav (`/figures`, anime megamenu, `/collections`, `/pre-orders`), search form to `/search`, cart/wishlist counters, mobile slide-out menu |
| `Footer.astro` | Dark footer — brand block, category links, policy links, newsletter form, payment/social row |

## For AI Agents

### Working In This Directory
- `SiteHeader.astro` imports `src/data/categories.ts` and `src/data/anime.ts`; `Footer.astro` imports `src/data/categories.ts` only. Both are static by design and never follow the API-first catalog path, so a new category needs a data-file entry to appear in the nav.
- The header owns the document-level click delegation for `.quick-add-btn` and `.wishlist-btn` — the same class names used by `catalog/ProductCard.astro` and `pages/products/[slug].astro`. Those buttons are inert if the header is not mounted.
- The header cart button dispatches the `wibu-open-cart` window event; `cart/CartDrawer.astro` listens for it. Counters (`#header-cart-count`, `#header-wishlist-count`) refresh on the `wibu-cart-change` / `wibu-wishlist-change` events.
- The mobile drawer ids (`#mobile-menu-toggle`, `#mobile-menu-drawer`, `#mobile-menu-panel`, `#mobile-menu-close`) are toggled by class swaps only — no `aria-expanded` is maintained.
- `AnnouncementBar.astro` is fully static and takes no props; changing the promo means editing the markup, and the `WIBUPOP` code is duplicated nowhere else.
- The footer's newsletter form is a no-op: `onsubmit` calls `preventDefault()` and shows an `alert`. Payment badges are mock labels, not real logos.
- `initHeaderControls()` runs at module scope and again on `astro:page-load`. No `<ClientRouter/>` is mounted, so `astro:page-load` never fires and only the first call binds.
- Stacking: the announcement bar and the sticky header are both `z-40`; the mobile drawer and the cart drawer are `z-50`.

### Testing Requirements
- `pnpm build` in `frontend/`; in `pnpm dev` check the megamenu, the mobile drawer below `xl`, the search form, and that the cart/wishlist counters update after an add-to-cart.

## Dependencies

### Internal
- `../../data/categories.ts`, `../../data/anime.ts` — nav and footer link lists
- `../../lib/cart.ts`, `../../lib/wishlist.ts` — counters and delegated handlers
- `../../layouts/BaseLayout.astro` — mounts all three components

### External
- `astro@^5.4.2`, `tailwindcss@^4.0.9`

<!-- MANUAL: -->
