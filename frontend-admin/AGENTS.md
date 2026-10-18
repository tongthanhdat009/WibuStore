<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-10-18 | Updated: 2026-10-18 -->

# frontend-admin

## Purpose
Admin Astro 5 + Tailwind 4 dashboard (:4322). Currently mock-data UI (`src/data/mockData.ts`); real `/api/v1/admin` wiring pending. Layouts: `AdminLayout` + `AuthLayout`.

## Key Files
| File | Description |
|------|-------------|
| `astro.config.mjs` | Astro + tailwind, pinned port 4322 |
| `package.json` | Same stack as storefront |

## Subdirectories
| Directory | Purpose |
|-----------|---------|
| `src/` | Pages, components, mock data (see `src/AGENTS.md`) |
| `public/` | `favicon.svg` only |

## For AI Agents

### Working In This Directory
- New admin pages: add `.astro` under `src/pages/`, reuse `domain/` badges + `ui/` primitives, format via `lib/formatters.ts`.
- When wiring real API: replace `mockData.ts` imports per-page; keep types in `src/types/index.ts`.
- No `fetch` to backend exists yet — check before assuming.

### Testing Requirements
- `pnpm build` in this dir.

## Dependencies

### Internal
- `backend/` `/api/v1/admin` (planned; Sanctum + `perm:*`)

### External
- `astro:5`, `tailwindcss:4`

<!-- MANUAL: -->
