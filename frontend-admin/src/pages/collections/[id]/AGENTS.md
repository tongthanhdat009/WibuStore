<!-- Parent: ../../AGENTS.md -->
<!-- Generated: 2026-10-22 | Updated: 2026-10-22 -->

# [id]

## Purpose
The revision editor for one CMS collection page: lists the page's revisions, creates a new draft from raw JSON, and publishes a revision so the storefront picks it up.

## Key Files
| File | Description |
|------|-------------|
| `edit.astro` | `GET /admin/pages` → find page by id → `GET /admin/pages/{slug}/revisions`; create `POST /admin/pages/{slug}/revisions`; publish `POST /admin/pages/{slug}/revisions/{id}/publish`. |

## For AI Agents

### Working In This Directory
- The route param is the CMS page **id**. The page resolves it against `GET /admin/pages` first, then switches to the slug for every revision call. An unknown id silently redirects to `/collections`.
- All four endpoints are gated by `perm:cms.publish`.
- `POST /admin/pages/{slug}/revisions` validates `content` as `required|array` server-side. The textarea JSON must parse to an object or array — a bare string or number fails validation, and the error surfaces through `mutate`'s `error.message`.
- The revision body is `{content: {...}}` only. There is no title or slug field; the page row is created implicitly by `firstOrCreate` on the slug.
- Publishing (`POST …/revisions/{id}/publish`) sets the revision status to `published` and repoints `current_published_revision_id`. The storefront reads only the published revision — an unpublished draft changes nothing on `:4321`.
- The version number is assigned server-side (`max(version_number) + 1`); the client never sends it.
- The slug is passed to the client script through `define:vars` + `window.__COLLECTION_DATA__` because Astro component scripts cannot read server variables directly.
- "Lưu bản nháp" creates the draft and reloads; "Tạo & Xuất bản" creates the draft and immediately publishes it (two sequential `mutate` calls).

### Testing Requirements
- `pnpm build` in `frontend-admin/`.
- Runtime: backend up; create a draft with valid JSON, then publish it and check the storefront page.
- No test runner is configured.

## Dependencies

### Internal
- `../../../lib/api.ts` — `apiGet`, `requireStaff`, `mutate`
- `../../../components/ui/Button.astro`, `../../../components/ui/Badge.astro`
- `../../../layouts/AdminLayout.astro`

### External
- Backend REST API `/api/v1/admin` at `PUBLIC_API_URL` (default `http://localhost:8000`) — Sanctum session auth + `perm:*` middleware

<!-- MANUAL: -->
