<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    public $timestamps = false;

    protected $fillable = ['order_id', 'variant_id', 'preorder_campaign_id', 'sku_snapshot', 'product_name_snapshot', 'variant_label_snapshot', 'unit_price_amount', 'quantity', 'line_total_amount', 'created_at'];

    public function reservation()
    {
        return $this->hasOne(PreorderReservation::class, 'order_item_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
