<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-10-22 | Updated: 2026-10-22 -->

# views

## Purpose
Blade templates for the Laravel side: the one HTML page the API host serves and the dompdf invoice template. The storefront and admin UIs are separate Astro apps and have no templates here.

## Key Files
| File | Description |
|------|-------------|
| `welcome.blade.php` | Stock Laravel 13 welcome page (223 lines). Served by `GET /` from `routes/web.php`; inlines a compiled Tailwind snapshot as a no-build fallback |
| `pdf/invoice.blade.php` | dompdf template for the order invoice PDF — see `pdf/AGENTS.md` |

## For AI Agents

### Working In This Directory
- `welcome.blade.php` is live, not leftover scaffolding: `routes/web.php` registers `Route::get('/', fn () => view('welcome'))`. It is the only HTML the app serves.
- The `@vite` call is conditional on `public_path('build/manifest.json')` or `public_path('hot')`. Neither exists (`/public/build` is gitignored), so the page renders the inlined `<style>` block on line 16 — a `tailwindcss v4.0.7` snapshot frozen at build time. Editing `resources/css/app.css` does not change the page until `npm run build` creates the manifest and flips the branch. The inline block is a copy of the compiled output, not a source of truth; regenerate it or drop it deliberately.
- `@fonts` (line 9) has no registered Blade directive in this app — no `laravel/fonts` composer package, and `bootstrap/providers.php` lists only `AppServiceProvider`. Blade emits the literal text `@fonts` into the HTML. Harmless, but it means the Instrument Sans webfont is never linked; the inlined CSS only names the family.
- `Route::has('login')` is TRUE: `routes/api.php:14` registers `Route::get('/login', ...)->name('login')` inside the api group (URI `api/login`, returns a JSON 401). The page therefore renders a "Log in" link to `/api/login`, which returns JSON, not a form. `Route::has('register')` is false — the register route (`/api/v1/auth/register`) is unnamed — so no register link is shown.
- The `@auth` branch links to `url('/dashboard')`. No `/dashboard` route exists in this app (the admin dashboard is the Astro app at `frontend-admin/`), so that link 404s with Laravel's HTML error page — the JSON `{error:{code,message}}` contract only applies under `api/*`.
- `<title>` is `config('app.name')`, i.e. `APP_NAME` from `.env` (`"Wibu Store API"`), not a literal.
- No API endpoint needs a Blade view. Add one only for server-rendered pages (web routes, PDFs); everything else returns JSON.

### Testing Requirements
- `tests/Feature/ExampleTest.php` is the only test touching this directory: it asserts `GET /` returns 200.
- Run `make test` from the repo root after changing either template.
- The PDF template has no automated coverage — assert it manually via the invoice endpoint.

## Dependencies

### Internal
- `routes/web.php` — the `welcome` view.
- `app/Http/Controllers/Api/V1/CartController.php` and `Api/V1/Admin/AdminOperationsController.php` — render `pdf.invoice`.
- `resources/css/app.css` — the CSS this page loads via `@vite`.

### External
- `laravel/framework:13` Blade, `barryvdh/laravel-dompdf`.

<!-- MANUAL: -->
