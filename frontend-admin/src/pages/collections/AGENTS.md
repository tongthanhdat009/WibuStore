<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-10-22 | Updated: 2026-10-22 -->

# collections

## Purpose
Lists the CMS landing pages ("collections") and creates new ones. The `home` page is excluded here because it is edited in `/content/homepage`; this screen only manages the themed collection pages.

## Key Files
| File | Description |
|------|-------------|
| `index.astro` | Grid of CMS pages. `GET /admin/pages`; create modal posts `POST /admin/pages` with `{slug, title}`. |

## For AI Agents

### Working In This Directory
- Both calls hit `/admin/pages`, gated by `perm:cms.publish`. A 403 means the account cannot publish CMS content.
- The list endpoint returns every CMS page; the screen filters out `slug === 'home'` client-side. The `home` page is edited at `/content/homepage`.
- `POST /admin/pages` uses `firstOrCreate` on the slug server-side, so re-submitting an existing slug silently returns the existing page instead of raising a duplicate error. The modal shows no difference between "created" and "already existed".
- The slug must be readable by the storefront: the convention is `collection-<name>` (the "Xem storefront" link strips that prefix before building the `:4321` URL).
- The card badge shows `published_revision.version_number` when a revision is published, else "Chưa xuất bản". `published_revision` comes from the backend's `with('publishedRevision:…')` eager load.
- The create form uses raw `<input>` elements, not `ui/Input.astro`. Errors render into `#page-error`; the modal is opened/closed by toggling `hidden`/`flex`.
- Revisions for a page are edited at `/collections/{id}/edit` — the id in the URL is the CMS page **id**, not the slug.

### Testing Requirements
- `pnpm build` in `frontend-admin/`.
- Runtime: backend up, then create a page and check it appears in the grid and at `/collections/{id}/edit`.
- No test runner is configured.

## Dependencies

### Internal
- `../../lib/api.ts` — `apiGet`, `requireStaff`, `mutate`
- `../../components/ui/Button.astro`, `../../components/ui/Badge.astro`
- `../../layouts/AdminLayout.astro`

### External
- Backend REST API `/api/v1/admin` at `PUBLIC_API_URL` (default `http://localhost:8000`) — Sanctum session auth + `perm:*` middleware

<!-- MANUAL: -->
