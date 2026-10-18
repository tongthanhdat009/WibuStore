<?php

namespace App\Domain\Payments;

use App\Models\ActivityLog;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentEvent;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function confirm(int $paymentId, ?int $actorId, ?string $providerRef = null): Payment
    {
        return DB::transaction(function () use ($paymentId, $actorId, $providerRef) {
            $payment = Payment::where('id', $paymentId)->lockForUpdate()->firstOrFail();
            if ($payment->status === 'confirmed') {
                return $payment;
            }
            if ($payment->status !== 'pending') {
                abort(response()->json(['error' => ['code' => 'INVALID_STATE', 'message' => 'Payment khong o trang thai pending']], 409));
            }
            if ($providerRef && Payment::where('provider', $payment->provider)->where('provider_reference', $providerRef)->exists()) {
                abort(response()->json(['error' => ['code' => 'INVALID_STATE', 'message' => 'Trung provider reference']], 409));
            }
            $payment->update(['status' => 'confirmed', 'confirmed_at' => now(), 'confirmed_by' => $actorId, 'provider_reference' => $providerRef ?? $payment->provider_reference]);
            $this->reconcile($payment->order_id);
            ActivityLog::record('payment.confirmed', 'payment', (string) $payment->id, [], ['order_id' => $payment->order_id, 'amount' => $payment->amount], $actorId);

            return $payment->fresh();
        });
    }

    public function reject(int $paymentId, ?int $actorId, string $reason): Payment
    {
        return DB::transaction(function () use ($paymentId, $actorId, $reason) {
            $payment = Payment::where('id', $paymentId)->lockForUpdate()->firstOrFail();
            if ($payment->status !== 'pending') {
                abort(response()->json(['error' => ['code' => 'INVALID_STATE', 'message' => 'Payment khong o trang thai pending']], 409));
            }
            $payment->update(['status' => 'failed', 'reject_reason' => $reason]);
            ActivityLog::record('payment.rejected', 'payment', (string) $payment->id, [], ['reason' => $reason], $actorId);

            return $payment->fresh();
        });
    }

    public function handleWebhook(string $provider, string $eventId, array $payload): array
    {
        return DB::transaction(function () use ($provider, $eventId, $payload) {
            $existing = PaymentEvent::where('provider', $provider)->where('event_id', $eventId)->first();
            if ($existing) {
                return ['deduped' => true, 'event_id' => $eventId];
            }
            $paymentId = $payload['payment_id'] ?? null;
            $event = PaymentEvent::create([
                'provider' => $provider, 'event_id' => $eventId, 'payment_id' => $paymentId,
                'payload_redacted' => ['status' => $payload['status'] ?? null, 'amount' => $payload['amount'] ?? null],
                'processing_status' => 'processed',
            ]);
            if ($paymentId && ($payload['status'] ?? null) === 'paid') {
                $payment = Payment::where('id', $paymentId)->lockForUpdate()->first();
                if ($payment && $payment->status === 'pending') {
                    $payment->update(['status' => 'confirmed', 'confirmed_at' => now(), 'provider_reference' => $eventId]);
                    $this->reconcile($payment->order_id);
                }
            }

            return ['deduped' => false, 'event_id' => $event->event_id];
        });
    }

    public function reconcile(int $orderId): void
    {
        $order = Order::where('id', $orderId)->lockForUpdate()->firstOrFail();
        $paid = (int) Payment::where('order_id', $orderId)->where('status', 'confirmed')->whereIn('kind', ['full', 'deposit', 'balance'])->sum('amount');
        $refunded = (int) Payment::where('order_id', $orderId)->where('status', 'refunded')->where('kind', 'refund')->sum('amount');
        $net = max(0, $paid - $refunded);
        $status = $net <= 0 ? 'unpaid' : ($net >= $order->total_amount ? 'paid' : 'partially_paid');
        $order->update(['paid_amount' => $net, 'payment_status' => $status]);
    }
}
