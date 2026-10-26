<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-10-18 | Updated: 2026-10-22 -->

# config

## Purpose
12 Laravel config files. Env-driven; only `cors.php` and `services.php` are hand-shaped beyond stock Laravel.

## Key Files
| File | Description |
|------|-------------|
| `app.php` | Name/env/debug/url, locale (`APP_LOCALE=vi`), AES-256-CBC cipher, `APP_KEY`, previous keys, file maintenance driver |
| `auth.php` | Defaults guard `web` + broker `users`; `web` = session driver; provider `users` → `App\Models\User` |
| `cache.php` | Default store `CACHE_STORE` (database); stores: array, database, file, redis |
| `cors.php` | Origins from `CORS_ALLOWED_ORIGINS` (default `localhost:4321,localhost:4322`); paths `api/*` + `sanctum/csrf-cookie`; credentials on |
| `database.php` | Connections: sqlite, mysql, mariadb, **pgsql** (`search_path=public`), sqlsrv; `DB_CONNECTION` selects the default |
| `filesystems.php` | Default disk `FILESYSTEM_DISK`; disks `local`, `public`, `s3` (`AWS_*` env incl. `root`, `endpoint`, `use_path_style_endpoint`, `throw=true`) |
| `logging.php` | Default channel `LOG_CHANNEL` (stack); channels stack/single/daily/monthly/slack/papertrail/stderr/syslog/errorlog/null/emergency |
| `mail.php` | Default mailer `MAIL_MAILER` (log); mailers smtp/ses/postmark/resend/sendmail/log |
| `queue.php` | Default connection `QUEUE_CONNECTION` (database, table `jobs`); also sync/beanstalkd/redis |
| `sanctum.php` | Stateful domains from `SANCTUM_STATEFUL_DOMAINS`; guard `web`; token expiration null; token prefix env |
| `services.php` | Third-party blocks: `postmark`, `resend`, `ses`, `slack`, **`odoo`** |
| `session.php` | Driver `SESSION_DRIVER` (database), lifetime 120, `same_site=lax`, secure via `SESSION_SECURE_COOKIE` |

## For AI Agents

### Working In This Directory
- `services.php` `odoo` block is load-bearing: `connector_url` (env `ODOO_CONNECTOR_URL`, default `http://localhost:8090`) and `webhook_secret` (env `ODOO_WEBHOOK_SECRET`, default `change-me`) are consumed by `app/Domain/Odoo/OdooHookService.php` (`config('services.odoo.*')`). Renaming either key or its env var breaks the Odoo order push — update the service in the same change.
- `ODOO_CONNECTOR_URL` / `ODOO_WEBHOOK_SECRET` are **not** present in `backend/.env` or `.env.example`, so the config defaults apply locally.
- Postgres is the only supported DB. Never point `DB_CONNECTION` at sqlite for real data — `database/database.sqlite` is an unused leftover.
- Redis/Minio changes must stay in sync with `infra/compose.yaml` (optional services only; Postgres stays external).
- Env-driven: never hardcode URLs, keys or credentials in a config file — use `env()`.
- Cache and queue default to the `database` driver, so the `cache`/`cache_locks`/`jobs` tables must exist.

### Testing Requirements
- `phpunit.xml` overrides `CACHE_STORE=array`, `SESSION_DRIVER=array`, `QUEUE_CONNECTION=sync`, `MAIL_MAILER=array`, `DB_CONNECTION=pgsql`, `DB_DATABASE=wibu_store_test` — config files must keep those env keys readable.
- Run `make test` from the repo root after any config change.

### Common Patterns
- Read config through `config('file.key')` in code; never `env()` outside config files.

## Dependencies

### Internal
- `bootstrap/app.php` reads the middleware config; `App\Providers\AppServiceProvider` may bind services from these values.
- `config/services.php` → `app/Domain/Odoo/OdooHookService.php`.

### External
- `laravel/framework:13`, `laravel/sanctum:4`, `league/flysystem-aws-s3-v3` config schemas.

<!-- MANUAL: -->
