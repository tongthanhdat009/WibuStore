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
