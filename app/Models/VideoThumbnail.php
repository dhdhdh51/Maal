<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VideoThumbnail extends Model
{
    protected $fillable = ['video_id', 'path', 'time_offset', 'sort_order'];

    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }
}
