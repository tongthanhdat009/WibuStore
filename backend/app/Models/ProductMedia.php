<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductMedia extends Model
{
    protected $table = 'product_media';

    protected $fillable = ['product_id', 'variant_id', 'media_asset_id', 'sort_order', 'is_primary'];

    protected $casts = ['is_primary' => 'boolean'];

    public function asset()
    {
        return $this->belongsTo(MediaAsset::class, 'media_asset_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
