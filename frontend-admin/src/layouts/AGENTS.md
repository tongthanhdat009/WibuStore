<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-10-22 | Updated: 2026-10-22 -->

# layouts

## Purpose
The two HTML shells for the admin app: the authenticated app chrome and the standalone auth page.

## Key Files
| File | Description |
|------|-------------|
| `AdminLayout.astro` | Authenticated shell. Imports `../styles/global.css`, mounts `shell/Sidebar.astro` (`currentPath`) and `shell/Topbar.astro` (`breadcrumbs`) around a `<slot />`, renders a page header with a named `actions` slot, and runs `guardClient()` in a client script. Props: `title` (required), `description`, `breadcrumbs` (defaults to a single crumb with the page title) |
| `AuthLayout.astro` | Login/forgot-password shell. Centred brand block, a card around the `<slot />`, and a footer row linking back to the storefront on `http://localhost:4321`. Props: `title` (required) |

## For AI Agents

### Working In This Directory
- `AdminLayout.astro` is for every authenticated screen; `AuthLayout.astro` is for `login.astro` and `forgot-password.astro` only.
- The page title is suffixed automatically: `${title} — Wibu Store Studio Admin`.
- `AdminLayout.astro` provides a named slot `actions` for header buttons. Use `<Button slot="actions">…</Button>` in a page rather than rebuilding a header row.
- The layout itself does not enforce auth — the page must call `requireStaff(Astro)` and return the redirect. `guardClient()` in the layout script is the browser-side backstop only.
- The `<body>` is `flex overflow-hidden` with the main column scrolling internally, so a page must not add its own page-level scroll container.
- `AuthLayout.astro` hardcodes the storefront URL `http://localhost:4321` in its footer link. That is a dev default.

### Testing Requirements
- `pnpm build` in `frontend-admin/`.
- Check both shells in `pnpm dev` (:4322) — a page using the wrong layout will look wrong in the sidebar/topbar chrome.

### Common Patterns
- Import with the depth-appropriate relative path (`../layouts/AdminLayout.astro` from `pages/`, `../../layouts/AdminLayout.astro` from `pages/<sub>/`).
- Page metadata → `title` + `description` props; navigation trail → the `breadcrumbs` prop; header buttons → the `actions` slot.

## Dependencies

### Internal
- `../styles/global.css` — Tailwind 4 entry and theme
- `../components/shell/Sidebar.astro`, `../components/shell/Topbar.astro`
- `../lib/api.ts` — `guardClient` used by `AdminLayout.astro`
- `../pages/**` — every page wraps one of these two layouts

### External
- `astro@^5.4.2`, `tailwindcss@^4.0.9`, Google Fonts

<!-- MANUAL: -->
