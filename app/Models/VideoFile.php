<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VideoFile extends Model
{
    protected $fillable = [
        'video_id', 'quality', 'height', 'width', 'bitrate',
        'playlist_path', 'file_size', 'is_ready',
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
