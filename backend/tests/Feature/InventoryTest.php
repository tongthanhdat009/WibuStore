<?php

namespace Tests\Feature;

use App\Domain\Inventory\InventoryService;
use App\Models\InventoryLevel;
use App\Models\ProductVariant;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoSeeder::class);
    }

    /**
     * The `(int)` cast binds tighter than `??`, so the old one-expression form
     * returned 0 instead of creating the missing warehouse.
     */
    public function test_default_warehouse_is_created_when_missing(): void
    {
        // Rename rather than delete: inventory_levels restricts on warehouse_id.
        DB::table('warehouses')->where('code', 'default')->update(['code' => 'default-old']);

        $id = (new InventoryService)->defaultWarehouseId();

        $this->assertGreaterThan(0, $id);
        $this->assertDatabaseHas('warehouses', ['id' => $id, 'code' => 'default']);
    }

    public function test_default_warehouse_is_reused_when_present(): void
    {
        $existing = DB::table('warehouses')->where('code', 'default')->value('id');

        $this->assertSame((int) $existing, (new InventoryService)->defaultWarehouseId());
        $this->assertSame(1, DB::table('warehouses')->where('code', 'default')->count());
    }

    /** ship() must answer with the repo-wide {error:{code,message}} contract, not a bare abort(). */
    public function test_ship_without_inventory_level_returns_error_contract(): void
    {
        $variant = ProductVariant::where('sku', 'GSC-GOJO-17-STD')->firstOrFail();
        InventoryLevel::where('variant_id', $variant->id)->delete();
        $warehouseId = (int) DB::table('warehouses')->where('code', 'default')->value('id');

        try {
            (new InventoryService)->ship($warehouseId, $variant->id, 1, 'order-1', 'idem-ship-1');
            $this->fail('ship() should have aborted');
        } catch (HttpResponseException $e) {
            $this->assertSame(409, $e->getResponse()->getStatusCode());
            $this->assertSame('INVALID_STATE', $e->getResponse()->getData(true)['error']['code']);
        }
    }
}
