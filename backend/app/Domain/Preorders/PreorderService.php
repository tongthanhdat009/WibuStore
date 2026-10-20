<?php

namespace App\Domain\Preorders;

use App\Models\OrderItem;
use App\Models\PreorderCampaign;
use App\Models\PreorderReservation;

class PreorderService
{
    public function campaignForCheckout(int $campaignId, bool $lock): PreorderCampaign
    {
        $q = PreorderCampaign::with('variant')->where('id', $campaignId);
        if ($lock) {
            $q->lockForUpdate();
        }
        $c = $q->first();
        if (! $c || $c->status !== 'open' || ($c->opens_at && now()->lt($c->opens_at)) || ($c->closes_at && now()->gt($c->closes_at))) {
            abort(response()->json(['error' => ['code' => 'INVALID_STATE', 'message' => 'Chien dich pre-order khong mo']], 409));
        }

        return $c;
    }

    public function holdQuota(int $campaignId, OrderItem $item, int $qty): PreorderReservation
    {
        $campaign = PreorderCampaign::where('id', $campaignId)->lockForUpdate()->firstOrFail();
        if ($campaign->quota - $campaign->reserved_quantity < $qty) {
            abort(response()->json(['error' => ['code' => 'INSUFFICIENT_STOCK', 'message' => 'Het quota pre-order']], 409));
        }
        $campaign->increment('reserved_quantity', $qty);
        $deposit = (int) $campaign->deposit_per_unit_amount * $qty;
        $balance = (int) $campaign->sale_price_per_unit_amount * $qty - $deposit;

        return PreorderReservation::create([
            'campaign_id' => $campaign->id, 'order_item_id' => $item->id, 'quantity' => $qty,
            'status' => 'awaiting_deposit',
            'deposit_required_amount' => $deposit, 'balance_due_amount' => max(0, $balance),
            'deposit_deadline_at' => now()->addHours(24),
        ]);
    }

    public function markDepositPaid(int $orderId): void
    {
        $reservations = PreorderReservation::whereIn(
            'order_item_id',
            OrderItem::where('order_id', $orderId)->pluck('id')
        )->lockForUpdate()->get();
        foreach ($reservations as $r) {
            if ($r->status === 'awaiting_deposit') {
                $r->update(['status' => 'deposit_paid', 'deposit_paid_at' => now()]);
            }
        }
    }

    public function markBalanceDue(int $reservationId, ?int $actorId, int $dueDays = 14): PreorderReservation
    {
        $r = PreorderReservation::where('id', $reservationId)->lockForUpdate()->firstOrFail();
        if (! in_array($r->status, ['deposit_paid', 'balance_due'], true)) {
            abort(response()->json(['error' => ['code' => 'INVALID_STATE', 'message' => 'Reservation chua coc xong']], 409));
        }
        $orderItem = OrderItem::find($r->order_item_id);
        $order = \App\Models\Order::find($orderItem->order_id);
        $rest = max(0, (int) $order->total_amount - (int) $order->paid_amount);
        \App\Models\Payment::firstOrCreate(
            ['order_id' => $orderItem->order_id, 'kind' => 'balance'],
            [
                'method' => 'bank_transfer', 'amount' => $rest,
                'status' => 'pending', 'idempotency_key' => 'pay:o'.$orderItem->order_id.':balance',
            ]
        );
        $r->update([
            'status' => 'balance_due',
            'balance_deadline_at' => now()->addDays($dueDays),
        ]);
        \App\Models\ActivityLog::record('preorder.balance_due', 'preorder_reservation', (string) $r->id, [], ['balance' => $r->balance_due_amount], $actorId);

        return $r->fresh();
    }

    public function markBalancePaid(int $orderId): void
    {
        $reservations = PreorderReservation::whereIn(
            'order_item_id',
            OrderItem::where('order_id', $orderId)->pluck('id')
        )->lockForUpdate()->get();
        foreach ($reservations as $r) {
            if (in_array($r->status, ['balance_due', 'deposit_paid'], true)) {
                $r->update(['status' => 'completed']);
            }
        }
    }

    public function releaseQuota(PreorderReservation $r): void
    {
        if (in_array($r->status, ['expired', 'cancelled', 'completed'], true)) {
            return;
        }
        $campaign = PreorderCampaign::where('id', $r->campaign_id)->lockForUpdate()->first();
        if ($campaign) {
            $campaign->decrement('reserved_quantity', min($r->quantity, $campaign->reserved_quantity));
        }
        $r->update(['status' => 'expired']);
    }
}
