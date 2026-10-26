<?php

namespace App\Jobs;

use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PushOrderToOdoo implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @return array<int> */
    public function backoff(): array
    {
        return [30, 120, 300];
    }

    public function __construct(public int $orderId) {}

    public function handle(): void
    {
        $order = Order::with(['items', 'payments'])->find($this->orderId);
        if (! $order) {
            return;
        }
        $payment = $order->payments->sortByDesc('confirmed_at')->first();
        $payload = [
            'channel_code' => config('odoo.channel_code'),
            'external_id' => $order->order_number,
            'items' => $order->items->map(fn ($it) => [
                'sku' => $it->sku_snapshot,
                'qty' => (int) $it->quantity,
                'price_vnd' => (int) $it->unit_price_amount,
            ])->values()->all(),
            'customer' => [
                'name' => $order->customer_name,
                'phone' => $order->phone,
                'email' => $order->email,
            ],
            'payment_method' => $payment?->method ?? 'cod',
        ];
        try {
            $res = Http::withHeaders(['X-Channel-Key' => (string) config('odoo.channel_key')])
                ->timeout((int) config('odoo.timeout', 10))
                ->post(rtrim((string) config('odoo.url'), '/').'/api/channel/order_push', $payload);
        } catch (\Throwable $e) {
            // Loi mang/timeout: log + nem de queue retry (failed() log cuoi).
            Log::warning('OdooPush failed: '.$e->getMessage(), ['order' => $order->id]);
            throw new \RuntimeException('Odoo push failed: '.$e->getMessage(), 0, $e);
        }

        // Nem de queue retry; failed() se log cuoi (fail-open: checkout khong bi block).
        if (! $res->successful()) {
            Log::warning('OdooPush rejected: HTTP '.$res->status(), ['order' => $order->id]);
            throw new \RuntimeException('Odoo push rejected: '.$res->status());
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::warning('OdooPush failed: '.$e->getMessage(), ['order' => $this->orderId]);
    }
}
