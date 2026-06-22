<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VideoPreview extends Model
{
    protected $fillable = [
        'video_id', 'hls_path', 'mp4_path',
        'start_seconds', 'duration_seconds', 'is_ready',
    ];

    protected function casts(): array
    {
        return ['is_ready' => 'boolean'];
    }

    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }
}
