<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-10-22 | Updated: 2026-10-22 -->

# shell

## Purpose
The application chrome mounted by `AdminLayout.astro`: the left navigation (`Sidebar.astro`) and the sticky header (`Topbar.astro`). Neither takes user data as props — both read the session from the client.

## Key Files
| File | Description |
|------|-------------|
| `Sidebar.astro` | Left nav. Inline `ICONS` SVG-path map + a hardcoded `navGroups` array (6 groups, 21 entries). Active item resolved by longest-prefix match on `currentPath`; collapses on desktop via `localStorage` key `admin-sidebar-collapsed`. |
| `Topbar.astro` | Sticky header — mobile sidebar toggle, breadcrumb trail, static global-search field, "Thêm mới" → `/products/new`, bell → `/tasks`, user menu and `#topbar-logout`. |

## For AI Agents

### Working In This Directory
- The nav list is the canonical map of admin routes: `/`, `/tasks`, `/orders`, `/preorders`, `/customers`, `/products`, `/categories`, `/series`, `/brands`, `/inventory`, `/content/homepage`, `/collections`, `/media`, `/promotions`, `/reviews`, `/analytics`, `/analytics/products`, `/analytics/inventory`, `/settings/staff`, `/settings/roles`, `/settings/store`, `/settings/audit-logs`. A new page is unreachable until a nav entry is added here.
- Nav badges (`'4'`, `'1 mới'`, `'2 thấp'`, `'1 chờ'`) are hardcoded strings. They are never computed from the API and never update.
- Active-item matching picks the longest matching href, so `/analytics/products` highlights "Báo cáo sản phẩm" and not "Tổng quan kinh doanh". `/` matches only exactly.
- Collapsing hides elements via two places that must stay in sync: the `hideSel` selector list in the inline script and the `.admin-sidebar-collapsed` rules in `../../styles/global.css`. A new collapsible element needs both.
- `Sidebar.astro` uses `<script is:inline>` (runs immediately, avoids a sidebar flash); `Topbar.astro` uses a bundled module `<script>` (deferred). Do not convert one to the other without thinking about paint order.
- The Topbar global-search input has no handler — it is a visual placeholder, not a search feature.
- Topbar identity comes from `guardClient(false)` (no admin check). The admin-only check is `guardClient()` inside `AdminLayout.astro`; the two run independently.
- Both components hardcode the storefront URL `http://localhost:4321` (footer link, "Xem storefront" arrows elsewhere).

### Testing Requirements
- `pnpm build` in `frontend-admin/`.
- Visual only: check a route in `pnpm dev` (:4322) — collapse state persists per browser, so test with a clean `localStorage`.
- No test runner is configured.

## Dependencies

### Internal
- `../../lib/api.ts` — `guardClient`, `staffLogout` (Topbar script only)
- `../../layouts/AdminLayout.astro` — mounts both, passes `currentPath` and `breadcrumbs`
- `../../styles/global.css` — `.admin-sidebar-collapsed` rules, `.comic-shadow*`

### External
- `astro@^5.4.2`, `tailwindcss@^4.0.9`

<!-- MANUAL: -->
