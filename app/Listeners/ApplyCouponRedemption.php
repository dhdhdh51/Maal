<?php

namespace App\Listeners;

use App\Events\PaymentCompleted;
use App\Services\Offers\CouponService;

class ApplyCouponRedemption
{
    public function __construct(protected CouponService $coupons) {}

    public function handle(PaymentCompleted $event): void
    {
        $payment = $event->payment;

        if ($payment->coupon_id && $payment->coupon) {
            $this->coupons->redeem($payment->coupon, $payment->user, $payment);
        }
    }
}
