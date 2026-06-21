<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NewsletterSubscriber extends Model
{
    protected $fillable = [
        'email', 'user_id', 'is_confirmed', 'source', 'utm_campaign', 'unsubscribed_at',
    ];

    protected function casts(): array
    {
        return [
            'is_confirmed' => 'boolean',
            'unsubscribed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
