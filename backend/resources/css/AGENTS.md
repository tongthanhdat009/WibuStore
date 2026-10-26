<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-10-22 | Updated: 2026-10-22 -->

# css

## Purpose
Tailwind v4 stylesheet entry compiled by Vite. One file; the only consumer of the compiled CSS is the stock Laravel welcome page.

## Key Files
| File | Description |
|------|-------------|
| `app.css` | Tailwind v4 entry: `@import 'tailwindcss'`, two `@source` globs, `@theme --font-sans` = Instrument Sans |

## For AI Agents

### Working In This Directory
- `@source` paths are relative to this file (`resources/css/`), so `../../vendor/...` = `backend/vendor/...` and `../../storage/framework/views/*.php` = the compiled Blade cache.
- The two globs exist because Tailwind cannot infer those classes on its own: Laravel's pagination Blade views (`Illuminate/Pagination/resources/views/*.blade.php`) and the compiled view cache. Dropping the pagination glob silently strips styling from `{{ $items->links() }}` output; the compiled-views glob only helps after those views have been rendered once.
- Tailwind v4 needs no `tailwind.config.js` — detection is automatic plus these globs. No config file exists in the repo.
- `--font-sans` here only *names* Instrument Sans. The webfont files are fetched by `laravel-vite-plugin/fonts` (`bunny('Instrument Sans', {weights:[400,500,600]})` in `vite.config.js`) and linked by the `@fonts` Blade directive — which has no registered handler in this app (no `laravel/fonts` package, `bootstrap/providers.php` lists only `AppServiceProvider`). Blade emits the literal text `@fonts`; no font file is ever loaded.
- No Blade view uses Tailwind utility classes except the compiled fallback inlined in `welcome.blade.php`. The storefront and admin are separate Astro apps with their own Tailwind build; nothing here styles them.
- `public/build/` is gitignored and absent in this checkout, so `welcome.blade.php` renders its inlined CSS snapshot instead of this file. Editing `app.css` changes nothing on the page until `npm run build` runs — that creates `public/build/manifest.json` and flips the template to the `@vite` branch.
- `node_modules` is not installed here; run `npm install --ignore-scripts` before `npm run build`.

### Testing Requirements
- No test asserts on the compiled CSS. `tests/Feature/ExampleTest.php` only checks `GET /` returns 200.
- `npm run build` from `backend/` must succeed after a change; output lands in `public/build/` (gitignored).
- Visual confirmation needs the built manifest present — otherwise the page keeps serving the inlined snapshot and silently hides your change.

## Dependencies

### Internal
- `vite.config.js` — declares `resources/css/app.css` as a Vite input and pulls the bunny font.
- `resources/views/welcome.blade.php` — the only consumer, via `@vite`.
- `package.json` — `npm run build` / `npm run dev` scripts.

### External
- Tailwind v4 (`@tailwindcss/vite`), Vite, `laravel-vite-plugin` (+ its `/fonts` subpath).

<!-- MANUAL: -->
