<?php

namespace App\Domain\Orders;

use App\Domain\Common\Statuses;
use App\Domain\Inventory\InventoryService;
use App\Domain\Preorders\PreorderService;
use App\Models\ActivityLog;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\PreorderReservation;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(
        protected InventoryService $inventory,
        protected PreorderService $preorders,
    ) {}

    public function transition(Order $order, string $to, ?int $actorId, ?string $reason = null): Order
    {
        return DB::transaction(function () use ($order, $to, $actorId, $reason) {
            $order = Order::where('id', $order->id)->lockForUpdate()->firstOrFail();
            $from = $order->order_status;
            if ($from === $to) {
                return $order;
            }
            if (! Statuses::canTransition($from, $to)) {
                abort(response()->json(['error' => ['code' => 'INVALID_STATE', 'message' => "Khong the chuyen {$from} -> {$to}"]], 409));
            }
            if ($to === 'shipped') {
                $wh = $this->inventory->defaultWarehouseId();
                foreach ($order->items as $item) {
                    if ($order->checkout_type === 'regular' && $item->variant_id) {
                        $this->inventory->ship($wh, $item->variant_id, $item->quantity, (string) $order->id, "ship:o{$order->id}:v{$item->variant_id}", $actorId);
                    }
                }
                $order->fulfillment_status = 'shipped';
            }
            if ($to === 'delivered') {
                $order->fulfillment_status = 'delivered';
            }
            if ($to === 'completed') {
                $order->fulfillment_status = 'delivered';
            }
            $order->order_status = $to;
            $order->save();
            OrderStatusHistory::create(['order_id' => $order->id, 'from_status' => $from, 'to_status' => $to, 'reason' => $reason, 'actor_user_id' => $actorId, 'created_at' => now()]);
            ActivityLog::record('order.transition', 'order', (string) $order->id, ['from' => $from], ['to' => $to], $actorId);

            return $order->fresh();
        });
    }

    public function cancel(Order $order, ?int $actorId): Order
    {
        return DB::transaction(function () use ($order, $actorId) {
            $order = Order::where('id', $order->id)->lockForUpdate()->firstOrFail();
            if (in_array($order->order_status, ['cancelled', 'expired', 'completed'], true)) {
                return $order;
            }
            if (! in_array($order->order_status, ['pending', 'confirmed', 'processing'], true)) {
                abort(response()->json(['error' => ['code' => 'INVALID_STATE', 'message' => 'Don khong the huy o trang thai hien tai']], 409));
            }
            $from = $order->order_status;
            if ($order->checkout_type === 'preorder') {
                foreach (PreorderReservation::whereIn('order_item_id', $order->items()->pluck('id'))->lockForUpdate()->get() as $r) {
                    $this->preorders->releaseQuota($r);
                    $r->update(['status' => 'cancelled']);
                }
            } else {
                $wh = $this->inventory->defaultWarehouseId();
                foreach ($order->items as $item) {
                    if ($item->variant_id) {
                        $this->inventory->release($wh, $item->variant_id, $item->quantity, 'order', (string) $order->id, "release:o{$order->id}:v{$item->variant_id}", $actorId);
                    }
                }
            }
            $order->update(['order_status' => 'cancelled']);
            OrderStatusHistory::create(['order_id' => $order->id, 'from_status' => $from, 'to_status' => 'cancelled', 'reason' => 'cancelled', 'actor_user_id' => $actorId, 'created_at' => now()]);

            return $order->fresh();
        });
    }

    public function expireUnpaid(int $limit = 100): int
    {
        $count = 0;
        foreach (Order::where('order_status', 'pending')->whereNotNull('expires_at')->where('expires_at', '<', now())->limit($limit)->get() as $order) {
            DB::transaction(function () use ($order, &$count) {
                $o = Order::where('id', $order->id)->lockForUpdate()->first();
                if (! $o || $o->order_status !== 'pending' || $o->paid_amount > 0) {
                    return;
                }
                if ($o->checkout_type === 'preorder') {
                    foreach (PreorderReservation::whereIn('order_item_id', $o->items()->pluck('id'))->lockForUpdate()->get() as $r) {
                        $this->preorders->releaseQuota($r);
                    }
                } else {
                    $wh = $this->inventory->defaultWarehouseId();
                    foreach (OrderItem::where('order_id', $o->id)->get() as $item) {
                        if ($item->variant_id) {
                            $this->inventory->release($wh, $item->variant_id, $item->quantity, 'order', (string) $o->id, "expire:o{$o->id}:v{$item->variant_id}");
                        }
                    }
                }
                $o->update(['order_status' => 'expired']);
                OrderStatusHistory::create(['order_id' => $o->id, 'from_status' => 'pending', 'to_status' => 'expired', 'reason' => 'reservation expired', 'created_at' => now()]);
                $count++;
            });
        }

        return $count;
    }
}
