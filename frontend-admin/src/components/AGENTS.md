<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-10-22 | Updated: 2026-10-22 -->

# components

## Purpose
Admin component families: the application shell (`shell/`), domain-specific badges and metrics (`domain/`), and generic primitives (`ui/`).

## Key Files
| File | Description |
|------|-------------|
| `shell/Sidebar.astro` | Left navigation. Inline `ICONS` SVG-path map and a `navGroups` array (TỔNG QUAN, BÁN HÀNG, SẢN PHẨM, NỘI DUNG & MARKETING, BÁO CÁO, HỆ THỐNG). Takes `currentPath` for the active item; collapsible on desktop via the `.admin-sidebar-collapsed` class |
| `shell/Topbar.astro` | Sticky header — mobile sidebar toggle, breadcrumb trail from the `breadcrumbs` prop, global search, quick-create, alerts, user menu, and the `#topbar-logout` button |
| `domain/OrderStatusBadge.astro` | Maps an `OrderStatus` to a label + `ui/Badge` variant (Chờ xác nhận, Đã xác nhận, Đang xử lý, Đang giao, Đã giao, Hoàn tất, Đã hủy, Hết hạn) |
| `domain/PaymentStatusBadge.astro` | Maps a `PaymentStatus` to a label + variant (Chưa thanh toán, Đã cọc một phần, Đã thanh toán, …) |
| `domain/FulfillmentStatusBadge.astro` | Maps a `FulfillmentStatus` to a label + variant (Chờ xuất kho, Đang soạn hàng, Đã đóng gói, Đang vận chuyển, Đã giao hàng, Hàng hoàn về) |
| `domain/InventoryLevelBadge.astro` | Props `status` (`ok` / `low_stock` / `out_of_stock`) and `available`; renders the count in the badge |
| `domain/MetricCard.astro` | KPI tile — `title`, `value`, optional `subtext`, `trend`, `link`, `badge`, `badgeVariant` |
| `ui/Badge.astro` | Generic pill. `variant` success/warning/danger/info/neutral/brand/yellow, `size` sm/md, `class` |
| `ui/Button.astro` | Renders an `<a>` when `href` is set, otherwise a `<button>`. `variant` primary/secondary/danger/ghost/comic, `size` sm/md/lg |
| `ui/Input.astro` | Labelled input with `label`, `name`, `id` (defaults to `name`), `type`, `value`, `placeholder`, `required`, `disabled`, `error`, `hint` |
| `ui/Pagination.astro` | Server-side pager. Props `meta` (`PageMeta`), `pathname`, `params`; builds `?page=N` links and shows the from/to/total range |

## Subdirectories
| Directory | Purpose |
|-----------|---------|
| `shell/` | Sidebar + Topbar, mounted by `AdminLayout.astro` |
| `domain/` | Status badges and KPI cards (see `domain/AGENTS.md`) |
| `ui/` | Generic primitives reused across pages |

## For AI Agents

### Working In This Directory
- Props are typed in each component's frontmatter. `ui/Badge.astro` and `ui/Button.astro` use `class` (not `className`) as the extra-class prop — the storefront uses `className`, so do not mix the two up.
- Status colour is never written inline. Map a status through `domain/*Badge.astro`; if a new status appears, add it to that component's `config` map, not to the page.
- `domain/*Badge.astro` import the shared union types from `../../types/index.ts` — a new status value needs a type update too.
- `ui/Pagination.astro` only renders when `lastPage > 1` or `total > 0`, and it preserves extra query params via the `params` prop.
- Style with Tailwind 4 utilities using the `@theme` tokens in `../styles/global.css` (rounded-[8px]/[14px]/[16px] radii, `#D8D4D0` borders, `#F7F5F0` canvas).
- The sidebar nav is a hardcoded array in `Sidebar.astro` — adding a route means adding a nav entry there too, not just a page file.

### Testing Requirements
- `pnpm build` in `frontend-admin/`.
- Badge/button changes are visual — check the affected route in `pnpm dev` (:4322).
- No test runner is configured.

### Common Patterns
- New status → extend the `config` map in the matching `domain/*Badge.astro` and widen the union in `../types/index.ts`.
- New form field → `ui/Input.astro`, then read it from the `FormData` in the page's client script.
- Values shown in a badge come from the API verbatim; format money/dates in the page with `lib/formatters.ts` before passing them in.

## Dependencies

### Internal
- `../types/index.ts` — `OrderStatus`, `PaymentStatus`, `FulfillmentStatus`
- `../lib/api.ts` — `PageMeta` type used by `ui/Pagination.astro`
- `../layouts/AdminLayout.astro` — mounts `shell/Sidebar.astro` and `shell/Topbar.astro`
- `../lib/api.ts` — `guardClient` / `staffLogout` used by the layout script and the topbar

### External
- `astro@^5.4.2`, `tailwindcss@^4.0.9`

<!-- MANUAL: -->
