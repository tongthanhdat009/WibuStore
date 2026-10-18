<?php

namespace Tests\Feature;

use App\Models\InventoryLevel;
use App\Models\ProductVariant;
use Database\Seeders\DemoSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(DemoSeeder::class);
    }

    protected function payload(int $variantId, int $qty = 1, array $extra = []): array
    {
        return array_merge([
            'items' => [['variant_id' => $variantId, 'quantity' => $qty]],
            'customer' => ['name' => 'Tester', 'email' => 't@t.vn', 'phone' => '0901'],
            'payment_method' => 'cod',
            'addresses' => [['type' => 'shipping', 'recipient_name' => 'Tester', 'phone' => '0901', 'line1' => '1 Le Loi']],
        ], $extra);
    }

    public function test_checkout_reserves_stock_and_snapshots(): void
    {
        $v = ProductVariant::where('sku', 'GSC-MEGUMI-18-STD')->firstOrFail();
        $res = $this->postJson('/api/v1/orders', $this->payload($v->id), ['Idempotency-Key' => 'k-checkout-1']);
        $res->assertCreated()->assertJsonPath('data.order_status', 'pending');

        $lvl = InventoryLevel::where('variant_id', $v->id)->first();
        $this->assertSame(1, $lvl->reserved);
        $this->assertSame('GSC-MEGUMI-18-STD', $res->json('data.items.0.sku'));

        // Idempotent retry returns the same order, no extra reservation.
        $retry = $this->postJson('/api/v1/orders', $this->payload($v->id), ['Idempotency-Key' => 'k-checkout-1']);
        $retry->assertCreated()->assertJsonPath('data.order_number', $res->json('data.order_number'));
        $this->assertSame(1, InventoryLevel::where('variant_id', $v->id)->first()->reserved);
    }

    public function test_oversell_rejected_and_cancel_releases_once(): void
    {
        $v = ProductVariant::where('sku', 'GSC-MEGUMI-18-STD')->firstOrFail();
        // Stock is 20; ask for more than available.
        $this->postJson('/api/v1/orders', $this->payload($v->id, 21), ['Idempotency-Key' => 'k-over-1'])
            ->assertStatus(409)->assertJsonPath('error.code', 'INSUFFICIENT_STOCK');

        $order = $this->postJson('/api/v1/orders', $this->payload($v->id, 2), ['Idempotency-Key' => 'k-over-2'])
            ->assertCreated()->json('data');

        $this->postJson("/api/v1/orders/{$order['id']}/cancel")->assertOk();
        $this->postJson("/api/v1/orders/{$order['id']}/cancel")->assertOk();
        $this->assertSame(0, InventoryLevel::where('variant_id', $v->id)->first()->reserved);
    }
}
