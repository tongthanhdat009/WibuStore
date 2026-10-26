<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-10-22 | Updated: 2026-10-22 -->

# pages

## Purpose
The admin screen set — 35 file-routed pages rendered server-side against `/api/v1/admin`. 32 of them are API-wired through `lib/api.ts`; only `403.astro`, `404.astro` and `settings/team.astro` are static.

## Key Files
| File | Description |
|------|-------------|
| `index.astro` | Dashboard. `GET /admin/dashboard/overview`, `/admin/orders?per_page=5`, `/admin/reports/sales?days=`, `/admin/products?per_page=5`; CSV export of the sales report |
| `tasks.astro` | Work queue. `GET /admin/dashboard/tasks` |
| `products/index.astro` | Product list. `GET /admin/products` (paged, filtered) plus `/admin/anime-series` and `/admin/manufacturers` option lists; CSV export |
| `products/new.astro` | Create product. Option lists from `/admin/anime-series`, `/admin/manufacturers`, `/admin/categories` |
| `products/[id]/edit.astro` | Edit product. `GET /admin/products/{id}`; `PATCH /admin/products/{id}` and `PATCH /admin/variants/{id}` |
| `orders/index.astro` | Order list. `GET /admin/orders` (paged, filtered); CSV export |
| `orders/[id].astro` | Order detail. `GET /admin/orders/{id}`; invoice link `${API_URL}/api/v1/admin/orders/{id}/invoice`; `POST /admin/orders/{id}/transition`, `POST /admin/payments/{id}/confirm` |
| `inventory/index.astro` | Stock levels. `GET /admin/inventory` (paged); `POST /admin/inventory/adjustments` |
| `inventory/adjustments.astro` | Movement history. `GET /admin/inventory/movements` (paged) |
| `preorders/index.astro` | Pre-order campaigns. `GET /admin/preorder-campaigns` and `/admin/inventory?per_page=200` (variant picker); `POST /admin/preorder-campaigns`, `POST /admin/preorder-campaigns/{id}/open` and `/close` |
| `preorders/[id].astro` | Campaign detail. `GET /admin/preorder-campaigns/{id}`; `open`, `close`, and `POST /admin/preorder-reservations/{id}/balance-due` |
| `customers/index.astro` | Customer list. `GET /admin/customers?per_page=20&page=&q=`; CSV export |
| `customers/[id].astro` | Customer detail. `GET /admin/customers/{id}` |
| `collections/index.astro` | CMS page list. `GET /admin/pages`; `POST /admin/pages` |
| `collections/[id]/edit.astro` | CMS page + revisions. `GET /admin/pages` and `/admin/pages/{slug}/revisions`; `POST /admin/pages/{slug}/revisions/{id}/publish` |
| `content/homepage.astro` | Homepage CMS editor. `GET /admin/pages/home/revisions`; create + publish revisions |
| `analytics/index.astro` | Sales report. `GET /admin/reports/sales`; CSV export |
| `analytics/products.astro` | Product performance. `GET /admin/products?per_page=50` |
| `analytics/inventory.astro` | Inventory report. `GET /admin/inventory?per_page=200` |
| `settings/store.astro` | Store settings. `GET /admin/settings`, `PATCH /admin/settings` |
| `settings/staff.astro` | Staff list. `GET /admin/staff?per_page=20&page=` and `/admin/rbac/roles`; `POST /admin/staff`, `PATCH /admin/staff/{id}`, `DELETE /admin/staff/{id}` |
| `settings/staff/[id].astro` | Staff detail. `GET /admin/staff?per_page=100` and `/admin/rbac/roles`; `PATCH /admin/staff/{id}` with `role_ids` |
| `settings/roles.astro` | Roles and permissions. `GET /admin/rbac/roles`, `GET /admin/permissions`; `PATCH /admin/rbac/roles/{id}`, `DELETE /admin/rbac/roles/{id}` |
| `settings/audit-logs.astro` | Activity log. `GET /admin/activity-logs?per_page=20&page=` |
| `settings/team.astro` | Static — redirects to `/settings/staff` |
| `categories.astro` | Category admin. `GET /admin/categories`; `POST /admin/categories` |
| `series.astro` | Anime-series admin. `GET /admin/anime-series`; `POST /admin/anime-series` |
| `brands.astro` | Manufacturer admin. `GET /admin/manufacturers`; `POST /admin/manufacturers` |
| `reviews.astro` | Review moderation. `GET /admin/reviews` (paged, filtered); `PATCH /admin/reviews/{id}` |
| `media.astro` | Media library. `GET /admin/media?per_page=24&page=&prefix=`; `DELETE /admin/media/{id}` |
| `promotions.astro` | Coupons. `GET /admin/coupons?per_page=20&page=`; `POST /admin/coupons` |
| `login.astro` | Staff login — client-side `staffLogin` (`POST /api/v1/auth/login` with the Sanctum CSRF cookie) |
| `forgot-password.astro` | Password reset request — client-side `mutate('/auth/forgot-password', 'POST')` |
| `403.astro` / `404.astro` | Static error pages |

