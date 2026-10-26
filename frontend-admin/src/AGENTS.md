<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-10-22 | Updated: 2026-10-22 -->

# src

## Purpose
Admin dashboard source: an SSR API client with Sanctum session forwarding, two layouts, the shell and domain component families, the admin page set, and the shared vi-VN formatters.

## Key Files
| File | Description |
|------|-------------|
| `lib/api.ts` | SSR + client API layer. `apiGet`, `mutate`, `currentPage`, `requireStaff`, `staffLogin`, `staffLogout`, `guardClient`, `API_URL`, `PageMeta` |
| `lib/formatters.ts` | `formatCurrency`, `formatNumber`, `formatDate`, `formatDateTime`, `formatBytes` (all vi-VN) |
| `lib/export.ts` | `downloadCSV(filename, headers, rows)` — client-side CSV with a UTF-8 BOM |
| `styles/global.css` | Tailwind 4 entry. `@theme` tokens (`--color-admin-canvas`, `--color-success-bg`, `--color-sidebar-*`, …) plus `.comic-shadow*`, `.tabular-nums` and the collapsible-sidebar rules |
| `types/index.ts` | Shared TS types for the admin domain (see `types/AGENTS.md`) |

## Subdirectories
| Directory | Purpose |
|-----------|---------|
| `pages/` | 35 file-routed admin screens (see `pages/AGENTS.md`) |
| `components/` | `shell/`, `domain/`, `ui/` (see `components/AGENTS.md`) |
| `layouts/` | `AdminLayout.astro`, `AuthLayout.astro` (see `layouts/AGENTS.md`) |
| `lib/` | API client, formatters, CSV export (see `lib/AGENTS.md`) |
| `data/` | `mockData.ts` — legacy fixtures, currently unimported (see `data/AGENTS.md`) |
| `types/` | Shared TS types (see `types/AGENTS.md`) |
| `styles/` | `global.css` — Tailwind 4 theme |

## For AI Agents

### Working In This Directory
- Astro only, no framework components. Client behaviour is plain `<script>` inside `.astro` files.
- `@/*` maps to `src/*` through the tsconfig `paths`; the codebase mostly uses relative imports — match the file you are editing.
- The admin theme lives in the Tailwind 4 `@theme` block in `styles/global.css`. Unlike the storefront, the admin pages consistently use these tokens (`bg-[#F7F5F0]`, `text-[#17141F]`, …) rather than semantic class names — keep that convention.
- `data/mockData.ts` is dead weight: no page imports it. Do not add new mock imports; wire the API instead.

### Testing Requirements
- `pnpm build` in `frontend-admin/`.
- Astro does not typecheck during `pnpm build`; `pnpm exec tsc --noEmit` covers `.ts` files only (`.astro` frontmatter is not covered).
- No test runner is configured.

### Common Patterns
- Read data with `apiGet<T>(path, Astro)`; write with `mutate(path, method, body)` inside a client `<script>`.
- Guard every admin page with `requireStaff(Astro)` before rendering data.
- Status display → `components/domain/*Badge.astro`. Money and dates → `lib/formatters.ts`.

## Dependencies

### Internal
- `lib/api.ts` → `backend/` `/api/v1/admin` via `PUBLIC_API_URL` (default `http://localhost:8000`)
- `layouts/` → `components/shell/`, `styles/global.css`
- `pages/` → `lib/api.ts`, `lib/formatters.ts`, `components/domain/`, `components/ui/`

### External
- `astro@^5.4.2`, `@astrojs/node@^8.3.4`, `tailwindcss@^4.0.9`, `@tailwindcss/vite@^4.0.9`

<!-- MANUAL: -->
