<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'public_id', 'phone', 'avatar', 'status'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            $user->public_id ??= (string) Str::uuid();
            $user->status ??= 'active';
        });
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_user');
    }

    public function addresses()
    {
        return $this->hasMany(UserAddress::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->roles()->where('slug', 'super_admin')->exists()) {
            return true;
        }

        return $this->roles()->whereHas('permissions', fn ($q) => $q->where('slug', $permission))->exists();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
