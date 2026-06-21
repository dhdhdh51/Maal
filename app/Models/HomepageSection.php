<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HomepageSection extends Model
{
    protected $fillable = [
        'title', 'type', 'category_id', 'video_ids', 'item_limit',
        'layout', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'video_ids' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
