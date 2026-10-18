<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IdempotencyKey extends Model
{
    protected $fillable = ['key', 'scope', 'response', 'order_id', 'expires_at'];

    protected $casts = ['response' => 'array', 'expires_at' => 'datetime'];
}
