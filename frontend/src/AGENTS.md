<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-10-22 | Updated: 2026-10-22 -->

# src

## Purpose
Storefront source root: the API client and static fallback data, the comic/pop component families, one shared layout, and the file-routed pages.

## Key Files
| File | Description |
|------|-------------|
| `styles/global.css` | Tailwind 4 entry. `@theme` block holds the colour/font/shadow tokens; custom utilities include `.comic-border*`, `.comic-shadow*`, `.comic-panel`, `.comic-btn-hover`, `.pattern-halftone*`, `.speed-lines*`, `.sticker-pop` |
| `types/index.ts` | Shared TS types: `Product`, `ProductVariant`, `FigureCategoryType`, `StockStatusType`, `CartItem`, `FilterOptions`, `HeroCampaign`, `CategoryInfo`, `AnimeSeries`, `Collection` |

## Subdirectories
| Directory | Purpose |
|-----------|---------|
| `lib/` | API client, catalog facade, auth, cart, wishlist, CMS reader, formatters (see `lib/AGENTS.md`) |
| `data/` | Static fallback datasets (see `data/AGENTS.md`) |
| `components/` | `.astro` component families (see `components/AGENTS.md`) |
| `layouts/` | `BaseLayout.astro` (see `layouts/AGENTS.md`) |
| `pages/` | File-routed routes (see `pages/AGENTS.md`) |
| `types/` | Shared TS types (see `types/AGENTS.md`) |
| `styles/` | `global.css` — Tailwind 4 theme + utilities |

## For AI Agents

### Working In This Directory
- Astro only, no framework components. Client behaviour is plain `<script>` inside `.astro` files.
- `@/*` maps to `src/*` through the tsconfig `paths`; both alias and relative imports appear in the codebase — match the file you are editing.
- Colours and shadows come from the Tailwind 4 `@theme` block in `styles/global.css`. Most components still inline the same hex values (`#D6284F`, `#17141F`, `#FFD44A`, `#FFFDF7`); do not introduce a third palette.
- New colours or effects belong in `@theme` (or a `.comic-*` utility), not in a per-page override.
- `src/data/` is the fallback tier, not the source of truth. The API wins whenever it is reachable.
- `src/assets/` exists but is empty, as do `src/components/checkout/` and `src/components/product/`. They have no `AGENTS.md` by design (empty directories are skipped); git does not track them, so they vanish on a fresh clone.

### Testing Requirements
- `pnpm build` from `frontend/` is the only check — there is no test runner in this package.
- Build with the backend up (live path) and down (fallback path); both must render.
- Astro does not typecheck on build; `pnpm exec tsc --noEmit` covers `.ts` files only.

### Common Patterns
- Catalog reads always go through `lib/catalog.ts` — never `fetch` from a page.
- Every page wraps `layouts/BaseLayout.astro`; the layout already supplies the site chrome and the global cart drawer.
- Reuse `components/ui/` and `components/comic/` primitives before writing new markup.
- Shared types live in `types/index.ts`; API payload types live next to the client in `lib/api.ts`.

## Dependencies

### Internal
- `lib/` → `backend/` `/api/v1` via `PUBLIC_API_URL` (default `http://localhost:8000`)
- `data/` → `types/index.ts` (typed static fixtures)
- `components/` → `lib/utils.ts`, `types/index.ts`

### External
- `astro@^5.4.2`, `tailwindcss@^4.0.9`, `@tailwindcss/vite@^4.0.9`

<!-- MANUAL: -->
