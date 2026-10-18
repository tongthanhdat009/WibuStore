<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-10-18 | Updated: 2026-10-18 -->

# app

## Purpose
Application code: fat domain services, thin API controllers, 30 Eloquent models.

## Subdirectories
| Directory | Purpose |
|-----------|---------|
| `Domain/` | Business logic: Orders, Inventory, Payments, Preorders, Common (see `Domain/AGENTS.md`) |
| `Http/` | Controllers/Middleware/Requests/Resources (see `Http/AGENTS.md`) |
| `Models/` | 30 Eloquent models (User→Setting) |
| `Jobs/` | `SendOrderConfirmation.php` (queued mail) |
| `Providers/` | `AppServiceProvider.php` only |

## For AI Agents
- New business logic → `Domain/` service, not controller. Controllers only HTTP glue.
- Models: keep fillable/casts accurate; money integer.

<!-- MANUAL: -->
