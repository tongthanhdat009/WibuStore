<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    protected $fillable = ['product_id', 'sku', 'label', 'attributes', 'price_amount', 'compare_at_amount', 'weight_grams', 'track_inventory', 'status'];

    protected $casts = ['attributes' => 'array', 'track_inventory' => 'boolean'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function inventoryLevels()
    {
        return $this->hasMany(InventoryLevel::class, 'variant_id');
    }

    public function preorderCampaigns()
    {
        return $this->hasMany(PreorderCampaign::class, 'variant_id');
    }

    /** Campaigns currently open for ordering (status + date window). */
    public function openPreorderCampaigns()
    {
        return $this->hasMany(PreorderCampaign::class, 'variant_id')->open();
    }

    public function scopeActive($q)
    {
        return $q->where('status', 'active');
    }
}
