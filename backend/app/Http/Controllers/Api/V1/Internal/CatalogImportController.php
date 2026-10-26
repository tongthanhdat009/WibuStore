<?php

namespace App\Http\Controllers\Api\V1\Internal;

use App\Domain\Inventory\InventoryService;
use App\Http\Controllers\Controller;
use App\Models\InventoryLevel;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Nhan master data tu Odoo: gia + ton kho cho san pham khop theo SKU.
 * POST /api/v1/internal/import/catalog
 * Body: {"products":[{"sku":"GSC-001","name":"...","price_vnd":8900000,"stock":12?}]}
 * Header: X-Source-Token (middleware 'source')
 *
 * `stock` la TON KHO TUYET DOI (on_hand). Bo qua neu khong gui.
 */
class CatalogImportController extends Controller
{
    public function __construct(private InventoryService $inventory)
    {
    }

    public function catalog(Request $request): JsonResponse
    {
        $rows = $request->validate([
            'products' => ['required', 'array', 'max:2000'],
            'products.*.sku' => ['required', 'string', 'max:128'],
            'products.*.name' => ['required', 'string', 'max:255'],
            'products.*.price_vnd' => ['required', 'integer', 'min:0'],
            'products.*.stock' => ['nullable', 'integer', 'min:0'],
        ]);

        $batch = now()->format('YmdHis');
        $created = 0;
        $priceUpdated = 0;
        $stocked = 0;
        $errors = [];

        foreach ($rows['products'] as $row) {
            try {
                $variant = ProductVariant::with('product')->where('sku', $row['sku'])->first();
                if (! $variant) {
                    $product = Product::create([
                        'name' => $row['name'],
                        'status' => 'active',
                    ]);
                    $variant = ProductVariant::create([
                        'product_id' => $product->id,
                        'sku' => $row['sku'],
                        'label' => $row['name'],
                        'price_amount' => (int) $row['price_vnd'],
                    ]);
                    $created++;
                } elseif ((int) $variant->price_amount !== (int) $row['price_vnd']) {
                    $variant->update(['price_amount' => (int) $row['price_vnd']]);
                    $priceUpdated++;
                }

                if (array_key_exists('stock', $row) && $row['stock'] !== null) {
                    $this->setStock($variant, (int) $row['stock'], $batch);
                    $stocked++;
                }
            } catch (\Throwable $e) {
                $errors[] = ['sku' => $row['sku'], 'error' => $e->getMessage()];
            }
        }

        return response()->json(['data' => [
            'created' => $created,
            'price_updated' => $priceUpdated,
            'stocked' => $stocked,
            'errors' => $errors,
        ]]);
    }

    /** Set on_hand tuyet doi; khong giam xuong duoi so da giu (reserved). */
    private function setStock(ProductVariant $variant, int $qty, string $batch): void
    {
        $warehouseId = $this->inventory->defaultWarehouseId();
        $level = InventoryLevel::firstOrCreate(
            ['warehouse_id' => $warehouseId, 'variant_id' => $variant->id],
            ['on_hand' => 0, 'reserved' => 0]
        );
        $current = (int) $level->on_hand;
        $reserved = (int) $level->reserved;
        $target = max($qty, $reserved);
        $delta = $target - $current;
        if ($delta !== 0) {
            $this->inventory->adjust(
                $warehouseId,
                $variant->id,
                $delta,
                'odoo sync',
                'odoo:' . $variant->sku,
                'odoo-stock:' . $batch . ':' . $variant->sku,
                null
            );
        }
    }
}
