<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserCategoryAccess extends Model
{
    protected $table = 'user_category_access';

    protected $fillable = [
        'user_id', 'category_id', 'plan_id', 'payment_id', 'source',
        'status', 'granted_at', 'expires_at', 'granted_by', 'note',
    ];

    protected function casts(): array
    {
        return [
            'granted_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(CategoryAccessPlan::class, 'plan_id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
    }

    public function isValid(): bool
    {
        return $this->status === 'active'
            && (is_null($this->expires_at) || $this->expires_at->isFuture());
    }
}
