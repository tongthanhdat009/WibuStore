<?php

namespace Tests\Feature;

use App\Domain\Payments\PaymentService;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ProductVariant;
use Database\Seeders\DemoSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(DemoSeeder::class);
    }

    public function test_confirm_idempotent_and_webhook_replay_deduped(): void
    {
        $v = ProductVariant::where('sku', 'GSC-MEGUMI-18-STD')->firstOrFail();
        $order = $this->postJson('/api/v1/orders', [
            'items' => [['variant_id' => $v->id, 'quantity' => 1]],
            'customer' => ['name' => 'P', 'email' => 'p@p.vn', 'phone' => '0901'],
            'payment_method' => 'bank_transfer',
            'addresses' => [['type' => 'shipping', 'recipient_name' => 'P', 'phone' => '0901', 'line1' => '1 Le Loi']],
        ], ['Idempotency-Key' => 'k-pay-1'])->assertCreated()->json('data');

        $payment = Payment::where('order_id', Order::where('public_id', $order['id'])->first()->id)->firstOrFail();
        $svc = app(PaymentService::class);
        $svc->confirm($payment->id, null);
        $svc->confirm($payment->id, null); // replay: no double count

        $fresh = Order::where('public_id', $order['id'])->first();
        $this->assertSame('paid', $fresh->payment_status);
        $this->assertSame($fresh->total_amount, $fresh->paid_amount);

        $r1 = $svc->handleWebhook('testpay', 'evt-1', ['payment_id' => $payment->id, 'status' => 'paid', 'amount' => 1]);
        $r2 = $svc->handleWebhook('testpay', 'evt-1', ['payment_id' => $payment->id, 'status' => 'paid', 'amount' => 1]);
        $this->assertFalse($r1['deduped']);
        $this->assertTrue($r2['deduped']);
    }
}
