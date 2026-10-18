<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-10-18 | Updated: 2026-10-18 -->

# tests

## Purpose
PHPUnit suites, run via root `make test` against `wibu_store_test`.

## Key Files
| File | Description |
|------|-------------|
| `TestCase.php` | Base case |
| `Feature/CatalogTest.php` | Public catalog endpoints |
| `Feature/CheckoutTest.php` | Cart→order flow, idempotency |
| `Feature/PaymentTest.php` | Confirm/reject, webhook |
| `Feature/PreorderTest.php` | Campaign reserve flow |
| `Feature/RbacTest.php` | `perm:*` gates |
| `Feature/ExampleTest.php` + `Unit/ExampleTest.php` | Scaffolding |

## For AI Agents
- New endpoint → Feature test covering happy + 401/403 paths. Domain rule → assert via service, not HTTP.

<!-- MANUAL: -->
