<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-10-22 | Updated: 2026-10-22 -->

# cart

## Purpose
The global cart slide-over mounted by `BaseLayout.astro` on every page: overlay + right-hand panel, item list, quantity controls, subtotal and the checkout links.

## Key Files
| File | Description |
|------|-------------|
| `CartDrawer.astro` | Overlay + panel with ids `cart-drawer-overlay`, `cart-drawer-panel`, `cart-drawer-close`, `cart-drawer-items`, `cart-drawer-count-badge`, `cart-drawer-subtotal`, `shipping-threshold-text`. The item list is rendered client-side from `lib/cart.ts` |

## For AI Agents

### Working In This Directory
- The panel starts closed (`opacity-0 pointer-events-none` + `translate-x-full`). Open it by dispatching the window event `wibu-open-cart` — the header cart button and `catalog/ProductCard`'s quick-add do this. Escape, the overlay click and `#cart-drawer-close` close it.
- Items are re-rendered from `getStoredCart()` on the `wibu-cart-change` window event and after every quantity/remove click. The badge is `<n> MÓN`; the subtotal is `sum(price * quantity)` — for pre-order lines that is the full price, not the deposit.
- The empty state exists twice: as static markup in `#cart-drawer-empty` and as an HTML string in the script. The first `renderDrawerItems()` replaces `#cart-drawer-items` wholesale, so the static copy is discarded and the script copy is what users see — keep both in sync or delete the static one.
- `#shipping-threshold-text` is static markup with the 1.500.000 ₫ threshold hardcoded. There is no progress bar and nothing recomputes it.
- Item markup is built with `innerHTML` and interpolates `item.name` / `item.image` / `item.series` unescaped — escape before changing that template.
- `updateCartQuantity` with `quantity <= 0` deletes the line, so `-` on a quantity-1 line removes it.
- `initCartDrawer()` runs at module scope and again on `astro:page-load`. This project mounts no `<ClientRouter/>`, so `astro:page-load` never fires and only the first call binds.
- The `Button` import at the top of the file is unused — the footer CTAs are plain anchors.

### Testing Requirements
- `pnpm build` in `frontend/`; in `pnpm dev`, add an item from `/figures` and confirm the drawer opens and the qty buttons work.

## Dependencies

### Internal
- `../lib/cart.ts` — `getStoredCart`, `updateCartQuantity`, `removeCartItem`
- `../lib/utils.ts` — `formatVND`
- `../ui/Button.astro` — imported but unused

### External
- Browser `localStorage` (through `lib/cart.ts`) and `CustomEvent`

<!-- MANUAL: -->
