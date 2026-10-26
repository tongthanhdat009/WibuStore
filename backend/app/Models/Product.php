<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = ['public_id', 'name', 'slug', 'anime_series_id', 'manufacturer_id', 'short_description', 'description', 'attributes', 'status', 'seo_title', 'seo_description', 'published_at'];

    protected $casts = ['attributes' => 'array', 'published_at' => 'datetime'];

    protected static function booted(): void
    {
        static::creating(function (Product $p) {
            $p->public_id ??= (string) Str::uuid();
            $p->slug ??= Str::slug($p->name).'-'.substr((string) Str::uuid(), 0, 8);
        });
    }

    public function series()
    {
        return $this->belongsTo(AnimeSeries::class, 'anime_series_id');
    }

    public function manufacturer()
    {
        return $this->belongsTo(Manufacturer::class);
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function activeVariants()
    {
        return $this->hasMany(ProductVariant::class)->where('status', 'active');
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'product_categories');
    }

    public function media()
    {
        return $this->hasMany(ProductMedia::class)->orderBy('sort_order');
    }

    public function primaryMedia()
    {
        return $this->hasOne(ProductMedia::class)->where('is_primary', true);
    }

    public function scopeActive($q)
    {
        return $q->where('status', 'active');
    }

    public function scopePublished($q)
    {
        return $q->active()->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    /**
     * Storefront availability: 'pre_order' when an open campaign still has quota,
     * 'sold_out' when no active variant has stock left, else 'in_stock'.
     *
     * Expects `activeVariants.inventoryLevels` and
     * `activeVariants.openPreorderCampaigns` to be eager loaded.
     */
    public function availability(): string
    {
        foreach ($this->activeVariants as $variant) {
            foreach ($variant->openPreorderCampaigns as $campaign) {
                if ($campaign->available > 0) {
                    return 'pre_order';
                }
            }
        }

        foreach ($this->activeVariants as $variant) {
            // A variant that does not track inventory is never stock-checked at
            // checkout, so it is always orderable.
            if (! $variant->track_inventory) {
                return 'in_stock';
            }
            foreach ($variant->inventoryLevels as $level) {
                if ($level->available > 0) {
                    return 'in_stock';
                }
            }
        }

        return 'sold_out';
    }
}
