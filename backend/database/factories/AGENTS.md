<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-10-22 | Updated: 2026-10-22 -->

# factories

## Purpose
Eloquent model factories. One file only — `UserFactory`.

## Key Files
| File | Description |
|------|-------------|
| `UserFactory.php` | `App\Models\User` factory: faked name/unique safe email, `email_verified_at = now()`, cached bcrypt hash of `password`, random `remember_token` |

## For AI Agents

### Working In This Directory
- `UserFactory` is the only factory in the project. `Product::factory()` and friends do **not** exist — build catalog rows explicitly (as `DemoSeeder` does) or seed in the test.
- `unverified()` is the only custom state; it nulls `email_verified_at`.
- The password hash is memoised in `static::$password` so every user from the factory shares one bcrypt hash — cheap for large suites. `BCRYPT_ROUNDS=4` is set by `phpunit.xml` in the test env.
- `fake()->unique()->safeEmail()` is used — a large seed run can exhaust the unique pool; scope it (e.g. `unique()->safeEmail()` per model) if you add many users in one test.
- Factory namespace is `Database\Factories\` (PSR-4 mapped in `composer.json`); the model is `App\Models\User`.

### Testing Requirements
- `RbacTest` is the only current consumer: `User::factory()->create()` then attaching roles from `RolePermissionSeeder`.
- Run `make test` from the repo root after adding states.

### Common Patterns
- Keep factories minimal and deterministic about the fields the app depends on (`status`, `public_id`, etc. are set by the model/database defaults, not the factory).

## Dependencies

### Internal
- `App\Models\User` (casts/fillables must accept the factory attributes).
- `database/seeders/RolePermissionSeeder.php` creates the roles `RbacTest` attaches to factory users.

### External
- `laravel/framework:13` factory builder, `fakerphp/faker`.

<!-- MANUAL: -->
