<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-10-18 | Updated: 2026-10-18 -->

# src

## Purpose
Admin dashboard source: mock-data pages, shell + badge components, vi-VN formatters.

## Subdirectories
| Directory | Purpose |
|-----------|---------|
| `pages/` | Dashboard, products, orders, inventory, preorders, customers, content, analytics, settings, auth |
| `components/` | `shell/` (Sidebar/Topbar), `domain/` (status badges, MetricCard), `ui/` (Badge/Button/Input) |
| `data/` | `mockData.ts` — all mock entities until API wired |
| `lib/` | `formatters.ts` (currency/date vi-VN) |
| `layouts/` | `AdminLayout.astro`, `AuthLayout.astro` |
| `types/` / `styles/` | Shared types, `global.css` |

## For AI Agents
- Status display: reuse `domain/*Badge.astro`, never inline colors. Money/dates via `formatters.ts`.
- Mock edits stay in `mockData.ts` typed by `types/index.ts`.

<!-- MANUAL: -->
