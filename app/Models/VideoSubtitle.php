<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VideoSubtitle extends Model
{
    protected $fillable = [
        'video_id', 'language', 'label', 'format', 'path', 'is_default', 'is_ready',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_ready' => 'boolean',
        ];
    }

    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }
}
