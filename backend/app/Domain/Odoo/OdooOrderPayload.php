<?php

namespace App\Domain\Odoo;

use App\Models\Order;

/**
 * Payload don hang theo shape UnifiedOrder cua odoo-connector-service.
 * Dung chung cho webhook (OdooHookService) va export (ExportController)
 * de ca hai duong ingest cung mot shape.
 */
class OdooOrderPayload
{
    public static function build(Order $order): array
    {
        $order->loadMissing(['items', 'payments', 'addresses']);
        $pay = $order->payments->where('status', 'confirmed')->sortByDesc('confirmed_at')->first();

        return [
            'source' => 'wibu',
            'source_order_id' => $order->order_number,
            'customer_name' => $order->customer_name,
            'customer_email' => $order->email,
            'customer_phone' => $order->phone ?? '',
            'address' => (string) ($order->addresses->firstWhere('type', 'shipping')?->line1 ?? ''),
            'lines' => $order->items->map(fn ($i) => [
                'sku' => $i->sku_snapshot,
                'name' => $i->product_name_snapshot,
                'qty' => (int) $i->quantity,
                'unit_price_vnd' => (int) $i->unit_price_amount,
                'is_service' => false,
            ])->values()->all(),
            'discount_vnd' => (int) $order->discount_amount,
            'shipping_vnd' => (int) $order->shipping_amount,
            'paid_vnd' => (int) $order->paid_amount,
            'payment_method' => $pay?->method ?? 'cod',
            'provider_ref' => (string) ($pay?->provider_reference ?? ''),
            'origin_note' => "wibu:orders:{$order->id}",
        ];
    }
}
