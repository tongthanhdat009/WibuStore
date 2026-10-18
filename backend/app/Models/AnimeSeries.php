<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnimeSeries extends Model
{
    protected $fillable = ['name', 'slug', 'cover_media_id', 'description', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }
}
