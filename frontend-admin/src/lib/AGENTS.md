<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-10-22 | Updated: 2026-10-22 -->

# lib

## Purpose
The admin's only data layer: an SSR API client that preserves the Sanctum session, a client-side mutation helper with CSRF handling, vi-VN formatters, and a CSV exporter.

## Key Files
| File | Description |
|------|-------------|
| `api.ts` | `API_URL` (`PUBLIC_API_URL`, default `http://localhost:8000`, trailing slash stripped); `apiGet<T>(path, astro?)` → `{data?, meta?, error?}`; `mutate(path, method, body?)`; `currentPage(astro)`; `requireStaff(astro, loginPath?)`; `staffLogin`, `staffLogout`, `guardClient`; types `ApiError`, `PageMeta`, `StaffMe` |
| `formatters.ts` | `formatCurrency` (vi-VN, zero decimals), `formatNumber`, `formatDate`, `formatDateTime`, `formatBytes` |
| `export.ts` | `downloadCSV(filename, headers, rows)` — builds a CSV blob with a UTF-8 BOM, quotes cells containing `,`, `"` or newline, and triggers a browser download |

## For AI Agents

### Working In This Directory
- `apiGet` forwards the incoming browser `Cookie` header and sets `Origin` + `Referer` to the SSR page origin. That is what makes Sanctum start/continue a session server-side. Removing those headers turns every page into a 401 — this is the single most important invariant in the admin.
- `apiGet` never throws. It returns `{data}` on success, or `{error: {status, code, message}}` where `status` is 401 (`UNAUTHENTICATED`), 403 (`FORBIDDEN`), the real HTTP status for other failures, or 0 with code `NETWORK` when the backend is unreachable. Always branch on `error`, not on a thrown exception.
- The response envelope is `{data, meta}` — `meta` is populated only when the body carries a `current_page` number; it is shaped into `PageMeta` (`current_page`, `last_page`, `per_page`, `total`, `from`, `to`).
- `requireStaff(astro)` calls `/me` and returns `{redirect, staff}`. `redirect` is a `Response` on 401 (`/login`) or 403 (`/403`); pages must `if (redirect) return redirect;` before rendering. `staff` is the `StaffMe` object with `roles` and `permissions`.
- `mutate` is client-side only: it fetches `/sanctum/csrf-cookie` when the `XSRF-TOKEN` cookie is missing, sends `X-XSRF-TOKEN`, and uses `credentials: 'include'`. Never call it during SSR — `document` is undefined there.
- `guardClient(adminOnly)` is the browser-side guard run by `AdminLayout.astro`; it redirects to `/login` or `/403` when the session is not an admin role.
- `currentPage(astro)` parses `?page=` and clamps it to a positive integer.
- Money is integer VND. `formatCurrency` uses `Intl` vi-VN with `maximumFractionDigits: 0`; never divide by 100.
- Several pages re-declare `const base = (import.meta.env.PUBLIC_API_URL ...) || 'http://localhost:8000'` inline for CSV export URLs instead of importing `API_URL`. Prefer `API_URL` in new code.

### Testing Requirements
- `pnpm build` in `frontend-admin/`.
- No test runner is configured.

### Common Patterns
- Read in the page frontmatter: `const { data, meta, error } = await apiGet<T>('/admin/orders?...', Astro);`
- Write in a client `<script>`: `const { error } = await mutate('/admin/products/' + id, 'PATCH', payload);`
- Guard the page: `const { redirect } = await requireStaff(Astro); if (redirect) return redirect;`
- Paged list: `currentPage(Astro)` for the page number, `apiGet` with `per_page`/`page`, then `<Pagination meta={meta} pathname={Astro.url.pathname} params={{...}} />`.

## Dependencies

### Internal
- `../components/ui/Pagination.astro` — consumes the `PageMeta` type from here
- `../components/shell/Topbar.astro` — uses `guardClient` and `staffLogout`
- `../layouts/AdminLayout.astro` — runs `guardClient` in its client script
- `../pages/**` — every page imports `apiGet` / `requireStaff` / `mutate` from here

### External
- Backend REST API `/api/v1` at `PUBLIC_API_URL` (default `http://localhost:8000`)
- Browser APIs: `fetch`, `document.cookie`, `URL.createObjectURL`, `Blob`

<!-- MANUAL: -->
