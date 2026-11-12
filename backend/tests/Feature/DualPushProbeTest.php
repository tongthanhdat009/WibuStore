<?php

namespace Tests\Feature;

use App\Domain\Payments\PaymentService;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ProductVariant;
use Database\Seeders\DemoSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

// TEMP probe vong 5: chung minh checkout (job) + confirm (legacy) cung ban.
class DualPushProbeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(DemoSeeder::class);
        config([
            'odoo.url' => 'http://odoo-direct.test',
            'odoo.channel_key' => 'test-key',
            'odoo.channel_code' => 'wibu',
            'services.odoo.connector_url' => 'http://odoo-legacy.test',
            'services.odoo.webhook_secret' => 'test-secret',
        ]);
        Http::fake([
            'odoo-direct.test/*' => Http::response(['success' => true], 200),
            'odoo-legacy.test/*' => Http::response(['ok' => true], 200),
        ]);
    }

    public function test_checkout_job_and_paid_hook_both_fire(): void
    {
        $v = ProductVariant::where('sku', 'GSC-MEGUMI-18-STD')->firstOrFail();
        $res = $this->postJson('/api/v1/orders', [
            'items' => [['variant_id' => $v->id, 'quantity' => 1]],
            'customer' => ['name' => 'T', 'email' => 't@t.vn', 'phone' => '0901'],
            'payment_method' => 'cod',
            'addresses' => [['type' => 'shipping', 'recipient_name' => 'T', 'phone' => '0901', 'line1' => '1 LL']],
        ], ['Idempotency-Key' => 'k-dual-1']);
        $res->assertCreated();
        $orderNumber = $res->json('data.order_number');

        // 1. job (sync queue trong test) da ban draft sang :8069.
        Http::assertSent(fn ($r) => str_contains($r->url(), 'odoo-direct.test/api/channel/order_push')
            && str_contains($r->body(), $orderNumber));

        // 2. confirm payment -> legacy hook sang :8090.
        $order = Order::where('order_number', $orderNumber)->firstOrFail();
        app(PaymentService::class)->confirm(Payment::where('order_id', $order->id)->firstOrFail()->id, null);

        Http::assertSent(fn ($r) => str_contains($r->url(), 'odoo-legacy.test/ingest/wibu')
            && str_contains($r->body(), $orderNumber));

        $this->assertSame(2, count(Http::recorded()));
    }
}
