<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-10-22 | Updated: 2026-10-22 -->

# styles

## Purpose
The single stylesheet of the admin app: the Tailwind 4 entry point, the design-token block, the base-layer overrides and the handful of custom utility classes the pages rely on.

## Key Files
| File | Description |
|------|-------------|
| `global.css` | `@import "tailwindcss"` + a `@theme` token block, `@layer base` overrides, `.comic-shadow*` / `.tabular-nums` classes and the `.admin-sidebar-collapsed` rules. |

## For AI Agents

### Working In This Directory
- Tailwind 4 — there is no `tailwind.config.js` and no `@tailwind` directives; `@theme` in this file is the whole config.
- The `@theme` tokens (`--color-admin-canvas`, `--color-brand`, `--color-success-bg`, `--color-sidebar-*`, `--font-display`, …) are declared but the admin pages mostly use hardcoded hex utilities (`bg-[#F7F5F0]`, `text-[#17141F]`, `border-[#D8D4D0]`). Match the existing hex convention in pages; do not introduce semantic class names only half the codebase uses.
- Custom classes worth knowing: `.comic-shadow-sm` / `.comic-shadow` (hard offset ink shadow), `.comic-shadow-brand`, `.comic-shadow-yellow`, `.tabular-nums` (used on every money/count column).
- `.admin-sidebar-collapsed` duplicates the logic in `../components/shell/Sidebar.astro`. The CSS hides the elements, the inline script sets the width and inline styles. Change both together or the collapsed sidebar breaks in one of the two modes.
- Fonts are not declared here — Anton (display) and Be Vietnam Pro (body) are loaded from Google Fonts in `AdminLayout.astro` and `AuthLayout.astro`. `--font-display` / `--font-sans` only name them.
- `@layer base` sets the body background/colour/font, the `::selection` colour, and the 6px custom scrollbar.
- Both layouts import this file; there is no other CSS entry point.

### Testing Requirements
- `pnpm build` in `frontend-admin/` — the Tailwind Vite plugin compiles this file.
- Visual changes: check a route in `pnpm dev` (:4322).
- No test runner is configured.

## Dependencies

### Internal
- `../layouts/AdminLayout.astro`, `../layouts/AuthLayout.astro` — import `global.css`
- `../components/shell/Sidebar.astro` — depends on the `.admin-sidebar-collapsed` rules

### External
- `tailwindcss@^4.0.9`, `@tailwindcss/vite@^4.0.9`

<!-- MANUAL: -->
