<?php

use App\Domain\Orders\OrderService;
use App\Models\Cart;
use App\Models\IdempotencyKey;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Idempotent scheduled tasks (spec 11.3)
Schedule::call(fn (OrderService $orders) => $orders->expireUnpaid())->name('expire-unpaid')->everyFiveMinutes();
Schedule::call(fn () => Cart::where('expires_at', '<', now())->delete())->name('purge-carts')->daily();
Schedule::call(fn () => IdempotencyKey::where('expires_at', '<', now())->delete())->name('purge-idempotency')->daily();
