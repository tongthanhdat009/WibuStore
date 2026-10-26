<!-- Parent: ../../AGENTS.md -->
<!-- Generated: 2026-10-22 | Updated: 2026-10-22 -->

# [id]

## Purpose
The product edit screen: basic data, classification, the media grid, per-variant pricing and the publish/archive controls.

## Key Files
| File | Description |
|------|-------------|
| `edit.astro` | `GET /admin/products/{id}` + `/admin/anime-series` + `/admin/manufacturers`; save `PATCH /admin/products/{id}` then `PATCH /admin/variants/{id}` per edited price; publish `POST /admin/products/{id}/publish`; archive `POST /admin/products/{id}/archive`. |

## For AI Agents

### Working In This Directory
- The product read needs `perm:products.read`; the save needs `perm:products.write`; publish/archive need `perm:products.publish`.
- An unknown id redirects to `/products` — the page shows no error state for a missing product.
- Saving sends `PATCH /admin/products/{id}` with `{name, short_description, description, anime_series_id, manufacturer_id}` and then, for every variant price cell the user actually typed into, a separate `PATCH /admin/variants/{id}` with `{price_amount}`. An empty cell is skipped, so leaving the placeholder value untouched does not rewrite the price.
- The variant price input carries the variant id in `data-variant-price`; the id is not in the row itself.
- Publish and archive are separate POST endpoints; the confirm dialog states that publishing needs at least one active SKU and one image, and the backend enforces it.
- The publish/archive buttons are rendered regardless of the current product status — the page does not hide them for an already-published product.
- `categories` and `media` are read from the API but there is no attach/detach UI: the media grid is read-only and the "Ảnh bìa" marker is derived from `is_primary` or the first item in the array.
- Variant status is a raw `ui/Badge.astro` (active → success, else neutral) — there is no domain badge for variant status.

### Testing Requirements
- `pnpm build` in `frontend-admin/`.
- Runtime: backend up; change a price, save, publish, then check the storefront page at `:4321`.
- No test runner is configured.

## Dependencies

### Internal
- `../../../lib/api.ts` — `apiGet`, `requireStaff`, `mutate`
- `../../../lib/formatters.ts` — `formatCurrency` (integer VND)
- `../../../components/ui/Input.astro`, `../../../components/ui/Button.astro`, `../../../components/ui/Badge.astro`
- `../../../layouts/AdminLayout.astro`

### External
- Backend REST API `/api/v1/admin` at `PUBLIC_API_URL` (default `http://localhost:8000`) — Sanctum session auth + `perm:*` middleware

<!-- MANUAL: -->
