<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryLevel extends Model
{
    public $timestamps = true;

    protected $fillable = ['warehouse_id', 'variant_id', 'on_hand', 'reserved'];

    public function getAvailableAttribute(): int
    {
        return max(0, $this->on_hand - $this->reserved);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }
}
