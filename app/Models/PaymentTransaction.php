<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentTransaction extends Model
{
    protected $fillable = [
        'payment_id', 'gateway', 'event', 'status', 'idempotency_key',
        'gateway_event_id', 'signature_verified', 'amount', 'currency',
        'request_payload', 'response_payload', 'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'signature_verified' => 'boolean',
            'amount' => 'decimal:2',
            'request_payload' => 'array',
            'response_payload' => 'array',
        ];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
