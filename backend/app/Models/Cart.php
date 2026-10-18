<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    protected $fillable = ['user_id', 'guest_token_hash', 'cart_type', 'expires_at'];

    protected $casts = ['expires_at' => 'datetime'];

    public function items()
    {
        return $this->hasMany(CartItem::class);
    }
}
