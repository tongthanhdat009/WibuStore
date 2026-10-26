<!-- Parent: ../../AGENTS.md -->
<!-- Generated: 2026-10-22 | Updated: 2026-10-22 -->

# Admin

## Purpose
Back-office endpoints under `/api/v1/admin`. Every route carries `auth:sanctum` plus a `perm:<slug>` middleware, and every mutation writes an `ActivityLog` row.

## Key Files
| File | Description |
|------|-------------|
| `AdminCatalogController.php` | Products/variants (publish, archive), categories, series, manufacturers, review moderation |
| `AdminCmsController.php` | Media upload/complete/delete, CMS pages + revisions + publish, coupons, activity logs, users, roles, settings |
| `AdminOperationsController.php` | Dashboard overview + work queue, orders + transitions, inventory, preorder campaigns/reservations, payment confirm/reject, customers, sales report, payment webhook |
| `RbacController.php` | Permissions, role CRUD, staff (user) CRUD with self- and last-super-admin guards |

## For AI Agents

### Working In This Directory
- Permission slugs in use: `reports.read`, `products.read`, `products.write`, `products.publish`, `orders.transition`, `inventory.adjust`, `preorders.manage`, `payments.confirm`, `users.manage`, `cms.publish`, `settings.manage`.
- `RbacController::destroyRole` refuses to delete the `super_admin` or `customer` roles (403 `FORBIDDEN`) and refuses to delete a role that still has users (409 `INVALID_STATE`).
- `RbacController::updateStaff` blocks a user from changing their own roles (403 `FORBIDDEN`). `RbacController::destroyStaff` blocks self-deletion and blocks deleting the last remaining `super_admin` (403 `FORBIDDEN`).
- `RbacController::staff()` lists staff only — users with no roles, or holding a role whose slug is not `customer` — and searches name/email with Postgres `ilike`.
- Role edits have two paths: the legacy `PUT /admin/users/{id}/roles` in `AdminCmsController::updateRoles` (also blocks self-assignment, and requires `users.manage` to grant `super_admin`) and the newer `/admin/rbac/*` + `/admin/staff` endpoints in `RbacController`. Do not add a third path for role mutation.
- `AdminCatalogController::publish` refuses to publish a product with no active variant or no media (422 `VALIDATION_ERROR`); `archive` only flips `status` to `archived`.
- `AdminCmsController::deleteMedia` refuses to delete an asset still referenced by `product_media` (409 `INVALID_STATE`).
- `AdminCmsController::media` POST stores the upload on `config('filesystems.default')` and writes a `media_assets` row with a sha256 checksum; the key layout is `<visibility>/products/YYYY/MM/<uuid>.<ext>`.
- `AdminOperationsController::webhook` is the only unauthenticated admin action — it is declared outside the `admin` prefix group in `routes/api.php` and is CSRF-exempt (`api/v1/webhooks/*`). It delegates to `PaymentService::handleWebhook()`, which writes a `payment.webhook` activity log and pushes the order to Odoo when the event actually confirms a payment.
- `AdminOperationsController` constructor-injects `PreorderService`; `OrderService`, `InventoryService` and `PaymentService` are method-injected per action.
- Postgres-only SQL: `ilike` filters and `date_trunc` in `salesReport`. The app does not run on MySQL/SQLite.
- `AdminOperationsController::adjust` requires a non-zero `delta_on_hand` and a `reason`; the warehouse defaults to `InventoryService::defaultWarehouseId()`, which creates the `default` warehouse row when it is missing.

### Testing Requirements
- `make test` from the repo root (DB `wibu_store_test`); `make pint` is the style gate. Invoke PHP only through the root `make` targets, not `php artisan` directly. The runtime is host PHP (8.3+), not Docker — see `backend/AGENTS.md`.
- `Feature/RbacTest.php` asserts admin routes require auth and the matching permission, and that guest order lookup leaks nothing.

### Common Patterns
- Mutations call `ActivityLog::record('<entity>.<verb>', '<entity>', (string) $id, $before, $after)`; `actor_user_id` defaults to the authenticated user.
- Read endpoints paginate server-side; `per_page` is clamped to `max(1, min(100, $per_page))`.
- Money is integer VND in every payload, including `amount` and `delta_on_hand`.

## Dependencies

### Internal
- `App\Domain\Inventory\InventoryService`, `App\Domain\Orders\OrderService`, `App\Domain\Payments\PaymentService`, `App\Domain\Preorders\PreorderService`, `App\Http\Requests\StoreProductRequest`, `App\Models\*`.

### External
- `laravel/framework:13`, `barryvdh/laravel-dompdf`

<!-- MANUAL: -->
