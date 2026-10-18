<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-10-18 | Updated: 2026-10-18 -->

# pages

## Purpose
File-routed storefront: `index`, `cart`, `checkout`, `search`, `wishlist`, `figures`, `pre-orders`, `about`, `contact` + `products/` (detail prerendered from API), `anime/`, `categories/`, `collections/`, `policies/`.

## For AI Agents
- Catalog reads via `lib/catalog.ts` helpers (API-first + fallback). Product detail pages depend on API at build for full prerender.
- Checkout page posts `variantId` items to `POST /api/v1/orders`; render server `WB-...` code.

<!-- MANUAL: -->
