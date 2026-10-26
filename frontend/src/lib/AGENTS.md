<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-10-22 | Updated: 2026-10-22 -->

# lib

## Purpose
Client-side data layer for the storefront: a typed Laravel API client, an API-first catalog facade with a filtered static fallback, Sanctum session auth, localStorage cart and wishlist, the homepage CMS reader, and the VND/status formatters.

## Key Files
| File | Description |
|------|-------------|
| `api.ts` | Typed API client. Exports `API_URL` (`PUBLIC_API_URL`, default `http://localhost:8000`, trailing slash stripped), `ApiProductItem`, `ApiProductDetail`, `ApiVariant`, `Paginated`, `mapCategory`, `fetchProducts` (returns `null` when the API is unreachable, `[]` when it answered with no rows), `fetchProductDetail`, `fetchMeta`, `fetchPreorders`, `toStoreProduct`, `createOrder`, `previewTotals`, `getGuestToken` |
| `catalog.ts` | API-first facade with a filtered static fallback. `getCatalogProducts`, `getAllCatalogProducts`, `getProductBySlug` return `{ products, live }`; `getAllSlugs` returns `string[]`; `fetchTaxonomy` returns `TaxonomyMeta[]` |
| `auth.ts` | Customer session auth. `customerLogin`, `customerRegister`, `customerLogout`, `customerMe`, `myOrders`, `myAddresses`, `lookupOrder`; the `CustomerMe` type carries `roles` and `permissions` |
| `cart.ts` | localStorage cart under `wibu_store_cart_v2`. `getStoredCart`, `saveCart`, `addToCartItem`, `updateCartQuantity`, `removeCartItem`, `clearCart`, `syncItemToServer`, `syncLocalCartToServer` |
| `wishlist.ts` | localStorage wishlist under `wibu_store_wishlist_v2`. `getStoredWishlist`, `saveWishlist`, `toggleWishlist`, `fetchServerWishlist`, `syncLocalWishlistToServer` |
| `cms.ts` | Homepage CMS reader. `getHomeContent()` reads `GET /api/v1/pages/home` and returns `HomeContent {schema_version, sections[]}`; `findSection`, `isSectionEnabled` |
| `utils.ts` | `formatVND` (vi-VN, zero decimals), `formatNumber`, `truncateText`, `getStatusBadgeInfo` |

## For AI Agents

### Working In This Directory
- `catalog.ts` is the only supported way for a page to read products. It returns `{ products, live }` — `live: false` means the API was unreachable and a **filtered** subset of `src/data/products.ts` was substituted. The offline fallback applies the same filters the API applies (`category` → `FigureCategoryType` via `mapCategory`, `anime_series` → `seriesSlug`, `q` → name / vietnameseTitle / character, case-insensitive). Pages must render identically in both cases.
- Paging in `catalog.ts` is capped at 10 pages x 48 per page (`getAllCatalogProducts`, `getAllSlugs`). A catalog larger than 480 products is silently truncated.
- `getCatalogProducts` fetches detail for only the first 24 items; the rest are mapped from the list payload alone (no gallery, no variants beyond the first).
- `getAllCatalogProducts` and `getAllSlugs` track reachability separately from emptiness: a reachable API that answers with zero rows yields an empty list (`live: true`), not the static catalogue. `getAllSlugs` is the exception — it still falls back to the static slugs when the reachable API returned no rows.
- `api.ts` translates API category slugs into `FigureCategoryType` through `CATEGORY_MAP`, which now holds only the canonical slugs: `scale`/`scale-figures` → `scale`, `nendoroid` → `nendoroid`, `action`/`action-figures` → `action`, `model_kit`/`model-kits` → `model_kit`. The old seeded slugs (`scale-figure`, `scale-figure-seed`, `chibi-seed`, `cosplay-seed`, `set-figure-seed`) are gone — a backend migration renamed them to the storefront vocabulary, so there is one slug namespace. An unknown slug falls back to `scale`. `mapCategory` is exported so `catalog.ts` can reuse it for the offline filter.
- `toStoreProduct` is the single place where API payloads become storefront `Product` objects. Do not spread API shapes into components.
- `toStoreProduct` sets `status` from the API's real `availability` via `mapAvailability` (`sold_out`/`pre_order` pass through, anything else → `in_stock`). It used to hardcode `in_stock`, so every product showed as in stock regardless of real stock or pre-order state.
- Every fetch is wrapped in try/catch and returns `null` / `[]` / the static fallback on failure — a dead backend must never throw during a build. `fetchProducts` separates the two empty cases: `null` = the API was unreachable, `[]` = the API answered with no rows. Callers must not collapse them.
- XSRF handling is duplicated in `api.ts`, `auth.ts` and `cart.ts`: read the `XSRF-TOKEN` cookie, `GET /sanctum/csrf-cookie` when it is absent, then send `X-XSRF-TOKEN`. `credentials: 'include'` is required on every authenticated call.
- `createOrder` sends an `Idempotency-Key` header and a `checkout_type` of `regular`; `checkout.astro` overrides it to `preorder` for carts that contain a pre-order item.
- Money is integer VND end to end — never divide by 100 and never use float currency.
- `cms.ts` deliberately defaults a missing CMS entry to enabled (`isSectionEnabled` returns `true` when no section is found), so the storefront keeps rendering when the CMS is empty or offline.

### Testing Requirements
- `pnpm build` in `frontend/` with the backend up (live path) and down (fallback path).
- No test runner is configured in this package.

### Common Patterns
- New API call → add the raw shape and a typed function to `api.ts`; expose it through `catalog.ts` if it is catalog data, then import it in pages.
- New localStorage collection → mirror `cart.ts`: read/write helpers that tolerate `typeof window === 'undefined'`, plus a `CustomEvent` so already-mounted components can react.
- Format values with `utils.ts`; do not inline `Intl` calls in pages.
- Authenticated calls → `credentials: 'include'` + the XSRF header, following `auth.ts`.

## Dependencies

### Internal
- `../data/products.ts` — the static fallback consumed by `catalog.ts`
- `../types/index.ts` — `Product`, `FigureCategoryType`, `StockStatusType`
- `../components/cart/CartDrawer.astro`, `../pages/cart.astro`, `../pages/checkout.astro` — consume `lib/cart.ts`
- `../pages/wishlist.astro`, `../components/catalog/ProductCard.astro` — consume `lib/wishlist.ts`
- `../pages/index.astro` — consumes `lib/cms.ts`
- `../pages/login.astro`, `register.astro`, `account.astro`, `lookup.astro` — consume `lib/auth.ts`

### External
- Backend REST API `/api/v1` at `PUBLIC_API_URL` (default `http://localhost:8000`)
- Browser APIs: `fetch`, `localStorage`, `document.cookie`, `crypto.randomUUID`

<!-- MANUAL: -->
