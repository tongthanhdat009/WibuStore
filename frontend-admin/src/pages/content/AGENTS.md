<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-10-22 | Updated: 2026-10-22 -->

# content

## Purpose
The homepage CMS editor. It reads the `home` page revisions, exposes the hero block and the section enable/order switches as a form, and writes the result back as a new revision.

## Key Files
| File | Description |
|------|-------------|
| `homepage.astro` | `GET /admin/pages/home/revisions`; create `POST /admin/pages/home/revisions`; publish `POST /admin/pages/home/revisions/{id}/publish`. Renders the published JSON verbatim next to the form. |

## For AI Agents

### Working In This Directory
- The page slug is hardcoded to `home` in every call — this screen edits only the storefront homepage.
- All endpoints are gated by `perm:cms.publish`.
- On save the client script re-reads the current revisions with a client-side `mutate('/admin/pages/home/revisions', 'GET')` and merges the form into the published section list. There is no PATCH — every save creates a new revision.
- The content shape is `{schema_version: number, sections: [{type, enabled, sort_order, …}]}`. Known editable types: `hero_comic`, `trending`, `preorder_spotlight`, `new_arrivals`. Types present in the stored content but not in the form are preserved on save but not editable.
- The hero form writes a field only when its value is non-empty, so clearing the headline in the UI does **not** clear it in the stored content.
- `save(false)` creates a draft; `save(true)` creates the draft and then publishes it.
- Publishing is atomic server-side and busts the storefront cache — the `:4321` homepage updates immediately.
- The revision history table is the same widget as in `../collections/[id]/edit.astro`; the "Xuất bản" button on an existing revision asks for confirmation first.

### Testing Requirements
- `pnpm build` in `frontend-admin/`.
- Runtime: backend up; toggle a section, save, publish, then check `:4321`.
- No test runner is configured.

## Dependencies

### Internal
- `../../lib/api.ts` — `apiGet`, `requireStaff`, `mutate`
- `../../components/ui/Button.astro`, `../../components/ui/Badge.astro`
- `../../layouts/AdminLayout.astro`

### External
- Backend REST API `/api/v1/admin` at `PUBLIC_API_URL` (default `http://localhost:8000`) — Sanctum session auth + `perm:*` middleware

<!-- MANUAL: -->
