<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PreviewAnalytic extends Model
{
    protected $table = 'preview_analytics';

    protected $fillable = [
        'video_id', 'category_id', 'user_id', 'payment_id',
        'preview_completed', 'completion_percent', 'clicked_unlock',
        'converted', 'checkout_abandoned', 'country', 'device_type',
        'traffic_source', 'session_id',
    ];

    protected function casts(): array
    {
        return [
            'preview_completed' => 'boolean',
            'clicked_unlock' => 'boolean',
            'converted' => 'boolean',
            'checkout_abandoned' => 'boolean',
        ];
    }

    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
