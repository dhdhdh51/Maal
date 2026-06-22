<?php

namespace App\Events;

use App\Models\Payment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a payment is captured. Listeners (coupons, referrals, analytics)
 * react to it in the offers system.
 */
class PaymentCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(public Payment $payment) {}
}
