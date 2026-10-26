<!-- Parent: ../AGENTS.md -->
<!-- Generated: 2026-10-22 | Updated: 2026-10-22 -->

# pdf

## Purpose
dompdf HTML templates. One file: the order invoice PDF.

## Key Files
| File | Description |
|------|-------------|
| `invoice.blade.php` | Invoice HTML: header block, item table, totals table. Receives `$order` and `$address` |

## For AI Agents

### Working In This Directory
- Two renderers, one template, identical variables:
  - `CartController::invoice()` → `GET /api/v1/orders/{publicId}/invoice`, keyed on the order **public_id**. Authorised for the order owner or a guest with a valid `X-Order-Token` header (sha256 compared to `orders.lookup_token_hash`); otherwise 403 `{error:{code:"FORBIDDEN"}}`.
  - `AdminOperationsController::invoice()` → `GET /api/v1/admin/orders/{id}/invoice`, gated by `perm:orders.transition`, keyed on the **numeric order id**.
  - Both do `Pdf::setOption(['defaultFont' => 'DejaVu Sans'])->loadView('pdf.invoice', ['order' => $order, 'address' => $address])->download("hoa-don-{$order->order_number}.pdf")`.
- Exactly two variables reach the template. `$order` is an `App\Models\Order` eager-loaded with `items` and `addresses`; `$address` is a pre-formatted string — `line1, district, province` joined with `, ` then `trim(..., ', ')`, or `—` when there is no shipping address. The address `ward` and phone are not printed.
- dompdf constraints: inline `<style>` in `<head>` only, no external CSS, no web fonts, no flexbox/grid. `DejaVu Sans` is set by the controller and is what makes the Vietnamese diacritics render — swapping the font without Unicode coverage produces blank glyphs.
- Money is integer VND in, `number_format($value, 0, ',', '.')` + ` ₫` out: `.` as thousands separator, `,` as decimal marker, zero decimals. Never divide by 100 here.
- Line data comes from the `OrderItem` snapshot columns (`product_name_snapshot`, `variant_label_snapshot`, `sku_snapshot`, `unit_price_amount`, `quantity`, `line_total_amount`), not from live product rows. A renamed column renders an empty cell, not an error — Blade prints `null` as empty string.
- `discount_amount` is printed with a hardcoded `-` prefix, so a zero discount shows `-0 ₫`.
- `placed_at` is nullable: `optional($order->placed_at)->format('d/m/Y H:i') ?? $order->created_at->format('d/m/Y H:i')` falls back to `created_at`.
- `order_status` and `payment_status` print the raw DB enum values (`pending`, `paid`, …), untranslated.
- There is no `config/dompdf.php` in the repo — dompdf runs on package defaults plus the `defaultFont` option set in the controllers.

### Testing Requirements
- No automated test asserts the PDF body. `make test` from the repo root covers the surrounding API.
- Manual check: `GET /api/v1/orders/{publicId}/invoice` as the owner (session) or with `X-Order-Token` → 200 and an attachment named `hoa-don-<order_number>.pdf`.
- Rendering changes need a visual check — dompdf failures (unsupported CSS, missing font) surface as a broken PDF, not a test failure.

## Dependencies

### Internal
- `app/Http/Controllers/Api/V1/CartController.php` — customer invoice endpoint.
- `app/Http/Controllers/Api/V1/Admin/AdminOperationsController.php` — admin invoice endpoint.
- `app/Models/Order.php`, `app/Models/OrderItem.php`, `app/Models/OrderAddress.php` — the fields the template reads.

### External
- `barryvdh/laravel-dompdf` (bundled DejaVu Sans).

<!-- MANUAL: -->
