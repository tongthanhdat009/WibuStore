<?php

namespace App\Domain\Orders;

use App\Domain\Inventory\InventoryService;
use App\Domain\Preorders\PreorderService;
use App\Models\ActivityLog;
use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\IdempotencyKey;
use App\Models\InventoryLevel;
use App\Models\Order;
use App\Models\OrderAddress;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckoutService
{
    public function __construct(
        protected InventoryService $inventory,
        protected PreorderService $preorders,
    ) {}

    /**
     * $input: items[{variant_id, quantity, preorder_campaign_id?}], customer[name,email,phone], addresses[shipping,billing],
     * payment_method, coupon_code?, checkout_type, guest_token?, idempotency_key
     */
    public function preview(array $input, ?int $userId): array
    {
        return $this->calculate($input, $userId, false);
    }

    public function checkout(array $input, ?int $userId, string $idempotencyKey): Order
    {
        $existing = IdempotencyKey::where('key', $idempotencyKey)->where('scope', 'checkout')->first();
        if ($existing?->order_id) {
            return Order::findOrFail($existing->order_id);
        }

        return DB::transaction(function () use ($input, $userId, $idempotencyKey) {
            $calc = $this->calculate($input, $userId, true);
            $isPreorder = ($input['checkout_type'] ?? 'regular') === 'preorder';

            $order = Order::create([
                'user_id' => $userId,
                'lookup_token_hash' => hash('sha256', Str::random(48)),
                'checkout_type' => $isPreorder ? 'preorder' : 'regular',
                'customer_name' => $input['customer']['name'],
                'email' => $input['customer']['email'],
                'phone' => $input['customer']['phone'],
                'order_status' => 'pending',
                'payment_status' => 'unpaid',
                'fulfillment_status' => 'unfulfilled',
                'subtotal_amount' => $calc['subtotal'],
                'discount_amount' => $calc['discount'],
                'shipping_amount' => $calc['shipping'],
                'total_amount' => $calc['total'],
                'paid_amount' => 0,
                'currency' => 'VND',
                'placed_at' => now(),
                'expires_at' => ($input['payment_method'] ?? 'cod') === 'bank_transfer' ? now()->addHours(24) : null,
                'customer_note' => $input['customer_note'] ?? null,
            ]);

            foreach ($calc['lines'] as $line) {
                $item = OrderItem::create([
                    'order_id' => $order->id,
                    'variant_id' => $line['variant_id'],
                    'preorder_campaign_id' => $line['preorder_campaign_id'] ?? null,
                    'sku_snapshot' => $line['sku'],
                    'product_name_snapshot' => $line['product_name'],
                    'variant_label_snapshot' => $line['variant_label'],
                    'unit_price_amount' => $line['unit_price'],
                    'quantity' => $line['quantity'],
                    'line_total_amount' => $line['line_total'],
                    'created_at' => now(),
                ]);
                if ($isPreorder) {
                    $this->preorders->holdQuota($line['preorder_campaign_id'], $item, $line['quantity']);
                } else {
                    $this->inventory->reserve(
                        $this->inventory->defaultWarehouseId(), $line['variant_id'], $line['quantity'],
                        'order', (string) $order->id, "reserve:o{$order->id}:v{$line['variant_id']}", $userId
                    );
                }
            }

            if ($calc['coupon']) {
                $coupon = $calc['coupon'];
                $coupon->lockForUpdate()->first();
                $coupon->increment('used_count');
                CouponRedemption::create(['coupon_id' => $coupon->id, 'order_id' => $order->id, 'user_id' => $userId, 'discount_amount' => $calc['discount']]);
            }

            $kind = $isPreorder ? 'deposit' : 'full';
            $amount = $isPreorder ? $calc['deposit_due'] : $calc['total'];
            Payment::create([
                'order_id' => $order->id, 'kind' => $kind,
                'method' => $input['payment_method'] ?? 'cod',
                'amount' => $amount, 'status' => 'pending',
                'idempotency_key' => 'pay:o'.$order->id.':'.$kind,
            ]);

            foreach ($input['addresses'] ?? [] as $addr) {
                OrderAddress::create(['order_id' => $order->id] + $addr);
            }

            OrderStatusHistory::create(['order_id' => $order->id, 'from_status' => null, 'to_status' => 'pending', 'reason' => 'created', 'actor_user_id' => $userId, 'created_at' => now()]);

            IdempotencyKey::create(['key' => $idempotencyKey, 'scope' => 'checkout', 'order_id' => $order->id, 'response' => ['order_id' => $order->id], 'expires_at' => now()->addDay()]);
            ActivityLog::record('order.created', 'order', (string) $order->id, [], ['order_number' => $order->order_number, 'total' => $order->total_amount], $userId);

            return $order->fresh(['items', 'payments', 'addresses']);
        });
    }

    /** @return array{subtotal:int,discount:int,shipping:int,total:int,deposit_due:int,lines:array,coupon:?Coupon} */
    protected function calculate(array $input, ?int $userId, bool $lock): array
    {
        $items = $input['items'] ?? [];
        if (! $items) {
            abort(response()->json(['error' => ['code' => 'VALIDATION_ERROR', 'message' => 'Gio hang trong']], 422));
        }
        $isPreorder = ($input['checkout_type'] ?? 'regular') === 'preorder';
        if ($isPreorder && count($items) > 1) {
            $campaigns = collect($items)->pluck('preorder_campaign_id')->unique();
            if ($campaigns->count() > 1) {
                abort(response()->json(['error' => ['code' => 'VALIDATION_ERROR', 'message' => 'Chi 1 preorder campaign moi checkout (MVP)']], 422));
            }
        }

        $variantIds = collect($items)->pluck('variant_id')->unique()->sort()->values();
        $variants = ProductVariant::with('product')
            ->when($lock, fn ($q) => $q->lockForUpdate())
            ->whereIn('id', $variantIds)->get()->keyBy('id');

        $subtotal = 0;
        $lines = [];
        foreach ($items as $it) {
            $v = $variants->get($it['variant_id']);
            if (! $v || $v->status !== 'active' || ! $v->product || $v->product->status !== 'active') {
                abort(response()->json(['error' => ['code' => 'VALIDATION_ERROR', 'message' => 'SKU khong kha dung']], 422));
            }
            $qty = max(1, (int) ($it['quantity'] ?? 1));
            if ($isPreorder) {
                $unit = (int) $v->product->preorderPriceFor($v) ?? (int) $v->price_amount;
                $campaign = $this->preorders->campaignForCheckout((int) ($it['preorder_campaign_id'] ?? 0), $lock);
                if ($campaign->variant_id !== $v->id || $campaign->available < $qty) {
                    abort(response()->json(['error' => ['code' => 'INSUFFICIENT_STOCK', 'message' => 'Het quota pre-order']], 409));
                }
                $unit = (int) $campaign->sale_price_per_unit_amount;
            } else {
                $unit = (int) $v->price_amount;
                if ($v->track_inventory && $lock) {
                    $wh = $this->inventory->defaultWarehouseId();
                    $lvl = InventoryLevel::where('warehouse_id', $wh)->where('variant_id', $v->id)->lockForUpdate()->first();
                    if (! $lvl || ($lvl->on_hand - $lvl->reserved) < $qty) {
                        abort(response()->json(['error' => ['code' => 'INSUFFICIENT_STOCK', 'message' => "SKU {$v->sku} khong du hang"]], 409));
                    }
                }
            }
            $lineTotal = $unit * $qty;
            $subtotal += $lineTotal;
            $lines[] = [
                'variant_id' => $v->id, 'preorder_campaign_id' => $it['preorder_campaign_id'] ?? null,
                'sku' => $v->sku, 'product_name' => $v->product->name, 'variant_label' => $v->label,
                'unit_price' => $unit, 'quantity' => $qty, 'line_total' => $lineTotal,
            ];
        }

        $coupon = null;
        $discount = 0;
        if (! empty($input['coupon_code'])) {
            $coupon = Coupon::where('code', $input['coupon_code'])->when($lock, fn ($q) => $q->lockForUpdate())->first();
            if ($coupon) {
                if ($isPreorder && ($coupon->rules['allow_preorder'] ?? false) !== true) {
                    $coupon = null;
                } else {
                    $discount = $coupon->discountFor($subtotal);
                    if ($discount <= 0) {
                        $coupon = null;
                    }
                }
            } else {
                $coupon = null;
            }
        }

        $shipping = $subtotal > 0 ? 30000 : 0;
        $total = max(0, $subtotal - $discount + $shipping);
        $deposit_due = 0;
        if ($isPreorder && isset($campaign)) {
            $deposit_due = (int) $campaign->deposit_per_unit_amount * array_sum(array_column($lines, 'quantity'));
        }

        return compact('subtotal', 'discount', 'shipping', 'total', 'deposit_due', 'lines', 'coupon');
    }
}
