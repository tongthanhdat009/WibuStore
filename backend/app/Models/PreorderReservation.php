<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PreorderReservation extends Model
{
    public $timestamps = true;

    protected $fillable = ['campaign_id', 'order_item_id', 'quantity', 'status', 'deposit_required_amount', 'balance_due_amount', 'deposit_deadline_at', 'balance_deadline_at', 'deposit_paid_at'];

    protected $casts = ['deposit_deadline_at' => 'datetime', 'balance_deadline_at' => 'datetime', 'deposit_paid_at' => 'datetime'];

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id');
    }
}
