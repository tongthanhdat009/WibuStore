<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-10-22 | Updated: 2026-10-22 -->

# Unit

## Purpose
Pure unit tests — no HTTP, no database. 1 file.

## Key Files
| File | Description |
|------|-------------|
| `ExampleTest.php` | Laravel scaffolding: extends `PHPUnit\Framework\TestCase` (not the Laravel base case) and asserts `true` is `true` |

## For AI Agents

### Working In This Directory
- `Unit/ExampleTest.php` extends `PHPUnit\Framework\TestCase` directly — no container, no `RefreshDatabase`, no fixtures.
- Put anything that needs Eloquent, the cache or the queue in `tests/Feature/` instead; `Unit/` must stay bootless.
- The suite currently holds no real unit test. A domain rule that can be tested without the framework (money rounding, state-machine transitions, slug helpers) is the right candidate here.

### Testing Requirements
- `make test` from the repo root runs both suites (`Unit` then `Feature`, per `phpunit.xml`).
- A Unit test must not touch the database — if it needs one, it is a Feature test.

### Common Patterns
- `phpunit.xml` maps `<testsuite name="Unit">` to `tests/Unit`; files must end in `Test.php` and the class must live in namespace `Tests\Unit`.

## Dependencies

### Internal
- `backend/tests/TestCase.php` — the Laravel base case (used by Feature, not by this suite).

### External
- `phpunit/phpunit:12`.

<!-- MANUAL: -->
