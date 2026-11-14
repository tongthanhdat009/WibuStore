<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OdooSyncLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'direction', 'event_id', 'order_id', 'endpoint', 'status',
        'http_status', 'payload', 'response_body', 'error', 'attempts',
        'duration_ms', 'actor_user_id', 'request_id', 'ip_address', 'created_at',
    ];

    protected $casts = ['payload' => 'array'];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public static function record(array $attrs): static
    {
        return static::create(array_merge([
            'status' => 'pending',
            'attempts' => 1,
            'request_id' => request()->header('X-Request-ID'),
            'ip_address' => request()->ip(),
            'created_at' => now(),
        ], $attrs));
    }
}
