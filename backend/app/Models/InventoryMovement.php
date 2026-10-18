<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryMovement extends Model
{
    public $timestamps = false;

    protected $fillable = ['warehouse_id', 'variant_id', 'delta_on_hand', 'delta_reserved', 'type', 'reason', 'reference_type', 'reference_id', 'idempotency_key', 'actor_user_id', 'created_at'];

    protected $casts = ['created_at' => 'datetime'];

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }
}
