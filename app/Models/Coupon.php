<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'code', 'description', 'type', 'value', 'max_discount', 'min_order_amount',
        'category_id', 'category_ids', 'usage_limit', 'per_user_limit', 'used_count',
        'starts_at', 'expires_at', 'first_purchase_only', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'max_discount' => 'decimal:2',
            'min_order_amount' => 'decimal:2',
            'category_ids' => 'array',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'first_purchase_only' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(CouponRedemption::class);
    }

    public function isWithinWindow(): bool
    {
        $now = now();

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    public function hasUsageLeft(): bool
    {
        return is_null($this->usage_limit) || $this->used_count < $this->usage_limit;
    }

    public function appliesToCategory(int $categoryId): bool
    {
        if ($this->category_id && $this->category_id === $categoryId) {
            return true;
        }

        if (! empty($this->category_ids)) {
            return in_array($categoryId, array_map('intval', $this->category_ids), true);
        }

        // No scope set = all categories
        return is_null($this->category_id) && empty($this->category_ids);
    }

    public function discountFor(float $amount): float
    {
        return match ($this->type) {
            'percentage' => min(
                $amount,
                $this->max_discount
                    ? min($amount * ($this->value / 100), (float) $this->max_discount)
                    : $amount * ($this->value / 100)
            ),
            'flat' => min($amount, (float) $this->value),
            'free_access' => $amount,
            default => 0.0,
        };
    }
}
