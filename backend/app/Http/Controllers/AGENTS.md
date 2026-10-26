<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-10-22 | Updated: 2026-10-22 -->

# Controllers

## Purpose
Controller base class and the versioned API tree. Controllers are HTTP glue: parse input, authorise, delegate to `App\Domain` services, shape JSON.

## Key Files
| File | Description |
|------|-------------|
| `Controller.php` | Empty abstract base every API controller extends |

## Subdirectories
| Directory | Purpose |
|-----------|---------|
| `Api/` | Versioned API tree — `Api/V1/AGENTS.md` covers auth, cart, admin and public controllers |

## For AI Agents

### Working In This Directory
- `Controller.php` is an empty abstract class with no traits and no helpers — it exists only to give the tree a shared parent. Do not add behaviour to it.
- Action names must match the controller method referenced in `routes/api.php`; there is no route-model binding, so controllers resolve models themselves (`Order::where('public_id', ...)` for orders, `Product::where('slug', ...)` for products).
- Money = integer VND; error body = `{error:{code,message}}`.
- Business rules belong in `App/Domain` services. A controller that queries stock, computes totals or mutates order state directly is in the wrong layer.
- Admin controllers additionally write `ActivityLog::record(...)` after every mutation.

### Testing Requirements
- `make test` from the repo root (DB `wibu_store_test`); `make pint` is the style gate. Invoke PHP only through the root `make` targets, not `php artisan` directly. The runtime is host PHP (8.3+), not Docker — see `backend/AGENTS.md`.
- Suites: `Feature/CatalogTest.php`, `Feature/CheckoutTest.php`, `Feature/PaymentTest.php`, `Feature/PreorderTest.php`, `Feature/RbacTest.php`.

### Common Patterns
- Shape of an action: route → controller method → (FormRequest) → domain service → Resource or `response()->json(['data' => ...])`.
- Handlers that abort throw away the default Laravel body — use `abort(response()->json(['error' => [...]], $status))` to keep the repo contract.

## Dependencies

### Internal
- `App\Http\Requests\*`, `App\Http\Resources\*`, `App\Domain\*`, `App\Models\*`.

### External
- `laravel/framework:13`

<!-- MANUAL: -->
