<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-10-22 | Updated: 2026-10-22 -->

# products

## Purpose
The product catalogue list and the create-product form.

## Key Files
| File | Description |
|------|-------------|
| `index.astro` | Product list. `GET /admin/products?per_page=20&page=` with `status`, `q`, `anime_series_id`, `manufacturer_id`; filter dropdowns from `/admin/anime-series` and `/admin/manufacturers`; CSV export. |
| `new.astro` | Create product. Option lists from `/admin/anime-series`, `/admin/manufacturers`, `/admin/categories`; `POST /admin/products`. |

## For AI Agents

### Working In This Directory
- `/admin/products` reads need `perm:products.read`; the option lists (`/admin/anime-series`, `/admin/manufacturers`, `/admin/categories`) are `perm:products.write` endpoints. A read-only account gets a 403 on the dropdowns but still sees the table.
- The URL filter names differ from the API parameter names: the dropdowns submit `series` and `maker`, and the page translates them to `anime_series_id` / `manufacturer_id`. `Pagination` receives the URL names (`series`, `maker`) so paging keeps the filters — keep the two naming sets straight.
- The list status cell is a hand-rolled `ui/Badge.astro` (active → success, archived/draft → neutral). There is no domain badge for product publish status. The CSV export does its own client-side fetch of `?per_page=100`, so it ignores the active filters and is capped at 100 rows.
- `new.astro` posts `{name, slug?, description?, short_description?, status, seo_title?, seo_description?, anime_series_id?, manufacturer_id?, category_ids?: [id], variants: [{sku, label, price_amount}]}` to `POST /admin/products`.
- "Lưu bản nháp" posts `status: 'draft'`, "Tạo & Xuất bản" posts `status: 'active'`. The `publishStatus` select (draft/published/archived) is **not** part of the payload — the two buttons decide the status.
- `stockStatus`, `scale`, `dimensions`, `material`, `releaseDate`, `isFeatured` and `isTrending` are rendered in the form but never collected into the payload. They are silently dropped.
- The media drop-zone is a visual placeholder — no upload happens here; it only links to `/media`. The "Preview Card" panel renders hardcoded demo content.
- On success the page redirects to `/products/{id}/edit`.

### Testing Requirements
- `pnpm build` in `frontend-admin/`.
- Runtime: backend up; create a product and confirm it lands on the edit screen and appears in the list.
- No test runner is configured.

## Dependencies

### Internal
- `../../lib/api.ts` — `apiGet`, `currentPage`, `requireStaff`, `mutate`
- `../../lib/formatters.ts` — `formatCurrency` (integer VND)
- `../../lib/export.ts` — `downloadCSV`
- `../../components/ui/Pagination.astro`, `../../components/ui/Input.astro`, `../../components/ui/Button.astro`, `../../components/ui/Badge.astro`
- `../../layouts/AdminLayout.astro`

### External
- Backend REST API `/api/v1/admin` at `PUBLIC_API_URL` (default `http://localhost:8000`) — Sanctum session auth + `perm:*` middleware

<!-- MANUAL: -->
