<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserAddress extends Model
{
    protected $fillable = ['user_id', 'recipient_name', 'phone', 'line1', 'line2', 'ward', 'district', 'province', 'country', 'postal_code', 'is_default'];

    protected $casts = ['is_default' => 'boolean'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
