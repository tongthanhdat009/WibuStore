<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-10-22 | Updated: 2026-10-22 -->

# Providers

## Purpose
Service providers. One file only, registered in `bootstrap/providers.php`.

## Key Files
| File | Description |
|------|-------------|
| `AppServiceProvider.php` | Empty stub — `register()` and `boot()` contain nothing |

## For AI Agents

### Working In This Directory
- `AppServiceProvider` is a no-op stub: both `register()` and `boot()` are empty. It does not configure rate limiting, Sanctum, queues, mail or filesystem disks — do not describe it as if it does.
- The real application wiring is in `bootstrap/app.php`: `statefulApi()` (Sanctum session auth), the `perm` middleware alias, `ForceJson` appended to the `api` group, the CSRF exemption for `api/v1/webhooks/*`, and the exception renderers that produce the `{error:{code,message}}` JSON contract.
- Rate limits are not defined here either — they are declared inline per route in `routes/api.php` (`throttle:10,1`, `throttle:5,1`, `throttle:20,1`).
- `bootstrap/providers.php` returns `AppServiceProvider::class` as the only entry.
- Add provider code here only when something genuinely needs a container binding or a boot hook; a provider with an empty body is the expected state today.

### Testing Requirements
- `make test` from the repo root (DB `wibu_store_test`); `make pint` is the style gate. Invoke PHP only through the root `make` targets, not `php artisan` directly. The runtime is host PHP (8.3+), not Docker — see `backend/AGENTS.md`.
- No provider-specific test exists; a container binding change needs a Feature test that exercises the bound service.

### Common Patterns
- Config values are env-driven; never hardcode credentials or URLs in a provider.

## Dependencies

### Internal
- None — the stub imports only `Illuminate\Support\ServiceProvider`.

### External
- `laravel/framework:13`

<!-- MANUAL: -->
