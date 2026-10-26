<!-- Parent: ../../AGENTS.md -->
<!-- Generated: 2026-10-22 | Updated: 2026-10-22 -->

# staff

## Purpose
The per-staff-member role assignment screen: pick one staff account and set the RBAC roles attached to it.

## Key Files
| File | Description |
|------|-------------|
| `[id].astro` | Loads `GET /admin/staff?per_page=100` and `GET /admin/rbac/roles`; saves `PATCH /admin/staff/{id}` with `{role_ids: [id]}`. |

## For AI Agents

### Working In This Directory
- There is no `GET /admin/staff/{id}` endpoint. The page loads the whole staff list with `per_page=100` and finds the member client-side. Anyone beyond the first 100 accounts is unreachable from this screen — a missing id redirects to `/settings/staff`.
- Both calls need `perm:users.manage`.
- The `customer` role is filtered out of the role list, so it can never be granted from here.
- Saving posts `{role_ids: [...]}` to `PATCH /admin/staff/{id}`. Only the checked boxes are sent, so unchecking every role clears the account's roles.
- The page prints "Không thể tự sửa vai trò của chính mình (an toàn)" but does not disable the button for your own account — the backend rejects a self-edit with 403 "Khong tu sua vai tro cua chinh minh", and the error is rendered into `#staff-error`.
- The account's current roles are shown as `ui/Badge.astro` pills at the top (`super_admin` → brand variant, everything else → neutral).
- The staff list endpoint excludes users whose only role is `customer`, so a customer-only account will not be found here and the page redirects.

### Testing Requirements
- `pnpm build` in `frontend-admin/`.
- Runtime: backend up; open a member from `/settings/staff`, change the roles, save, and confirm the list reflects them.
- No test runner is configured.

## Dependencies

### Internal
- `../../../lib/api.ts` — `apiGet`, `requireStaff`, `mutate`
- `../../../components/ui/Button.astro`, `../../../components/ui/Badge.astro`
- `../../../layouts/AdminLayout.astro`

### External
- Backend REST API `/api/v1/admin` at `PUBLIC_API_URL` (default `http://localhost:8000`) — Sanctum session auth + `perm:*` middleware

<!-- MANUAL: -->
