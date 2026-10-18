<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Order extends Model
{
    protected $fillable = ['public_id', 'order_number', 'user_id', 'lookup_token_hash', 'checkout_type', 'customer_name', 'email', 'phone', 'order_status', 'payment_status', 'fulfillment_status', 'subtotal_amount', 'discount_amount', 'shipping_amount', 'total_amount', 'paid_amount', 'currency', 'placed_at', 'expires_at', 'customer_note', 'internal_note'];

    protected $casts = ['placed_at' => 'datetime', 'expires_at' => 'datetime'];

    protected static function booted(): void
    {
        static::creating(function (Order $o) {
            $o->public_id ??= (string) Str::uuid();
            $o->order_number ??= 'WB-'.now()->format('Ymd').'-'.str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        });
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function addresses()
    {
        return $this->hasMany(OrderAddress::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function history()
    {
        return $this->hasMany(OrderStatusHistory::class)->orderBy('created_at');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
