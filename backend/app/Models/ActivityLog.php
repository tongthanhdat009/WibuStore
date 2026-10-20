<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['actor_user_id', 'action', 'subject_type', 'subject_id', 'before_redacted', 'after_redacted', 'request_id', 'ip_address', 'created_at'];

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    protected $casts = ['before_redacted' => 'array', 'after_redacted' => 'array'];

    public static function record(string $action, ?string $subjectType, ?string $subjectId, array $before = [], array $after = [], ?int $actorId = null): void
    {
        static::create([
            'actor_user_id' => $actorId ?? auth()->id(),
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'before_redacted' => $before,
            'after_redacted' => $after,
            'request_id' => request()->header('X-Request-ID'),
            'ip_address' => request()->ip(),
            'created_at' => now(),
        ]);
    }
}
