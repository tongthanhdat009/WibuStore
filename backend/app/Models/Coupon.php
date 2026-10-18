<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $fillable = ['code', 'discount_type', 'discount_value', 'max_discount_amount', 'min_order_amount', 'starts_at', 'ends_at', 'usage_limit', 'per_user_limit', 'used_count', 'is_active', 'rules'];

    protected $casts = ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'is_active' => 'boolean', 'rules' => 'array'];

    public function isUsable(int $orderAmount = 0): bool
    {
        if (! $this->is_active) {
            return false;
        }
        $now = now();
        if ($this->starts_at && $now->lt($this->starts_at)) {
            return false;
        }
        if ($this->ends_at && $now->gt($this->ends_at)) {
            return false;
        }
        if ($this->usage_limit !== null && $this->used_count >= $this->usage_limit) {
            return false;
        }
        if ($orderAmount < (int) $this->min_order_amount) {
            return false;
        }

        return true;
    }

    public function discountFor(int $orderAmount): int
    {
        if (! $this->isUsable($orderAmount)) {
            return 0;
        }
        $d = $this->discount_type === 'percent'
            ? intdiv($orderAmount * (int) $this->discount_value, 100)
            : (int) $this->discount_value;
        if ($this->max_discount_amount !== null) {
            $d = min($d, (int) $this->max_discount_amount);
        }

        return max(0, min($d, $orderAmount));
    }
}
