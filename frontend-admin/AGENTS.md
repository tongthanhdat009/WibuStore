<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-10-22 | Updated: 2026-10-22 -->

# frontend-admin

## Purpose
Admin Astro 5 + Tailwind 4 dashboard on :4322. It is an SSR app (`output: 'server'` + `@astrojs/node` standalone adapter) that renders every screen from `/api/v1/admin` through `src/lib/api.ts`. Session auth is Sanctum cookie-based and works because the SSR fetch forwards the browser cookie plus `Origin`/`Referer`.

## Key Files
| File | Description |
|------|-------------|
| `astro.config.mjs` | `output: 'server'`, `@astrojs/node` adapter in `standalone` mode, `server.port = 4322`, `host: true`, Tailwind vite plugin, `pageExtensions: ['astro']` so `AGENTS.md` files are not routed as pages |
| `package.json` | `astro@^5.4.2`, `@astrojs/node@^8.3.4`, `tailwindcss@^4.0.9`, `@tailwindcss/vite@^4.0.9`. Scripts pin port 4322 (`dev`, `start`, `build`, `preview`) |
| `.env` / `.env.example` | `PUBLIC_API_URL` (default `http://localhost:8000`) |
| `tsconfig.json` | `astro/tsconfigs/strict` + `strictNullChecks`; path alias `@/*` → `src/*` |
| `pnpm-workspace.yaml` | pnpm build approvals (`esbuild`, `sharp`) |

## Subdirectories
| Directory | Purpose |
|-----------|---------|
| `src/` | Pages, components, lib, layouts, types, styles (see `src/AGENTS.md`) |
| `public/` | `favicon.svg` only. No `AGENTS.md` here — `public/` is copied into the build verbatim, so a doc file would be published at `/AGENTS.md` |

## public/
Astro copies `public/` into the server output untouched — no bundling, no hashing. The admin app has exactly one asset.

| File | Description |
|------|-------------|
| `favicon.svg` | 64x64 brand mark — brand-red rounded square, ink outline, white "W", yellow accent dot. Referenced by both layouts as `/favicon.svg` |

- Anything here is served at the site root: `public/favicon.svg` → `/favicon.svg`. Do not reference it as `/public/favicon.svg`.
- Both layouts hardcode `<link rel="icon" type="image/svg+xml" href="/favicon.svg" />`. Adding a second icon means editing the layouts.
- Keep the SVG self-contained — no external fonts or images — it renders before the webfonts load.

## For AI Agents

### Working In This Directory
- Astro only — no React/Vue. Interactivity is plain `<script>` blocks inside `.astro` files.
- This app is API-wired, not mock-first. 32 of the 35 pages call `/api/v1/admin` through `src/lib/api.ts`; only `403.astro`, `404.astro` and `settings/team.astro` are static. `src/data/mockData.ts` exists but is imported by no page.
- SSR auth is the load-bearing detail: `apiGet` copies the incoming `Cookie` header and sets `Origin` + `Referer` to the SSR page origin. Dropping those headers silently turns every page into a 401.
- `requireStaff(Astro)` is the SSR guard every page starts with. It redirects to `/login` on 401 and `/403` on 403.
- Admin routes are permission-gated on the backend (`perm:products.read`, `perm:orders.transition`, `perm:inventory.adjust`, `perm:preorders.manage`, `perm:payments.confirm`, `perm:cms.publish`, `perm:users.manage`, `perm:settings.manage`, `perm:reports.read`). A 403 from the API is a permissions problem, not a bug in the page.
- Money is integer VND; format values through `src/lib/formatters.ts` (vi-VN, zero decimals).
- The dev port is 4322, set both in `astro.config.mjs` and in the `package.json` scripts; the root `Makefile` (`make dev-admin`) is the preferred entry point.
- **Astro routes every `.md` file under `src/pages/` as a page.** Each `AGENTS.md` in this tree is therefore served at its directory path (`/AGENTS`, `/settings/AGENTS`, `/orders/AGENTS`, …). Unlike the `.astro` pages, these get **no** `requireStaff` guard — the auth check lives in each page's frontmatter, which a markdown page cannot have. Astro offers no way to exclude markdown from routing (`pageExtensions` only *adds* extensions; there is no route-removal API). Keep the admin app off the public internet, and note that the repo is public on GitHub so the same content is already published in source. The one place docs must **not** live is `public/` — Astro copies that directory verbatim, which would serve the raw markdown file.

### Testing Requirements
- `pnpm build` in this directory. The build needs no backend, but every page will render its error state at runtime if the API is unreachable.
- No test runner is configured in this package.

### Common Patterns
- New page → add `.astro` under `src/pages/`, call `requireStaff(Astro)` first, then `apiGet<T>(path, Astro)`; wrap it in `AdminLayout.astro` (auth pages use `AuthLayout.astro`).
- Read via `apiGet`, write via `mutate` (client-side POST/PATCH/DELETE with the `X-XSRF-TOKEN` header).
- Status UI → `src/components/domain/*Badge.astro` only. Generic pills/buttons/inputs → `src/components/ui/`.
- Formatted values → `src/lib/formatters.ts`; CSV export → `src/lib/export.ts` → `downloadCSV`.

## Dependencies

### Internal
- `backend/` `/api/v1/admin` via `PUBLIC_API_URL` (default `http://localhost:8000`) — Sanctum session auth + `perm:*` middleware
- `backend/` `/api/v1/me` for the staff identity behind `requireStaff`

### External
- `astro@^5.4.2`, `@astrojs/node@^8.3.4`, `tailwindcss@^4.0.9`, `@tailwindcss/vite@^4.0.9`, `typescript@^5.8.2` (dev)
- Node 20+ runtime for the standalone server output

<!-- MANUAL: -->
