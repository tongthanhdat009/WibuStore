<?php

namespace Tests\Feature;

use App\Domain\Orders\CheckoutService;
use App\Jobs\PushOrderToOdoo;
use App\Models\Order;
use App\Models\ProductVariant;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PushOrderToOdooTest extends TestCase
{
    use RefreshDatabase;

    protected int $odooStatus = 200;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoSeeder::class);
        config([
            'odoo.url' => 'http://odoo-direct.test',
            'odoo.channel_key' => 'test-key',
            'odoo.channel_code' => 'wibu',
        ]);
        // Mot stub duy nhat (Http::fake them sau bi stub truoc che): doc status qua property.
        Http::fake(['odoo-direct.test/*' => fn () => Http::response(['success' => $this->odooStatus === 200], $this->odooStatus)]);
    }

    private function placeOrder(): Order
    {
        $variantId = ProductVariant::where('sku', 'GSC-MEGUMI-18-STD')->value('id');

        return app(CheckoutService::class)->checkout([
            'items' => [['variant_id' => $variantId, 'quantity' => 2]],
            'customer' => ['name' => 'Tester', 'email' => 't@t.vn', 'phone' => '0901'],
            'payment_method' => 'cod',
            'addresses' => [['type' => 'shipping', 'recipient_name' => 'Tester', 'phone' => '0901', 'line1' => '1 Le Loi']],
        ], null, 'idem-'.uniqid());
    }

    public function test_job_pushes_contract_payload(): void
    {
        $order = $this->placeOrder();

        (new PushOrderToOdoo($order->id))->handle();

        Http::assertSent(function ($req) use ($order) {
            $body = $req->data();

            return $req->url() === 'http://odoo-direct.test/api/channel/order_push'
                && $req->header('X-Channel-Key') === ['test-key']
                && $body['channel_code'] === 'wibu'
                && $body['external_id'] === $order->order_number
                && $body['items'][0]['qty'] === 2
                && $body['customer']['email'] === 't@t.vn'
                && $body['payment_method'] === 'cod';
        });
    }

    public function test_rejected_push_throws_for_retry(): void
    {
        $this->odooStatus = 500;
        $order = $this->placeOrder();

        $this->expectException(\RuntimeException::class);
        (new PushOrderToOdoo($order->id))->handle();
    }
}
