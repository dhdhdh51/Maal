<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference', 'user_id', 'plan_id', 'category_id', 'coupon_id',
        'gateway', 'amount', 'discount', 'tax', 'total', 'currency', 'status',
        'gateway_payment_id', 'gateway_order_id', 'meta', 'invoice_number',
        'utm_source', 'utm_medium', 'utm_campaign', 'referred_by',
        'paid_at', 'refunded_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'discount' => 'decimal:2',
            'tax' => 'decimal:2',
            'total' => 'decimal:2',
            'meta' => 'array',
            'paid_at' => 'datetime',
            'refunded_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(CategoryAccessPlan::class, 'plan_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function markPaid(?string $gatewayPaymentId = null): void
    {
        $this->update([
            'status' => 'paid',
            'gateway_payment_id' => $gatewayPaymentId ?? $this->gateway_payment_id,
            'paid_at' => now(),
        ]);
    }
}
