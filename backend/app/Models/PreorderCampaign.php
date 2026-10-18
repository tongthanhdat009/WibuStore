<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PreorderCampaign extends Model
{
    public $timestamps = true;

    protected $fillable = ['variant_id', 'status', 'opens_at', 'closes_at', 'expected_release_at', 'expected_arrival_at', 'quota', 'reserved_quantity', 'deposit_per_unit_amount', 'sale_price_per_unit_amount', 'balance_due_days', 'terms_revision_id'];

    protected $casts = ['opens_at' => 'datetime', 'closes_at' => 'datetime', 'expected_release_at' => 'datetime', 'expected_arrival_at' => 'datetime'];

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function scopeOpen($q)
    {
        return $q->where('status', 'open')->where('opens_at', '<=', now())->where(function ($w) {
            $w->whereNull('closes_at')->orWhere('closes_at', '>', now());
        });
    }

    public function getAvailableAttribute(): int
    {
        return max(0, $this->quota - $this->reserved_quantity);
    }
}
