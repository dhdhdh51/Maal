<?php

namespace App\Services\Offers;

use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\Payment;
use App\Models\User;

/**
 * Validates and redeems coupon codes. Enforces window, usage limits, per-user
 * limits, minimum order, first-purchase-only and category scoping.
 */
class CouponService
{
    /**
     * @return array{valid:bool, discount:float, coupon:?Coupon, reason:?string}
     */
    public function validate(string $code, User $user, ?int $categoryId, float $amount): array
    {
        $coupon = Coupon::whereRaw('LOWER(code) = ?', [strtolower(trim($code))])->first();

        if (! $coupon || ! $coupon->is_active) {
            return $this->fail('This code is not valid.');
        }

        if (! $coupon->isWithinWindow()) {
            return $this->fail('This code has expired or is not active yet.');
        }

        if (! $coupon->hasUsageLeft()) {
            return $this->fail('This code has reached its usage limit.');
        }

        if ($coupon->min_order_amount && $amount < (float) $coupon->min_order_amount) {
            return $this->fail('Order does not meet the minimum for this code.');
        }

        if ($categoryId && ! $coupon->appliesToCategory($categoryId)) {
            return $this->fail('This code does not apply to this category.');
        }

        if ($coupon->first_purchase_only && $user->payments()->where('status', 'paid')->exists()) {
            return $this->fail('This code is for first purchases only.');
        }

        $usedByUser = CouponRedemption::where('coupon_id', $coupon->id)
            ->where('user_id', $user->id)->count();

        if ($usedByUser >= $coupon->per_user_limit) {
            return $this->fail('You have already used this code.');
        }

        return [
            'valid' => true,
            'discount' => round($coupon->discountFor($amount), 2),
            'coupon' => $coupon,
            'reason' => null,
        ];
    }

    /**
     * Record a redemption + increment usage. Idempotent per (coupon, payment).
     */
    public function redeem(Coupon $coupon, User $user, Payment $payment): void
    {
        $already = CouponRedemption::where('coupon_id', $coupon->id)
            ->where('payment_id', $payment->id)->exists();

        if ($already) {
            return;
        }

        CouponRedemption::create([
            'coupon_id' => $coupon->id,
            'user_id' => $user->id,
            'payment_id' => $payment->id,
            'discount_applied' => $payment->discount,
        ]);

        $coupon->increment('used_count');
    }

    /**
     * @return array{valid:bool, discount:float, coupon:null, reason:string}
     */
    protected function fail(string $reason): array
    {
        return ['valid' => false, 'discount' => 0.0, 'coupon' => null, 'reason' => $reason];
    }
}
