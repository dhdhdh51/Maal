<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Video extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id', 'uploaded_by', 'title', 'slug', 'description',
        'poster_path', 'teaser_path', 'hls_master_path',
        'original_filename', 'original_path', 'checksum', 'file_size',
        'duration', 'width', 'height', 'source_format', 'available_qualities',
        'is_locked', 'is_free_preview', 'preview_seconds', 'preview_start',
        'processing_status', 'processing_progress', 'is_published', 'is_disabled',
        'published_at', 'watermark_enabled', 'views_count', 'preview_plays_count',
        'seo_title', 'meta_description', 'noindex',
    ];

    protected function casts(): array
    {
        return [
            'available_qualities' => 'array',
            'is_locked' => 'boolean',
            'is_free_preview' => 'boolean',
            'is_published' => 'boolean',
            'is_disabled' => 'boolean',
            'noindex' => 'boolean',
            'watermark_enabled' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    // ---------------------------------------------------------------------
    // Relationships
    // ---------------------------------------------------------------------

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function files(): HasMany
    {
        return $this->hasMany(VideoFile::class);
    }

    public function preview(): HasOne
    {
        return $this->hasOne(VideoPreview::class);
    }

    public function subtitles(): HasMany
    {
        return $this->hasMany(VideoSubtitle::class);
    }

    public function thumbnails(): HasMany
    {
        return $this->hasMany(VideoThumbnail::class)->orderBy('sort_order');
    }

    public function processingJobs(): HasMany
    {
        return $this->hasMany(VideoProcessingJob::class);
    }

    // ---------------------------------------------------------------------
    // Scopes
    // ---------------------------------------------------------------------

    public function scopePlayable(Builder $query): Builder
    {
        return $query->where('is_published', true)
            ->where('is_disabled', false)
            ->where('processing_status', 'ready');
    }

    public function scopeVisible(Builder $query): Builder
    {
        // Visible in listings even if locked, but not disabled.
        return $query->where('is_disabled', false)->where('is_published', true);
    }

    // ---------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------

    public function isReady(): bool
    {
        return $this->processing_status === 'ready';
    }

    /**
     * Preview length resolution order: video > category > global default.
     */
    public function effectivePreviewSeconds(): int
    {
        if ($this->preview_seconds) {
            return $this->preview_seconds;
        }

        if ($this->relationLoaded('category') ? $this->category : $this->category()->first()) {
            $cat = $this->category;
            if ($cat && $cat->preview_seconds) {
                return $cat->preview_seconds;
            }
        }

        return (int) config('streaming.preview.default_seconds', 20);
    }

    public function watermarkEnabled(): bool
    {
        if (! is_null($this->watermark_enabled)) {
            return $this->watermark_enabled;
        }

        if ($this->category && ! is_null($this->category->watermark_enabled)) {
            return $this->category->watermark_enabled;
        }

        return (bool) config('streaming.watermark.enabled', false);
    }
}
