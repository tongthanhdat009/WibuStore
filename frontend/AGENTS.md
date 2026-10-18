<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-10-18 | Updated: 2026-10-18 -->

# frontend

## Purpose
Public Astro 5 + Tailwind 4 storefront (:4321). API-first catalog via `PUBLIC_API_URL`, static fallback when backend offline. Cart stores API `variantId`; checkout `POST /api/v1/orders`, shows server `WB-...` code.

## Key Files
| File | Description |
|------|-------------|
| `astro.config.mjs` | Astro + tailwindcss vite plugin |
| `package.json` | Astro 5, Tailwind 4, TS only deps |
| `.env` / `.env.example` | `PUBLIC_API_URL` (default `http://localhost:8000`) |

## Subdirectories
| Directory | Purpose |
|-----------|---------|
| `src/` | Pages, components, lib, data (see `src/AGENTS.md`) |
| `public/` | Static assets |

## For AI Agents

### Working In This Directory
- Astro only, no React/Vue. `pnpm dev --port 4321`; build prerenders product pages from live API when up.
- Keep static fallback working: never assume API reachable at build.
- VND formatting via `Intl vi-VN`, zero decimals.

### Testing Requirements
- `pnpm build` in this dir (needs API up for full prerender check).

## Dependencies

### Internal
- `backend/` `/api/v1` (catalog, cart, orders)

### External
- `astro:5`, `tailwindcss:4`, `@tailwindcss/vite`

<!-- MANUAL: -->
