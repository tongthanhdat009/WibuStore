<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderStatusHistory extends Model
{
    public $timestamps = false;

    protected $table = 'order_status_history';

    protected $fillable = ['order_id', 'from_status', 'to_status', 'reason', 'actor_user_id', 'created_at'];
}
