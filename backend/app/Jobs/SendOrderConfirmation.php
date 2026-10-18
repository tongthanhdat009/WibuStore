<?php

namespace App\Jobs;

use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendOrderConfirmation implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $orderId) {}

    public function handle(): void
    {
        $order = Order::find($this->orderId);
        if (! $order) {
            return;
        }
        Mail::raw("Cam on ban da dat hang {$order->order_number}. Tong: {$order->total_amount} VND.", function ($m) use ($order) {
            $m->to($order->email)->subject("Xac nhan don hang {$order->order_number}");
        });
    }
}
