<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    protected $fillable = ['cart_id', 'variant_id', 'preorder_campaign_id', 'quantity'];

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function campaign()
    {
        return $this->belongsTo(PreorderCampaign::class, 'preorder_campaign_id', 'id');
    }
}
