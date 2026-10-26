<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-10-22 | Updated: 2026-10-22 -->

# pages

## Purpose
File-routed storefront — 23 routes. Astro's static output with `getStaticPaths()` on the dynamic routes; catalog data comes from `lib/catalog.ts` (API-first, static fallback) and the homepage additionally reads the CMS.

## Key Files
| File | Description |
|------|-------------|
| `index.astro` | Homepage. `getCatalogProducts()` + `getHomeContent()`; static `heroCampaigns`/`categories`/`products`/`anime`/`collections`; mounts `VideoStory`; sections `hero_comic`, `preorder_spotlight`, `new_arrivals` are gated by the CMS |
| `figures.astro` | Catalog. `getAllCatalogProducts()`; renders every product server-side and filters/sorts client-side from URL params via `ProductCard`'s `data-*` attributes |
| `products/[slug].astro` | Product detail. `getStaticPaths()` from `getAllSlugs()` + `getProductBySlug`; related products from `getCatalogProducts({per_page: 12})` |
| `collections/index.astro` | Collection grid. Static `data/collections.ts` + raw `GET /api/v1/collections` + `getProductBySlug` for cover images |
| `collections/[slug].astro` | Collection detail. `getStaticPaths()` unions static slugs with `GET /api/v1/collections`; body from `GET /api/v1/collections/{slug}` + `getProductBySlug` |
| `anime/[slug].astro` | Series page. `fetchTaxonomy('/anime-series')` + `getCatalogProducts({anime_series: slug})`; static `data/anime.ts` supplies the banner and Japanese title |
| `categories/[slug].astro` | Category page. `fetchTaxonomy('/categories')` + `getCatalogProducts({category: slug})`; static `data/categories.ts` supplies the accent colour and badge |
| `pre-orders.astro` | Pre-order campaigns. `fetchPreorders()` + `getProductBySlug` for the product image and name |
| `search.astro` | Search results. `getCatalogProducts({q})` — falls back to the full static list when `q` is empty |
| `checkout.astro` | Checkout. Client-side: `previewTotals` for coupon/shipping/deposit, then `createOrder` → `POST /api/v1/orders`; renders the server `WB-...` order code and clears the cart |
| `cart.astro` | Cart page. Client-side read of `getStoredCart()` with quantity/remove/clear actions |
| `wishlist.astro` | Wishlist. Static `data/products.ts` catalogue + client-side `lib/wishlist.ts` (`getStoredWishlist`, `toggleWishlist`, `fetchServerWishlist`) |
| `account.astro` | Customer area. Client-side `customerMe`, `myOrders`, `customerLogout`; invoice link points at `${API_URL}/api/v1/orders/{id}/invoice` |
| `login.astro` | Customer login — client-side `customerLogin` |
| `register.astro` | Customer registration — client-side `customerRegister` |
| `lookup.astro` | Guest order lookup — client-side `lookupOrder` (`POST /api/v1/orders/lookup`) |
| `forgot-password.astro` | Client-side `POST /api/v1/auth/forgot-password` (does not use `lib/auth.ts`) |
| `reset-password.astro` | Client-side `POST /api/v1/auth/reset-password` (does not use `lib/auth.ts`) |
| `about.astro` / `contact.astro` | Static editorial and contact copy |
| `policies/pre-order.astro`, `policies/return.astro`, `policies/shipping.astro` | Static policy pages |

## Subdirectories
| Directory | Purpose |
|-----------|---------|
| `anime/` | Series landing pages — `[slug].astro` |
| `categories/` | Category landing pages — `[slug].astro` |
| `collections/` | Collection index and detail — `index.astro`, `[slug].astro` |
| `policies/` | Static policy pages — `pre-order`, `return`, `shipping` |
| `products/` | Product detail — `[slug].astro` |

## For AI Agents

### Working In This Directory
- Every page wraps `layouts/BaseLayout.astro` and passes `title`/`description` through props.
- Catalog reads go through `lib/catalog.ts`. Do not add raw `fetch` calls for products — the fallback is what keeps the build green when the API is down. (`collections/*` are the one exception: they call `/api/v1/collections` directly and already wrap those calls in try/catch with a static fallback.)
- `figures.astro` filters client-side over `ProductCard`'s `data-*` attributes and reads URL params (`category`, `series`, `status`, `sort`, `page`). Renaming those attributes breaks filtering.
- `/figures` and `/products/[slug]` are the two heaviest pages at build time: `getAllCatalogProducts` pages 10 x 48, and `getAllSlugs` does the same for paths.
- Checkout is the only page that writes: `createOrder` sends `variant_id` + `quantity` (+ `preorder_campaign_id`) and requires items that carry a `variantId`. Items without one are dropped, and the submit button refuses with an alert.
- `checkout.astro` chooses `checkout_type: 'preorder'` when any cart item has a `preorderCampaignId`, and shows `deposit_due` instead of the full total in that case.
- Auth state is client-side only. The auth pages call `lib/auth.ts` (which sends `X-XSRF-TOKEN` and `credentials: 'include'`); `forgot-password` and `reset-password` call the API directly with their own `fetch`.
- Password-reset contract (backend `AuthController`): `POST /auth/forgot-password` takes `{email}` and returns `{data:{ok:true}}` or 422 `{error:{code:'VALIDATION_ERROR',message}}`; `POST /auth/reset-password` takes `{token, email, password, password_confirmation}` (password min 8, confirmed) and returns the same two shapes. The reset token expires after 60 min (`config/auth.php`). `MAIL_MAILER=log`, so the reset link lands in `laravel.log`, not a real inbox — the flow is not end-to-end testable without a real mailer.
- The homepage CMS is optional: `lib/cms.ts` returns `null` when the API is offline, and `isSectionEnabled` defaults a missing section to enabled, so the page still renders the static storefront.

### Testing Requirements
- `pnpm build` in `frontend/`. With the backend up, product and collection pages prerender from live data; with it down they fall back to `src/data/`.
- No test runner is configured.

### Common Patterns
- New route → add a `.astro` file here, wrap it in `BaseLayout`, read data through `lib/`, and render products with `components/catalog/ProductGrid.astro`.
- Dynamic routes need a `getStaticPaths()` that unions API slugs with the static slugs, so the page exists in both modes.
- Format money with `lib/utils.ts` → `formatVND`.

## Dependencies

### Internal
- `../layouts/BaseLayout.astro` — page shell
- `../lib/catalog.ts`, `../lib/api.ts`, `../lib/auth.ts`, `../lib/cart.ts`, `../lib/wishlist.ts`, `../lib/cms.ts`, `../lib/utils.ts`
- `../data/products.ts`, `../data/categories.ts`, `../data/anime.ts`, `../data/collections.ts`, `../data/heroCampaigns.ts`
- `../components/catalog/`, `../components/comic/`, `../components/ui/`

### External
- `backend/` `/api/v1` via `PUBLIC_API_URL` (default `http://localhost:8000`)

<!-- MANUAL: -->
