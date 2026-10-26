<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-10-22 | Updated: 2026-10-22 -->

# Jobs

## Purpose
Queued background jobs. Currently one: the order confirmation email.

## Key Files
| File | Description |
|------|-------------|
| `SendOrderConfirmation.php` | `ShouldQueue` job that mails a plain-text order thank-you with the order total |

## For AI Agents

### Working In This Directory
- `SendOrderConfirmation` takes an `int $orderId` (not the model) and re-fetches the order in `handle()`, so the queued payload stays small and serialisable.
- `handle()` returns silently when the order no longer exists — it must not throw for a missing row.
- The body is built with `Mail::raw(...)`: a Vietnamese thank-you line with the order number and `total_amount` in VND. There is no Blade mail view.
- The job is dispatched from `CheckoutService::checkout()` via `SendOrderConfirmation::dispatch($order->id)`. The dispatch happens **inside** the order transaction, so the queued row is only written for orders that actually commit — a rolled-back checkout sends no mail.
- The default queue connection is `database` (`config/queue.php`); `make queue` runs the worker. The default mailer is `log` (`config/mail.php`), so mail lands in the log until `MAIL_MAILER` is changed.
- The job declares no `$tries`, `$backoff`, `$timeout` or `failed()` handler — a failure surfaces as a plain queue failure with no notification.
- It uses the `Queueable` trait (`Illuminate\Foundation\Queue\Queueable`).

### Testing Requirements
- `make test` from the repo root (DB `wibu_store_test`); `make pint` is the style gate. Invoke PHP only through the root `make` targets, not `php artisan` directly. The runtime is host PHP (8.3+), not Docker — see `backend/AGENTS.md`.
- No test covers this job today; assert it with `Mail::fake()` if you add one.

### Common Patterns
- Job constructors take scalars/IDs; models are re-fetched in `handle()` rather than serialised into the payload.

## Dependencies

### Internal
- `App\Models\Order`.
- Dispatcher: `App\Domain\Orders\CheckoutService::checkout()`.

### External
- `laravel/framework:13` (`Mail` facade, `ShouldQueue` contract, `Queueable` trait)

<!-- MANUAL: -->
