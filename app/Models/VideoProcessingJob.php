<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VideoProcessingJob extends Model
{
    protected $fillable = [
        'video_id', 'stage', 'quality', 'status', 'progress', 'attempts',
        'log', 'error_message', 'queue_job_id', 'started_at', 'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }

    public function markProcessing(): void
    {
        $this->update([
            'status' => 'processing',
            'started_at' => $this->started_at ?? now(),
            'attempts' => $this->attempts + 1,
        ]);
    }

    public function markCompleted(): void
    {
        $this->update(['status' => 'completed', 'progress' => 100, 'finished_at' => now()]);
    }

    public function markFailed(string $message): void
    {
        $this->update(['status' => 'failed', 'error_message' => $message, 'finished_at' => now()]);
    }
}