## Subdirectories
| Directory | Purpose |
|-----------|---------|
| `analytics/` | `index.astro` (sales), `products.astro`, `inventory.astro` |
| `collections/` | `index.astro`, `[id]/edit.astro` — CMS page list and revision editor |
| `content/` | `homepage.astro` — homepage CMS section editor |
| `customers/` | `index.astro`, `[id].astro` |
| `inventory/` | `index.astro` (levels), `adjustments.astro` (movements) |
| `orders/` | `index.astro`, `[id].astro` |
| `preorders/` | `index.astro`, `[id].astro` |
| `products/` | `index.astro`, `new.astro`, `[id]/edit.astro` |
| `settings/` | `store.astro`, `staff.astro`, `staff/[id].astro`, `roles.astro`, `audit-logs.astro`, `team.astro` |

## For AI Agents

### Working In This Directory
- Every page starts with `const { redirect } = await requireStaff(Astro); if (redirect) return redirect;` — do that before any `apiGet` call, otherwise the page renders an empty shell for an unauthenticated visitor.
- `lib/api.ts` is the only data path. `src/data/mockData.ts` is not imported by any page — do not reintroduce it.
- Reads use `apiGet<T>(path, Astro)`; writes use `mutate(path, method, body)` inside a client `<script>` block. `mutate` cannot run during SSR (`document` is undefined there).
- Endpoint paths are backend routes under `/api/v1/admin`, permission-gated with `perm:*` middleware. A 403 in the UI means the signed-in staff account lacks the permission — it is not a page bug.
- Paged lists follow one shape: `currentPage(Astro)` → `apiGet(...?per_page=N&page=P, Astro)` → `meta` → `<Pagination meta={meta} pathname={Astro.url.pathname} params={{...}} />`.
- Layout: authenticated screens wrap `AdminLayout.astro` (with `breadcrumbs` and the `actions` slot for header buttons); `login.astro` and `forgot-password.astro` wrap `AuthLayout.astro`.
- Status rendering goes through `components/domain/*Badge.astro`; money and dates through `lib/formatters.ts`; CSV export through `lib/export.ts` → `downloadCSV`.
- Several pages rebuild the API base URL inline (`const base = (import.meta.env.PUBLIC_API_URL ...) || 'http://localhost:8000'`) for export links instead of importing `API_URL` from `lib/api.ts`. Prefer `API_URL` in new code and reuse the existing pattern when editing those files.
- `collections/*` and `content/homepage.astro` both drive the CMS revision flow (`/admin/pages`, `/admin/pages/{slug}/revisions`, then `POST .../publish`).

### Testing Requirements
- `pnpm build` in `frontend-admin/`.
- Runtime verification needs the backend up: `make dev-backend` then `make dev-admin`, and sign in with the demo account `admin@wibustore.vn` / `Admin@123`.
- No test runner is configured.

### Common Patterns
- New page → add `.astro` here, guard with `requireStaff`, read with `apiGet`, write with `mutate`, and wrap it in `AdminLayout.astro`.
- New admin section → also add the nav entry in `components/shell/Sidebar.astro` (`navGroups`), otherwise the screen is unreachable from the UI.
- New list endpoint → follow the paged pattern above so pagination and CSV export stay consistent.

## Dependencies

### Internal
- `../lib/api.ts` — `apiGet`, `mutate`, `currentPage`, `requireStaff`, `staffLogin`, `staffLogout`, `PageMeta`
- `../lib/formatters.ts` — currency/date/byte formatting
- `../lib/export.ts` — `downloadCSV`
- `../layouts/AdminLayout.astro`, `../layouts/AuthLayout.astro`
- `../components/domain/*Badge.astro`, `../components/ui/*`
- `../types/index.ts` — shared status unions

### External
- `backend/` `/api/v1/admin` and `/api/v1/auth` via `PUBLIC_API_URL` (default `http://localhost:8000`) — Sanctum session auth plus `perm:*` middleware

<!-- MANUAL: -->
