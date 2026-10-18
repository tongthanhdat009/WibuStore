<?php

namespace Tests\Feature;

use App\Models\InventoryLevel;
use App\Models\PreorderCampaign;
use App\Models\PreorderReservation;
use App\Models\ProductVariant;
use Database\Seeders\DemoSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PreorderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(DemoSeeder::class);
    }

    public function test_quota_enforced_and_no_physical_stock_touched(): void
    {
        $campaign = PreorderCampaign::where('status', 'open')->firstOrFail();
        $campaign->update(['quota' => 2, 'reserved_quantity' => 0]);
        $v = ProductVariant::find($campaign->variant_id);

        $make = fn (string $key, string $email) => $this->postJson('/api/v1/orders', [
            'items' => [['variant_id' => $v->id, 'quantity' => 2, 'preorder_campaign_id' => $campaign->id]],
            'customer' => ['name' => 'PO', 'email' => $email, 'phone' => '0901'],
            'payment_method' => 'bank_transfer', 'checkout_type' => 'preorder',
            'addresses' => [['type' => 'shipping', 'recipient_name' => 'PO', 'phone' => '0901', 'line1' => '1 Le Loi']],
        ], ['Idempotency-Key' => $key]);

        $first = $make('k-pre-1', 'a@a.vn')->assertCreated()->json('data');
        $this->assertSame('preorder', $first['checkout_type']);
        $this->assertSame('deposit', $first['payments'][0]['kind']);

        // Quota left is 0 -> second full reservation fails with 409.
        $make('k-pre-2', 'b@b.vn')->assertStatus(409)->assertJsonPath('error.code', 'INSUFFICIENT_STOCK');

        // Physical stock untouched by pre-orders.
        $this->assertSame(0, InventoryLevel::where('variant_id', $v->id)->first()->reserved);
        $this->assertSame(1, PreorderReservation::where('campaign_id', $campaign->id)->count());
    }
}
