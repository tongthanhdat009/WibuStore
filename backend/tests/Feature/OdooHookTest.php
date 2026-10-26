<?php

namespace Tests\Feature;

use App\Domain\Orders\CheckoutService;
use App\Domain\Payments\PaymentService;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Models\ProductVariant;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class OdooHookTest extends TestCase
{
    use RefreshDatabase;

    /** Status the fake Odoo connector answers with. */
    protected int $odooStatus = 200;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DemoSeeder::class);
        config([
            'services.odoo.connector_url' => 'http://odoo.test',
            'services.odoo.webhook_secret' => 'test-secret',
        ]);
        // One stub only: Http::fake() appends stubs and the first match wins,
        // so a second Http::fake() in a test would be shadowed by this one.
        Http::fake(['odoo.test/*' => fn () => Http::response(['ok' => true], $this->odooStatus)]);
    }

    protected function placeOrder(): Order
    {
        $variantId = ProductVariant::where('sku', 'GSC-MEGUMI-18-STD')->value('id');

        return app(CheckoutService::class)->checkout([
            'items' => [['variant_id' => $variantId, 'quantity' => 1]],
            'customer' => ['name' => 'Tester', 'email' => 't@t.vn', 'phone' => '0901'],
            'payment_method' => 'cod',
            'addresses' => [['type' => 'shipping', 'recipient_name' => 'Tester', 'phone' => '0901', 'line1' => '1 Le Loi']],
        ], null, 'idem-'.uniqid());
    }

    public function test_confirming_a_payment_pushes_the_order_to_odoo(): void
    {
        $order = $this->placeOrder();
        $payment = Payment::where('order_id', $order->id)->firstOrFail();

        app(PaymentService::class)->confirm($payment->id, null);

        Http::assertSent(fn (Request $r) => str_contains($r->url(), '/ingest/wibu')
            && $r->hasHeader('X-Signature')
            && str_contains($r->body(), $order->order_number));
    }

    /** Webhook-confirmed orders used to never reach Odoo at all. */
    public function test_webhook_confirmation_pushes_the_order_to_odoo(): void
    {
        $order = $this->placeOrder();
        $payment = Payment::where('order_id', $order->id)->firstOrFail();

        $result = app(PaymentService::class)->handleWebhook('gateway', 'evt-1', [
            'payment_id' => $payment->id,
            'status' => 'paid',
        ]);

        $this->assertFalse($result['deduped']);
        Http::assertSent(fn (Request $r) => str_contains($r->url(), '/ingest/wibu')
            && str_contains($r->body(), $order->order_number));
    }

    public function test_replayed_webhook_is_deduped_and_does_not_push_again(): void
    {
        $order = $this->placeOrder();
        $payment = Payment::where('order_id', $order->id)->firstOrFail();
        $service = app(PaymentService::class);

        $service->handleWebhook('gateway', 'evt-2', ['payment_id' => $payment->id, 'status' => 'paid']);
        Http::assertSentCount(1);

        $replay = $service->handleWebhook('gateway', 'evt-2', ['payment_id' => $payment->id, 'status' => 'paid']);

        $this->assertTrue($replay['deduped']);
        Http::assertSentCount(1);
        $this->assertSame(1, PaymentEvent::where('event_id', 'evt-2')->count());
    }

    /** A connector that answers 5xx used to fail silently — nothing was logged. */
    public function test_rejected_payload_is_logged(): void
    {
        $this->odooStatus = 500;
        Log::spy();

        $order = $this->placeOrder();
        $payment = Payment::where('order_id', $order->id)->firstOrFail();
        app(PaymentService::class)->confirm($payment->id, null);

        Log::shouldHaveReceived('warning')
            ->withArgs(fn ($message) => str_contains((string) $message, 'OdooHook rejected'));
    }
}
