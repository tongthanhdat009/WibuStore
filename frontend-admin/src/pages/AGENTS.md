<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-10-18 | Updated: 2026-10-18 -->

# pages

## Purpose
Mock-data admin screens: dashboard `index`, `products/` (index/new/[id]), `orders/`, `inventory/`, `preorders/`, `customers/`, `collections/`, `content/homepage`, `analytics/` (index/products/inventory), `settings/` (store/team/audit-logs), auth (`login`/`forgot-password`), `403`/`404`, + `brands/categories/media/promotions/reviews/series/tasks`.

## For AI Agents
- All data from `data/mockData.ts` typed by `types/index.ts`; wrap in `AdminLayout` (auth pages `AuthLayout`).
- Wiring real endpoint = swap one page's mock import for API call; keep badge/formatter usage.

<!-- MANUAL: -->
