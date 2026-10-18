<!-- Parent: ../../../AGENTS.md -->
<!-- Generated: 2026-10-18 | Updated: 2026-10-18 -->

# V1

## Purpose
Versioned API surface. `AuthController` (register/login/logout/me/addresses), `CartController` (cart/checkout/orders/lookup), `Public/CatalogController` (products/categories/series/preorders/pages), `Admin/` trio.

## Subdirectories
| Directory | Purpose |
|-----------|---------|
| `Admin/` | `AdminCatalogController` (products/variants/publish), `AdminCmsController` (media/pages/coupons/users/settings), `AdminOperationsController` (orders/inventory/preorders/payments/reports/webhook) |
| `Public/` | `CatalogController.php` — all public reads + health |

## For AI Agents
- Public reads stay unauthenticated; everything under `admin/` needs `auth:sanctum` + `perm:*`.
- Webhook route lives in OperationsController, no CSRF — gateway verifies signature.

<!-- MANUAL: -->
