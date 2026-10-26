<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-10-22 | Updated: 2026-10-22 -->

# layouts

## Purpose
The single Astro layout for the storefront.

## Key Files
| File | Description |
|------|-------------|
| `BaseLayout.astro` | Full HTML shell. Imports `../styles/global.css`, mounts `AnnouncementBar` + `SiteHeader` + `CartDrawer` + `Footer` around a `<slot />`, and emits the title/description/OG/Twitter meta plus the Anton + Be Vietnam Pro font links |

## For AI Agents

### Working In This Directory
- `BaseLayout.astro` is the only layout. Every page must wrap its content in it — it is what supplies the header, footer and global cart drawer.
- Props: `title` (default `Wibu Store — Anime Pop & Comic Collectibles`), `description`, `image` (default `/images/og-image.jpg`). The title gets ` | Wibu Store` appended unless it already contains "Wibu Store".
- The default OG `image` points at `/images/og-image.jpg`, but `public/images/` does not exist in this repo — the default OG image is a dead path. Pass an explicit `image` if the OG tag matters.
- `lang="vi"` and the body classes hardcode the canvas colours and font; do not duplicate that styling in pages.
- There is only the default `<slot />` — a page needing a second slot (e.g. a header action bar) must add it here, not work around it.
- The layout is static HTML with no client script; the only runtime behaviour comes from the components it mounts.

### Testing Requirements
- `pnpm build` in `frontend/`; check the emitted `<head>` for the title-suffix behaviour and the OG tags.
- No test runner is configured.

### Common Patterns
- Import with the depth-appropriate relative path (`../layouts/BaseLayout.astro` from `pages/`, `../../layouts/BaseLayout.astro` from `pages/<sub>/`) and wrap the page body.
- Page-specific metadata goes through props, not a duplicated `<head>` block.

## Dependencies

### Internal
- `../styles/global.css` — Tailwind 4 entry and theme
- `../components/layout/AnnouncementBar.astro`, `../components/layout/SiteHeader.astro`, `../components/layout/Footer.astro`
- `../components/cart/CartDrawer.astro`
- `../components/ui/Button.astro` — used inside `CartDrawer`

### External
- `astro@^5.4.2`, `tailwindcss@^4.0.9`, Google Fonts

<!-- MANUAL: -->
