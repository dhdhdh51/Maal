<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class CategoryAccessPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id', 'name', 'slug', 'description', 'type', 'price',
        'compare_at_price', 'currency', 'validity_days', 'bundle_category_ids',
        'is_active', 'is_featured', 'sort_order', 'trial_days',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'compare_at_price' => 'decimal:2',
            'bundle_category_ids' => 'array',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function isLifetime(): bool
    {
        return $this->type === 'lifetime' || is_null($this->validity_days);
    }

    public function isBundle(): bool
    {
        return $this->type === 'bundle';
    }

    /**
     * Category ids this plan grants access to.
     *
     * @return array<int>
     */
    public function coveredCategoryIds(): array
    {
        if ($this->isBundle() && ! empty($this->bundle_category_ids)) {
            return array_map('intval', $this->bundle_category_ids);
        }

        return $this->category_id ? [$this->category_id] : [];
    }

    public function expiresAtFromNow(): ?Carbon
    {
        return $this->validity_days ? now()->addDays($this->validity_days) : null;
    }
}
