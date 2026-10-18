<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderAddress extends Model
{
    public $timestamps = false;

    protected $fillable = ['order_id', 'type', 'recipient_name', 'phone', 'line1', 'line2', 'ward', 'district', 'province', 'country', 'postal_code'];
}
