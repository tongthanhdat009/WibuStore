<?php

namespace App\Domain\Odoo;

use App\Models\OdooSyncLog;
use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OdooHookService
{
    public static function firePaid(Order $order): void
    {
        $started = (int) (microtime(true) * 1000);
        try {
            $payload = [
                'event_id' => $order->order_number,
                'order' => OdooOrderPayload::build($order),
            ];
            $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
            $sig = hash_hmac('sha256', $json, config('services.odoo.webhook_secret'));
            $endpoint = rtrim(config('services.odoo.connector_url'), '/').'/ingest/wibu';
            $res = Http::withBody($json, 'application/json')
                ->withHeaders(['X-Signature' => $sig])
                ->timeout(5)
                ->post($endpoint);

            // Laravel's HTTP client does not throw on 4xx/5xx, so a rejected
            // payload would otherwise vanish without a trace.
            if (! $res->successful()) {
                Log::warning('OdooHook rejected: HTTP '.$res->status(), [
                    'order' => $order->id ?? null,
                    'body' => mb_substr((string) $res->body(), 0, 500),
                ]);
                OdooSyncLog::record([
                    'direction' => 'push',
                    'event_id' => $order->order_number,
                    'order_id' => $order->id,
                    'endpoint' => $endpoint,
                    'status' => 'rejected',
                    'http_status' => $res->status(),
                    'payload' => $payload,
                    'response_body' => mb_substr((string) $res->body(), 0, 2000),
                    'error' => 'OdooHook rejected: HTTP '.$res->status(),
                    'duration_ms' => (int) (microtime(true) * 1000) - $started,
                ]);
            } else {
                OdooSyncLog::record([
                    'direction' => 'push',
                    'event_id' => $order->order_number,
                    'order_id' => $order->id,
                    'endpoint' => $endpoint,
                    'status' => 'success',
                    'http_status' => $res->status(),
                    'payload' => $payload,
                    'duration_ms' => (int) (microtime(true) * 1000) - $started,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('OdooHook failed: '.$e->getMessage(), ['order' => $order->id ?? null]);
            try {
                OdooSyncLog::record([
                    'direction' => 'push',
                    'event_id' => $order->order_number,
                    'order_id' => $order->id,
                    'endpoint' => rtrim((string) config('services.odoo.connector_url'), '/').'/ingest/wibu',
                    'status' => 'failed',
                    'payload' => $payload ?? [],
                    'error' => mb_substr($e->getMessage(), 0, 2000),
                    'duration_ms' => (int) (microtime(true) * 1000) - $started,
                ]);
            } catch (\Throwable $ignored) {
                // Never break checkout because the sync log is unavailable.
            }
        }
    }
}
