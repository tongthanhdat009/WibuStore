<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-10-22 | Updated: 2026-10-22 -->

# resources

## Purpose
Server-rendered assets for the API: Blade views, the Tailwind CSS entry and the Vite JS entry. The public storefront and admin are separate Astro apps — this directory serves only the Laravel side.

## Key Files
| File | Description |
|------|-------------|
| `css/app.css` | Tailwind v4 entry: `@import 'tailwindcss'`, `@source` for the Laravel pagination blade views and compiled views, `@theme --font-sans` = Instrument Sans |
| `js/app.js` | Empty Vite entry (single `//` line) — no client JS shipped yet |
| `views/welcome.blade.php` | Stock Laravel 13 welcome page (223 lines, Tailwind CSS inlined as a fallback plus `@vite`/`@fonts`). Served by `GET /` in `routes/web.php` |
| `views/pdf/invoice.blade.php` | dompdf HTML template for the invoice PDF: expects `$order` and `$address`, prints VI labels and VND via `number_format($amount, 0, ',', '.')` + `₫` |

## Subdirectories
| Directory | Purpose |
|-----------|---------|
| `css/` | Tailwind v4 entry point consumed by `vite.config.js` |
| `js/` | Vite JS entry point |
| `views/` | Blade templates (`welcome.blade.php`, `pdf/`) |

## For AI Agents

### Working In This Directory
- `views/pdf/invoice.blade.php` is the only business-facing template. It is rendered by `CartController::invoice()` for `GET /api/v1/orders/{publicId}/invoice` (and the admin invoice route) via `loadView('pdf.invoice', ['order' => ..., 'address' => ...])`. Change the variables it expects and you break the invoice endpoint — keep both in sync.
- dompdf cannot run modern CSS: the invoice template uses inline `<style>` only, `DejaVu Sans` for Unicode, and simple tables. No flexbox/grid.
- Money in the template is `number_format($value, 0, ',', '.')` — integer VND in, formatted string out. Never divide by 100.
- `welcome.blade.php` is unmodified Laravel scaffolding; it inlines the compiled Tailwind CSS so the page renders without a Vite build. Rebuilding assets overwrites that block — keep it, or drop the fallback deliberately.
- The API itself returns JSON; no new Blade views are needed for endpoints. Only add a view for server-rendered pages (web routes, PDFs).
- `@vite` needs `public/build/manifest.json`; the repo currently has no `public/build` directory, so the inlined fallback is what renders.

### Testing Requirements
- No test asserts on these files except `tests/Feature/ExampleTest.php`, which hits `GET /` and expects HTTP 200.
- Run `make test` from the repo root after changing `welcome.blade.php` or the PDF template.

### Common Patterns
- Assets are built with Vite from the backend root (`vite.config.js`); `npm run build` outputs to `public/build`.

## Dependencies

### Internal
- `app/Http/Controllers/Api/V1/CartController.php` → `pdf.invoice` view.
- `routes/web.php` → `welcome` view.
- `backend/vite.config.js` → `resources/css/app.css` + `resources/js/app.js`.

### External
- `laravel/framework:13` Blade, `barryvdh/laravel-dompdf`, Tailwind v4 + Vite.

<!-- MANUAL: -->
