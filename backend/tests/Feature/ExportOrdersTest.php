<?php

namespace Tests\Feature;

use App\Domain\Orders\CheckoutService;
use App\Domain\Payments\PaymentService;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ProductVariant;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ExportOrdersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoSeeder::class);
        config([
            'services.odoo.source_token' => 'test-source-token',
            'services.odoo.connector_url' => 'http://odoo.test',
            'services.odoo.webhook_secret' => 'test-secret',
        ]);
        Http::fake(['odoo.test/*' => fn () => Http::response(['ok' => true], 200)]);
    }

    private function placePaidOrder(string $email): Order
    {
        $variantId = ProductVariant::where('sku', 'GSC-MEGUMI-18-STD')->value('id');
        $order = app(CheckoutService::class)->checkout([
            'items' => [['variant_id' => $variantId, 'quantity' => 1]],
            'customer' => ['name' => 'Tester', 'email' => $email, 'phone' => '0901'],
            'payment_method' => 'cod',
            'addresses' => [['type' => 'shipping', 'recipient_name' => 'Tester', 'phone' => '0901', 'line1' => '1 Le Loi']],
        ], null, 'idem-'.uniqid());
        $payment = Payment::where('order_id', $order->id)->firstOrFail();
        app(PaymentService::class)->confirm($payment->id, null);

        return $order->fresh();
    }

    private function authed()
    {
        return $this->withHeaders(['X-Source-Token' => 'test-source-token']);
    }

    public function test_export_rejects_missing_source_token(): void
    {
        $this->getJson('/api/v1/internal/export/orders')->assertUnauthorized();
    }

    public function test_export_paginates_with_composite_cursor_no_dup_no_miss(): void
    {
        $orders = [
            $this->placePaidOrder('a@t.vn'),
            $this->placePaidOrder('b@t.vn'),
            $this->placePaidOrder('c@t.vn'),
        ];
        // Cung timestamp: cursor iso thuan se lap/thieu o day.
        DB::table('orders')->whereIn('id', array_map(fn ($o) => $o->id, $orders))
            ->update(['updated_at' => now()->startOfSecond()]);

        $page1 = $this->authed()->getJson('/api/v1/internal/export/orders?limit=2')
            ->assertOk()->json();
        $this->assertCount(2, $page1['data']);
        $this->assertStringContainsString('|', $page1['cursor']);

        $page2 = $this->authed()->getJson('/api/v1/internal/export/orders?limit=2&since='.urlencode($page1['cursor']))
            ->assertOk()->json();
        $this->assertCount(1, $page2['data']);

        $got = array_merge(
            array_column($page1['data'], 'source_order_id'),
            array_column($page2['data'], 'source_order_id'),
        );
        sort($got);
        $expected = array_map(fn ($o) => $o->order_number, $orders);
        sort($expected);
        $this->assertEquals($expected, $got);

        $page3 = $this->authed()->getJson('/api/v1/internal/export/orders?limit=2&since='.urlencode($page2['cursor']))
            ->assertOk()->json();
        $this->assertCount(0, $page3['data']);
    }

    public function test_export_accepts_legacy_iso_cursor(): void
    {
        $order = $this->placePaidOrder('d@t.vn');
        $iso = $order->updated_at->copy()->subSecond()->format(DATE_ATOM);

        $res = $this->authed()->getJson('/api/v1/internal/export/orders?since='.urlencode($iso))
            ->assertOk();
        $this->assertContains($order->order_number, array_column($res->json('data'), 'source_order_id'));
    }

    public function test_export_clamps_limit(): void
    {
        $this->placePaidOrder('e@t.vn');

        $res = $this->authed()->getJson('/api/v1/internal/export/orders?limit=0')->assertOk();
        $this->assertCount(1, $res->json('data'));

        $this->authed()->getJson('/api/v1/internal/export/orders?limit=9999')->assertOk();
    }
}
