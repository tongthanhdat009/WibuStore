<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-10-22 | Updated: 2026-10-22 -->

# settings

## Purpose
The system section: store configuration, staff accounts, RBAC roles and permissions, and the activity log. One page here (`team.astro`) is a static redirect and does no API work.

## Key Files
| File | Description |
|------|-------------|
| `store.astro` | Store config. `GET /admin/settings` → flat `{key: value}` map; `PATCH /admin/settings` with `{settings: {key: value}}`. |
| `staff.astro` | Staff list. `GET /admin/staff?per_page=20&page=` + `/admin/rbac/roles`; `POST /admin/staff`, `PATCH /admin/staff/{id}`, `DELETE /admin/staff/{id}`. Per-member role editor: `staff/[id].astro`. |
| `roles.astro` | Roles & permissions. `GET /admin/rbac/roles` + `/admin/permissions`; `POST /admin/rbac/roles`, `PATCH /admin/rbac/roles/{id}`, `DELETE /admin/rbac/roles/{id}`. |
| `audit-logs.astro` | Activity log. `GET /admin/activity-logs?per_page=20&page=`. CSV export writes `activity-logs.csv`. |
| `team.astro` | Static — `Astro.redirect('/settings/staff')`. No API call, no `requireStaff` guard. |

## For AI Agents

### Working In This Directory
- Every file here except `team.astro` starts with `const { redirect } = await requireStaff(Astro); if (redirect) return redirect;`.
- Permissions differ per screen: store config needs `perm:settings.manage`, staff/roles/logs need `perm:users.manage`.
- `GET /admin/settings` returns a flat key→value map (not `{settings: {...}}`) — the page reads it directly. `PATCH /admin/settings` takes `{settings: {...}}` and upserts one row per key, so unknown keys are stored as-is.
- `store.astro` collects every enabled `input[name]`. The `timezone` input is `disabled`, so it is never sent and its displayed value is a hardcoded default.
- Staff endpoints: the list excludes users whose only role is `customer`. `POST /admin/staff` requires name, unique email, a password of ≥8 chars, and takes `role_ids: [id]`. `PATCH /admin/staff/{id}` accepts `status` (`active`/`suspended`) and `role_ids` — sending `role_ids` for your own account is rejected with 403 "Khong tu sua vai tro cua chinh minh". `DELETE` refuses to delete yourself or the last super admin. `staff.astro` only hides the delete button client-side; the backend check is the real one.
- Role endpoints: `POST /admin/rbac/roles` requires a unique slug. `PATCH /admin/rbac/roles/{id}` syncs `permission_ids`. `DELETE` refuses `super_admin`/`customer` and a role still assigned to staff (409 `INVALID_STATE`); the delete button is hidden for those two slugs.
- `roles.astro` groups permissions by the text before the first `.` in the permission slug and labels the groups with a local `GROUP_TITLES` map. A new permission namespace shows the raw slug as the group title until the map is extended.
- `audit-logs.astro` maps action codes to Vietnamese labels with a local `ACTION_LABELS` map; unknown codes fall back to the raw code. The "Xuất tệp nhật ký" button (`id="export-csv-btn"`) now exports: a `<script>` fetches `GET /admin/activity-logs?per_page=100` with `credentials: 'include'` and writes `activity-logs.csv` (`Thoi gian`, `Nguoi thuc hien`, `Action`, `Tai nguyen`, `ID`, `Request ID`) through `downloadCSV` — the same client-side export pattern as `/customers`, `/orders`, `/products`, `/analytics` and `/analytics/products`. On failure it alerts `Xuat that bai. Kiem tra dang nhap.`
- `team.astro` is the one static page in the admin: a bare redirect, not linked from the sidebar, no API call. Do not describe it as wired to the staff API.

### Testing Requirements
- `pnpm build` in `frontend-admin/`.
- Runtime: backend up; try creating a staff account, editing a role's permissions, and saving the store config.
- No test runner is configured.

## Dependencies

### Internal
- `../../lib/api.ts` — `apiGet`, `currentPage`, `requireStaff`, `mutate`
- `../../lib/export.ts` — `downloadCSV` (audit-log export)
- `../../components/ui/Pagination.astro`, `../../components/ui/Input.astro`, `../../components/ui/Button.astro`, `../../components/ui/Badge.astro`
- `../../layouts/AdminLayout.astro`

### External
- Backend REST API `/api/v1/admin` at `PUBLIC_API_URL` (default `http://localhost:8000`) — Sanctum session auth + `perm:*` middleware

<!-- MANUAL: -->
