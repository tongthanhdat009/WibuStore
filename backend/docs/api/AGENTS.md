<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-10-22 | Updated: 2026-10-22 -->

# api

## Purpose
OpenAPI 3.1 contract for the Wibu Store API. Hand-maintained — not generated from the route file.

## Key Files
| File | Description |
|------|-------------|
| `openapi.yaml` | 209-line spec: `info` (title "Wibu Store API", version 1.0.0), server `http://localhost:8000/api/v1`, 5 tags, 14 paths, `MoneyVND`/`Error` schemas + 5 shared responses |

## For AI Agents

### Working In This Directory
- Coverage is partial: 14 documented paths vs 94 route registrations in `routes/api.php`. Documented: `/health`, `/products`, `/products/{slug}`, `/preorders`, `/pages/home`, `/auth/register`, `/auth/login`, `/checkout/preview`, `/orders`, `/orders/{publicId}`, `/admin/dashboard/overview`, `/admin/orders/{id}/transition`, `/admin/payments/{id}/confirm`, `/webhooks/payments/{provider}`.
- Not documented (but implemented): product reviews, wishlists, cart item CRUD, collections, the whole admin catalog/media/CMS/coupon/RBAC/settings surface, `GET /api/login`. The spec is not a routing index — check `routes/api.php` for the truth.
- Shared schemas worth reusing instead of re-declaring:
  - `MoneyVND` — `type: integer`, `minimum: 0`, description "DO NOT use float for money. Integer VND."
  - `Error` — `{error:{code,message,details?,request_id?}}` with `code` enum: `BAD_REQUEST`, `UNAUTHENTICATED`, `FORBIDDEN`, `NOT_FOUND`, `INSUFFICIENT_STOCK`, `INVALID_STATE`, `VALIDATION_ERROR`, `RATE_LIMITED`, `INTERNAL_ERROR`.
  - Responses: `NotFound`, `Unauthenticated`, `Forbidden`, `ValidationError`, `InsufficientStock` (409).
- Path-level responses are terse (`description` only) — most schemas are not declared. Adding a response schema means adding a `components.schemas` entry first.
- Query params on `/products`: `q`, `category`, `anime_series`, `manufacturer`, `min_price`, `max_price`, `sort` (`newest|price_asc|price_desc|name_asc|featured`), `page`, `per_page` (max 48).
- `/orders` accepts an `Idempotency-Key` header and `checkout_type` (`regular|preorder`), `payment_method` (`cod|bank_transfer|gateway`) in the body.
- Keep the file in sync by hand when you add or change an endpoint — nothing regenerates it and no CI check enforces it.

### Testing Requirements
- No automated test reads this file. Validate that the YAML still parses after editing, and that documented status codes match what the controller actually returns.

### Common Patterns
- Refer to shared components via `$ref: '#/components/responses/...'` and `'#/components/schemas/...'` instead of inlining error shapes.

## Dependencies

### Internal
- `routes/api.php` — implemented routes.
- `backend/AGENTS.md` — error contract and money rules the spec encodes.

### External
- OpenAPI 3.1 tooling used by frontend consumers (`frontend/`, `frontend-admin/`).

<!-- MANUAL: -->
