<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-10-22 | Updated: 2026-10-22 -->

# policies

## Purpose
Static policy pages — pre-order deposit terms, shipping/packaging standards and the return/warranty rules. No data, no scripts.

## Key Files
| File | Description |
|------|-------------|
| `pre-order.astro` | Pre-order deposit policy: deposit rates, release schedule, 100% refund conditions. Sticker badge `TERMS & GUARANTEE` |
| `return.astro` | Return & damage warranty policy: 7-day conditions and the unboxing-video requirement. Sticker badge `RETURN & WARRANTY` |
| `shipping.astro` | Packaging and shipping policy: 3-layer bubble wrap, delivery times, free-shipping threshold. Sticker badge `PACKAGING STANDARD` |

## For AI Agents

### Working In This Directory
- All three files are pure static markup — no frontmatter logic, no API calls, no `<script>`. The copy is Vietnamese; the surrounding chrome is not translated.
- Every page wraps `layouts/BaseLayout.astro` and reuses `ui/Breadcrumb.astro` and `comic/StickerBadge.astro`. The breadcrumb root label is "Chính sách" and links to the page itself.
- The layout is a narrower `max-w-[960px]` container than the catalog pages (`max-w-[1440px]`).
- The free-shipping threshold of `1.500.000 ₫` is stated here and hardcoded independently in `layout/AnnouncementBar.astro`, `cart/CartDrawer.astro` and `pages/cart.astro` (the cart page applies it in a client-side script). Changing one does not change the others.
- The pages are linked from `layout/Footer.astro` (all three) and from the `layout/SiteHeader.astro` mobile drawer (pre-order and shipping only). Adding a policy page means updating those links.
- Pre-order copy references percentages and flat amounts; keep them in sync with the deposit values the API returns on `products/[slug].astro`.
- Astro 5 renders Markdown pages, so this `AGENTS.md` is itself prerendered as a public route (`/policies/AGENTS/`) with no layout or chrome around it.

### Testing Requirements
- `pnpm build` in `frontend/`; the three routes are static — verify the copy renders and the breadcrumb resolves.
- No test runner is configured.

## Dependencies

### Internal
- `../../layouts/BaseLayout.astro`
- `../../components/ui/Breadcrumb.astro`, `../../components/comic/StickerBadge.astro`

### External
- None.

<!-- MANUAL: -->
