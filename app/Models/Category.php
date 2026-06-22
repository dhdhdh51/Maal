<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'description', 'cover_image', 'banner_image',
        'sort_order', 'is_active', 'access_type', 'price', 'currency',
        'validity_days', 'preview_seconds', 'country_availability',
        'device_limit', 'watermark_enabled', 'seo_title', 'meta_description',
        'canonical_url', 'noindex', 'videos_count',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'noindex' => 'boolean',
            'watermark_enabled' => 'boolean',
            'price' => 'decimal:2',
            'country_availability' => 'array',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function videos(): HasMany
    {
        return $this->hasMany(Video::class);
    }

    public function plans(): HasMany
    {
        return $this->hasMany(CategoryAccessPlan::class);
    }

    public function accessRecords(): HasMany
    {
        return $this->hasMany(UserCategoryAccess::class);
    }

    public function isFree(): bool
    {
        return $this->access_type === 'free';
    }

    public function effectivePreviewSeconds(): int
    {
        return $this->preview_seconds ?? (int) config('streaming.preview.default_seconds', 20);
    }

    public function availableInCountry(?string $country): bool
    {
        if (empty($this->country_availability)) {
            return true;
        }

        return $country !== null && in_array(strtoupper($country), array_map('strtoupper', $this->country_availability), true);
    }
}
