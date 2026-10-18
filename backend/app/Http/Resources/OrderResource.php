<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->public_id,
            'order_number' => $this->order_number,
            'checkout_type' => $this->checkout_type,
            'order_status' => $this->order_status,
            'payment_status' => $this->payment_status,
            'fulfillment_status' => $this->fulfillment_status,
            'subtotal_amount' => $this->subtotal_amount,
            'discount_amount' => $this->discount_amount,
            'shipping_amount' => $this->shipping_amount,
            'total_amount' => $this->total_amount,
            'paid_amount' => $this->paid_amount,
            'currency' => $this->currency,
            'placed_at' => $this->placed_at?->toIso8601String(),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($i) => [
                'sku' => $i->sku_snapshot, 'product' => $i->product_name_snapshot,
                'variant' => $i->variant_label_snapshot, 'unit_price_amount' => $i->unit_price_amount,
                'quantity' => $i->quantity, 'line_total_amount' => $i->line_total_amount,
            ])),
            'payments' => $this->whenLoaded('payments', fn () => $this->payments->map->only(['kind', 'method', 'amount', 'status'])),
            'addresses' => $this->whenLoaded('addresses'),
        ];
    }
}
