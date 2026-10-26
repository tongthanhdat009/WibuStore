<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-10-22 | Updated: 2026-10-22 -->

# public

## Purpose
Laravel's document root: the front controller that boots the app, plus the static files a web server serves directly. Everything else in `backend/` is outside the web root.

## Key Files
| File | Description |
|------|-------------|
| `.htaccess` | Apache rewrite rules: pass `Authorization` / `X-XSRF-Token` headers through, strip trailing slashes (301), route everything else to `index.php` |
| `favicon.ico` | 0-byte placeholder — empty file, no icon data |
| `index.php` | Front controller: defines `LARAVEL_START`, loads the maintenance file, autoloader and `bootstrap/app.php`, then `$app->handleRequest(Request::capture())` |
| `robots.txt` | `User-agent: *` / `Disallow:` — allows all crawlers |

## For AI Agents

### Working In This Directory
- `index.php` reaches the app through paths relative to its own location (`__DIR__.'/../vendor/autoload.php'`, `__DIR__.'/../bootstrap/app.php'`). The document root must stay `backend/public`; moving or renaming `public/` breaks the front controller.
- `.htaccess` only works on Apache with `mod_rewrite`. Under nginx/PHP-FPM it is ignored — the front-controller rule has to live in the server config instead. The `Authorization` and `X-XSRF-Token` passthrough rules are what let Sanctum see the bearer token and the CSRF header.
- `favicon.ico` is empty (0 bytes). The browser gets a zero-length icon; the real storefront/admin icons live in the Astro apps (`frontend/public/`, `frontend-admin/public/`).
- `robots.txt` allows everything. This is the API document root, not the storefront — crawler policy for the shop is the Astro app's own file.
- `public/build/` does not exist and is gitignored (`/public/build`, `/public/hot`, `/public/storage`, `/public/fonts-manifest.dev.json`). Do not commit build output; `npm run build` recreates it.
- Never put secrets, `.env`, or storage contents here — the whole directory is served over HTTP.

### Testing Requirements
- No test asserts on files in this directory. `tests/Feature/ExampleTest.php` exercises the front controller indirectly with `GET /` → 200.
- Smoke check the app through this root: `make dev` then `GET /up` (the health route registered in `bootstrap/app.php`) → 200.

## Dependencies

### Internal
- `bootstrap/app.php` — booted by `index.php`.
- `storage/framework/maintenance.php` — optional maintenance-mode file loaded by `index.php`.
- `vendor/autoload.php` — Composer autoloader loaded by `index.php`.

### External
- PHP 8.3+ with the extensions in `composer.json`; Apache `mod_rewrite` for `.htaccess` (optional).

<!-- MANUAL: -->
