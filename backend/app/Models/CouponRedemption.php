<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CouponRedemption extends Model
{
    public $timestamps = false;

    protected $fillable = ['coupon_id', 'order_id', 'user_id', 'discount_amount'];
}
