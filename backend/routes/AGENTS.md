<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-10-18 | Updated: 2026-10-18 -->

# routes

## Purpose
Route definitions. `api.php` = whole API (public/auth/customer/admin); `web.php`/`console.php` stock.

## For AI Agents
- Add routes in `api.php` under correct group (public vs `auth:sanctum` vs `admin`+`perm:*`). Throttle auth/checkout/lookup.

<!-- MANUAL: -->
