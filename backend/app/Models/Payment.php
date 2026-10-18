<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = ['order_id', 'kind', 'method', 'provider', 'provider_reference', 'amount', 'status', 'confirmed_at', 'confirmed_by', 'idempotency_key', 'reject_reason'];

    protected $casts = ['confirmed_at' => 'datetime'];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
