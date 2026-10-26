<?php

namespace App\Domain\Odoo;

use App\Domain\Inventory\InventoryService;
use App\Models\InventoryLevel;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OdooStockPull
{
    public function __construct(private InventoryService $inventory) {}

    /** @return array{checked: int, updated: int} */
    public function pull(int $limit = 200): array
    {
        $checked = 0;
        $updated = 0;
        $variants = ProductVariant::take($limit)->get();
        foreach ($variants as $variant) {
            $checked++;
            try {
                $qty = $this->fetchQty($variant->sku);
            } catch (\Throwable $e) {
                Log::warning('OdooStockPull failed: '.$e->getMessage(), ['sku' => $variant->sku]);

                continue;
            }
            if ($qty === null) {
                continue;
            }
            if ($this->setStock($variant, $qty)) {
                $updated++;
            }
        }

        return ['checked' => $checked, 'updated' => $updated];
    }

    private function fetchQty(string $sku): ?int
    {
        $res = Http::withHeaders(['X-Channel-Key' => (string) config('odoo.channel_key')])
            ->timeout((int) config('odoo.timeout', 10))
            ->get(
                rtrim((string) config('odoo.url'), '/').'/api/channel/stock/'.urlencode($sku),
                ['channel_code' => config('odoo.channel_code')]
            );
        if (! $res->successful()) {
            return null;
        }
        $qty = $res->json('data.qty');

        return is_numeric($qty) ? (int) $qty : null;
    }

    /** Set on_hand tuyet doi; khong giam xuong duoi so da giu (reserved). */
    private function setStock(ProductVariant $variant, int $qty): bool
    {
        $warehouseId = $this->inventory->defaultWarehouseId();
        $level = InventoryLevel::firstOrCreate(
            ['warehouse_id' => $warehouseId, 'variant_id' => $variant->id],
            ['on_hand' => 0, 'reserved' => 0]
        );
        $target = max($qty, (int) $level->reserved);
        $delta = $target - (int) $level->on_hand;
        if ($delta === 0) {
            return false;
        }
        $this->inventory->adjust(
            $warehouseId,
            $variant->id,
            $delta,
            'odoo pull',
            'odoo:'.$variant->sku,
            'odoo-pull:'.now()->format('YmdHi').':'.$variant->sku,
            null
        );

        return true;
    }
}
