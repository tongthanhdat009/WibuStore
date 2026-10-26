<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-10-22 | Updated: 2026-10-22 -->

# frontend

## Purpose
Public Astro 5 + Tailwind 4 storefront on :4321. The catalog is API-first via `PUBLIC_API_URL` with a static fallback in `src/data/`, so the site still builds and renders when the backend is down. The cart stores API `variantId`s and checkout posts to `POST /api/v1/orders`, then shows the server-issued `WB-...` order code.

## Key Files
| File | Description |
|------|-------------|
| `astro.config.mjs` | Astro + `@tailwindcss/vite` plugin. No adapter — static output. `pageExtensions: ['astro']` so `AGENTS.md` files are not routed as pages |
| `package.json` | `astro@^5.4.2`, `tailwindcss@^4.0.9`, `@tailwindcss/vite@^4.0.9`, `typescript@^5.8.2` (dev). Scripts: `dev`, `start`, `build`, `preview`, `astro` |
| `.env` / `.env.example` | `PUBLIC_API_URL` (default `http://localhost:8000`) |
| `tsconfig.json` | Extends `astro/tsconfigs/strict`; path alias `@/*` → `src/*` |
| `pnpm-workspace.yaml` | pnpm build approvals (`esbuild`, `sharp`) — not a member of a root workspace |

## Subdirectories
| Directory | Purpose |
|-----------|---------|
| `src/` | Pages, components, lib, data, layouts, types, styles (see `src/AGENTS.md`) |
| `public/` | Static files served at `/`. No `AGENTS.md` here — `public/` is copied into the build verbatim, so a doc file would be published at `/AGENTS.md`. Its contents are documented below |

## public/
Astro copies `public/` into the build output untouched — no bundling, no hashing. Everything in it is served from `/` as-is.

| File | Description |
|------|-------------|
| `favicon.svg` | Site icon, referenced by `src/layouts/BaseLayout.astro` as `/favicon.svg` |
| `videos/ichigo.mp4` | Chapter 1 clip ("ICHIGO" — Bleach), h264 720x720 ~8 s ~1.9 MB, AAC audio |
| `videos/goku.mp4` | Chapter 2 clip ("GOKU" — Dragon Ball), h264 720x720 ~8 s ~1.9 MB, AAC audio |
| `videos/promo.mp4` | Chapter 3 clip ("WIBU STORE" promo), h264 720x720 ~8 s ~1.9 MB, **no audio track** |

- The only consumer of the clips is `src/components/comic/VideoStory.astro`, referenced by absolute path (`/videos/ichigo.mp4`, `/videos/goku.mp4`, `/videos/promo.mp4`). Adding a clip means editing that component's `chapters` array as well.
- `VideoStory` does **not** autoplay or loop: it calls `videos.forEach((v) => v.pause())` at init and drives playback by seeking `currentTime` from the scroll position. The `<video>` elements are hidden (`opacity-0`) and a canvas draws the luma-keyed frame (near-white pixels → transparent, channel min ≥ 250). A clip with a white background keys out entirely.
- `prefers-reduced-motion` disables the scroll scrubbing and the `sunburst`/`bob` animations.
- Never import from `public/` in TS or Astro code — reference the `/...` URL path instead.
- `src/layouts/BaseLayout.astro` references `/images/og-image.jpg` as the default OG image, but `public/images/` does not exist — a dead path.

## For AI Agents

### Working In This Directory
- Astro only — there is no React/Vue anywhere. Page interactivity is plain `<script>` blocks inside `.astro` files.
- Output is static (no adapter). `products/[slug].astro` prerenders one page per slug from the live API when it is reachable; when it is not, `getAllSlugs()` falls back to the slugs in `src/data/products.ts`.
- `pnpm build` must never assume the API is up: `src/lib/catalog.ts` swallows every fetch failure and substitutes `src/data/*`.
- Money is integer VND end to end. Format with `Intl` vi-VN and zero decimals via `src/lib/utils.ts` → `formatVND`.
- The dev port (4321) is set by the root `Makefile` (`make dev-storefront`), not by `astro.config.mjs`.
- **Astro routes every `.md` file under `src/pages/` as a page.** Each `AGENTS.md` in this tree is therefore served at its directory path (`/AGENTS`, `/categories/AGENTS`, `/products/AGENTS`, …) and rendered with no layout or chrome. Astro offers no way to exclude markdown from routing — `pageExtensions` only *adds* extensions, it does not restrict `.md`, and there is no route-removal API. This is accepted: the repo is public on GitHub, so the same content is already published in source. The one place docs must **not** live is `public/` — Astro copies that directory verbatim, which would serve the raw markdown file.
- Run the app through `make dev-storefront` from the repo root rather than `pnpm dev` directly, so the port and the process cleanup stay consistent with the other two services.

### Testing Requirements
- `pnpm build` in this directory. With the backend up it prerenders every API product; with the backend down it still succeeds on the static fallback. Both paths are worth exercising.
- Astro does not typecheck during `pnpm build`; `pnpm exec tsc --noEmit` catches errors in `.ts` files only (`.astro` frontmatter is not covered).
- No test runner is configured in this package.

### Common Patterns
- Read catalog data through `src/lib/catalog.ts` (`getCatalogProducts`, `getAllCatalogProducts`, `getProductBySlug`, `getAllSlugs`) — never raw `fetch` from a page.
- Wrap every page in `layouts/BaseLayout.astro`; it already mounts the announcement bar, header, cart drawer and footer.
- Reuse `src/components/ui/` primitives (`Button`, `Badge`, `Breadcrumb`) and `src/components/comic/StickerBadge.astro` instead of restyling per page.
- Client-side state lives in `localStorage` through `src/lib/cart.ts` and `src/lib/wishlist.ts`; both broadcast a `CustomEvent` so open components can react.

## Dependencies

### Internal
- `backend/` `/api/v1` — catalog, taxonomy, preorders, cart, wishlist, orders, Sanctum session auth. Base URL from `PUBLIC_API_URL` (default `http://localhost:8000`)

### External
- `astro@^5.4.2`, `tailwindcss@^4.0.9`, `@tailwindcss/vite@^4.0.9`, `typescript@^5.8.2` (dev)
- Runtime: Google Fonts (Anton, Be Vietnam Pro), Unsplash CDN images

<!-- MANUAL: -->
