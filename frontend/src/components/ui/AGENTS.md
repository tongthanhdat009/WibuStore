<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-10-22 | Updated: 2026-10-22 -->

# ui

## Purpose
Generic presentational primitives shared across pages and component families — badges, breadcrumbs and buttons. Reuse these instead of restyling per page.

## Key Files
| File | Description |
|------|-------------|
| `Badge.astro` | Colour badge. Either `variant` (brand/yellow/mint/pink/ink/outline) or `status` (in_stock/pre_order/sold_out); `comicSticker` swaps the pill for the rotated sticker shape |
| `Breadcrumb.astro` | `items: {label, href?}[]` trail, always prefixed with a "Trang chủ" home link; the last crumb (or any crumb without `href`) renders as `aria-current="page"` |
| `Button.astro` | Renders an `<a>` when `href` is set, otherwise a `<button>`. `variant` primary/secondary/outline/yellow/ghost, `size` sm/md/lg |

## For AI Agents

### Working In This Directory
- `Button.astro`: passing `href` switches the element to an anchor — `type` and `disabled` are then ignored and the anchor gets `role`-less link semantics. `disabled` only applies to the `<button>` branch.
- `Button.astro` spreads all unrecognised props (`...rest`) onto the rendered element, so `data-*` and `aria-*` pass straight through.
- `Badge.astro` gives `status` precedence: when `status` is set the `variant` switch is skipped entirely, so `variant` + `status` together silently ignores the variant. `status` only recognises `in_stock`, `pre_order`, `sold_out` — `coming_soon` falls through to the default brand colour. The `variant` union includes `'status'`, which has no case in the switch.
- `Badge.astro` renders a `<span>` with a slot; the label text is the slot content, not a prop.
- `Breadcrumb.astro` hardcodes the leading "Trang chủ" link — do not add it to `items`. A crumb without `href` is rendered as plain text with `aria-current="page"` even when it is not the last one.
- No page currently imports `Badge.astro` — status pills in the catalog come from `comic/StickerBadge.astro`. Prefer `StickerBadge` for the comic family and `Badge` for generic labels.
- Every colour here is a hardcoded hex, not a `@theme` token from `styles/global.css`.

### Testing Requirements
- `pnpm build` in `frontend/`; check the affected route in `pnpm dev` — there is no unit test for these primitives.

## Dependencies

### Internal
- `../../styles/global.css` — Tailwind 4 theme (the hex values here are duplicated from it, not referenced)
- `../comic/StickerBadge.astro` — the comic-family badge used for status pills

### External
- `astro@^5.4.2`, `tailwindcss@^4.0.9`

<!-- MANUAL: -->
