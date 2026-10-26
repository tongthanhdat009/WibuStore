<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-10-22 | Updated: 2026-10-22 -->

# js

## Purpose
Vite JavaScript entry for the Laravel side. The file is intentionally empty — the backend is a JSON API and the two UIs are separate Astro apps.

## Key Files
| File | Description |
|------|-------------|
| `app.js` | Single line: `//`. Declared as a Vite input in `vite.config.js`, so `npm run build` still emits a JS bundle |

## For AI Agents

### Working In This Directory
- The file is not empty on disk — it holds one `//` line. Deleting it breaks `npm run build`: `vite.config.js` lists `resources/js/app.js` in the `laravel()` plugin `input` array, and a missing input fails the build.
- The bundle it produces is near-empty. `welcome.blade.php` references it via `@vite(['resources/css/app.css', 'resources/js/app.js'])`, but only when `public/build/manifest.json` or `public/build/hot` exists; neither is in the repo (`/public/build` is gitignored), so the `@vite` branch is currently skipped.
- There is no place for app behaviour to live here. The only HTML page this app serves is `welcome.blade.php` (the stock Laravel screen); the storefront (`frontend/`) and admin (`frontend-admin/`) are Astro apps with their own scripts. Adding JS here ships code that nothing executes.
- `@auth` / `Route::has()` branches in the welcome page are server-side Blade; there is no client router to hook into.

### Testing Requirements
- No test asserts on this file. `tests/Feature/ExampleTest.php` only checks `GET /` returns 200.
- `npm run build` from `backend/` must still succeed after touching the file.

## Dependencies

### Internal
- `vite.config.js` — declares `resources/js/app.js` as a Vite input.
- `resources/views/welcome.blade.php` — the only page that would load the bundle.

### External
- Vite, `laravel-vite-plugin`.

<!-- MANUAL: -->
