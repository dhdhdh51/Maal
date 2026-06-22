<?php

namespace App\Services\Offers;

use App\Models\Payment;
use App\Models\Referral;
use App\Services\Notifier;

/**
 * Grants referral rewards once the referred user completes their first paid
 * order. Rewards are configurable per-referral (reward_type/value).
 */
class ReferralService
{
    public function __construct(
        protected WalletService $wallet,
        protected Notifier $notifier,
    ) {}

    public function rewardForPayment(Payment $payment): void
    {
        $user = $payment->user;

        if (! $user || ! $user->referred_by) {
            return;
        }

        // Only reward on the user's FIRST paid order.
        $paidCount = $user->payments()->where('status', 'paid')->count();
        if ($paidCount > 1) {
            return;
        }

        $referral = Referral::where('referred_user_id', $user->id)
            ->where('status', 'pending')
            ->first();

        if (! $referral) {
            return;
        }

        $referrer = $referral->referrer;
        $value = (float) ($referral->reward_value ?: setting('referral_reward_value', 0));

        switch ($referral->reward_type) {
            case 'wallet_credit':
            case 'points':
                if ($referrer && $value > 0) {
                    $this->wallet->credit($referrer, $value, 'referral reward');
                }
                break;
                // discount / free_access / preview_extension: recorded; redeemed elsewhere.
            default:
                break;
        }

        $referral->update([
            'payment_id' => $payment->id,
            'status' => 'rewarded',
            'rewarded_at' => now(),
        ]);

        if ($referrer) {
            $this->notifier->notify(
                $referrer,
                'referral_reward',
                'Referral reward earned',
                'Someone you referred made their first purchase. Your reward has been applied.',
                url('/dashboard'),
                'gift',
            );
        }
    }
}
