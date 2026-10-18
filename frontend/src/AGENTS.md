<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-10-18 | Updated: 2026-10-18 -->

# src

## Purpose
Storefront source: API client + static fallback data, comic-theme components, file-routed pages.

## Subdirectories
| Directory | Purpose |
|-----------|---------|
| `lib/` | `api.ts` (typed client), `catalog.ts` (API-first+fallback), `cart.ts`, `wishlist.ts`, `utils.ts` |
| `data/` | Static fallback (`products.ts`, `categories.ts`, `anime.ts`, `collections.ts`, `heroCampaigns.ts`) |
| `components/` | `layout/`, `catalog/`, `comic/`, `cart/`, `ui/` (+ empty `checkout/`, `product/`) |
| `pages/` | Routes: index/cart/checkout/products/anime/categories/collections/policies/search/wishlist |
| `layouts/` | `BaseLayout.astro` |
| `types/` / `styles/` / `assets/` | Shared types, `global.css`, images |

## For AI Agents
- Catalog reads: use `getCatalogProducts`/`getProductBySlug` from `lib/catalog.ts`, never raw fetch.
- New page: `.astro` in `pages/`, wrap `BaseLayout`, reuse `ui/` primitives.

<!-- MANUAL: -->
