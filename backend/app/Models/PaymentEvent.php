<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentEvent extends Model
{
    public $timestamps = false;

    protected $fillable = ['provider', 'event_id', 'payment_id', 'payload_redacted', 'processing_status'];

    protected $casts = ['payload_redacted' => 'array'];
}
