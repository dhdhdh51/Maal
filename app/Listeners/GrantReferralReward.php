<?php

namespace App\Listeners;

use App\Events\PaymentCompleted;
use App\Services\Offers\ReferralService;

class GrantReferralReward
{
    public function __construct(protected ReferralService $referrals) {}

    public function handle(PaymentCompleted $event): void
    {
        $this->referrals->rewardForPayment($event->payment);
    }
}
