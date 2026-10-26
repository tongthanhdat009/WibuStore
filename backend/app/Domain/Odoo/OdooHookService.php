<?php

namespace App\Domain\Odoo;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OdooHookService
{
    public static function firePaid(Order $order): void
    {
        try {
            $payload = [
                'event_id' => $order->order_number,
                'order' => OdooOrderPayload::build($order),
            ];
            $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
            $sig = hash_hmac('sha256', $json, config('services.odoo.webhook_secret'));
            $res = Http::withBody($json, 'application/json')
                ->withHeaders(['X-Signature' => $sig])
                ->timeout(5)
                ->post(rtrim(config('services.odoo.connector_url'), '/').'/ingest/wibu');

            // Laravel's HTTP client does not throw on 4xx/5xx, so a rejected
            // payload would otherwise vanish without a trace.
            if (! $res->successful()) {
                Log::warning('OdooHook rejected: HTTP '.$res->status(), ['order' => $order->id ?? null]);
            }
        } catch (\Throwable $e) {
            Log::warning('OdooHook failed: '.$e->getMessage(), ['order' => $order->id ?? null]);
        }
    }
}
