<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-10-18 | Updated: 2026-10-18 -->

# Http

## Purpose
HTTP layer: versioned API controllers, JSON enforcement, per-permission gates, validation + shaping.

## Subdirectories
| Directory | Purpose |
|-----------|---------|
| `Controllers/Api/V1/` | `Auth`/`Cart` + `Admin/` (catalog/cms/ops) + `Public/` (catalog) — see `Api/V1/AGENTS.md` |
| `Middleware/` | `ForceJson.php` (always JSON), `RequirePermission.php` (`perm:*`) |
| `Requests/` | `StoreCheckoutRequest.php`, `StoreProductRequest.php` |
| `Resources/` | `OrderResource`, `ProductResource`, `ProductDetailResource` |

## For AI Agents
- New endpoint → controller method + FormRequest + Resource; admin routes need `perm:xxx` in `routes/api.php`.
- Never return HTML from API; `ForceJson` + `/login` 401 fallback enforce it.

<!-- MANUAL: -->
