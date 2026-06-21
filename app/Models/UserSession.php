<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSession extends Model
{
    protected $fillable = [
        'user_id', 'device_id', 'token_id', 'ip_address', 'country',
        'user_agent', 'is_playing', 'playing_video_id', 'last_activity_at', 'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'is_playing' => 'boolean',
            'last_activity_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(UserDevice::class, 'device_id');
    }

    public function isActive(): bool
    {
        return is_null($this->revoked_at);
    }
}
