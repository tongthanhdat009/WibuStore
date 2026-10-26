<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-10-22 | Updated: 2026-10-22 -->

# styles

## Purpose
The single Tailwind 4 stylesheet for the storefront: the `@theme` token block, the base layer, and the comic-print utility classes.

## Key Files
| File | Description |
|------|-------------|
| `global.css` | `@import "tailwindcss"` plus a `@theme` block (fonts, colours, shadows), a `@layer base` reset for body/selection/scrollbar, the `.comic-*` / `.pattern-halftone*` / `.speed-lines*` / `.sticker-pop` utilities, and a global `prefers-reduced-motion` override |

## For AI Agents

### Working In This Directory
- `global.css` is imported once, by `layouts/BaseLayout.astro`. It is the only place Tailwind is wired in — do not add a second `@import "tailwindcss"`.
- `@theme` defines `--font-display` (Anton) and `--font-sans` (Be Vietnam Pro), the `--color-*` palette (`--color-brand` `#D6284F`, `--color-ink` `#17141F`, `--color-accent-yellow` `#FFD44A`, `--color-canvas` `#FFFDF7`, …) and the `--shadow-comic*` hard shadows. These generate the utilities (`bg-brand`, `text-ink`, `shadow-comic`, `font-display`).
- Almost every component still inlines the same hex values instead of the token utilities — ~398 `#17141F` occurrences across the `.astro` files. Only the font utilities are really used (`font-display` ~114 lines, `font-sans` ~8); the colour and shadow tokens have exactly one usage in the whole app (`stroke-ink` in `pages/about.astro`) — `bg-brand`, `text-ink`, `shadow-comic`, `bg-canvas` and friends are unused. Match the file you are editing rather than introducing a third palette.
- The `.comic-*` utilities reference `var(--color-ink-border)` (`#26212B`), not `--color-ink` (`#17141F`) — so `.comic-border` and `.comic-shadow` are visibly softer than the hand-written `border-[#17141F]` / `shadow-[4px_4px_0px_#17141F]` used everywhere else.
- Of the custom utilities only `.pattern-halftone` (8 files) and `.speed-lines` (`comic/ComicCoverHero.astro`) are actually used; `.comic-border*`, `.comic-shadow*`, `.comic-panel`, `.comic-btn-hover`, `.pattern-halftone-yellow`, `.pattern-halftone-pink`, `.speed-lines-accent` and `.sticker-pop` have no consumers yet.
- The base layer sets `overflow-x: hidden` on `body` and styles `::selection` and the webkit scrollbar; the scrollbar rules are webkit-only and silently do nothing on Firefox.
- The trailing `prefers-reduced-motion` block forces every animation and transition to 0.01ms site-wide. `comic/VideoStory.astro` adds its own scoped reduced-motion rules on top.

### Testing Requirements
- `pnpm build` in `frontend/` — Tailwind 4 compiles at build time, so a new token only becomes a utility after a rebuild.
- No test runner is configured.

## Dependencies

### Internal
- `../layouts/BaseLayout.astro` — the only importer of this file

### External
- `tailwindcss@^4.0.9` via the `@tailwindcss/vite` plugin in `astro.config.mjs`
- Google Fonts: Anton, Be Vietnam Pro (linked from `BaseLayout.astro`, not from here)

<!-- MANUAL: -->
