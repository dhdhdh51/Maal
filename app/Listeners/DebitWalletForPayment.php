<?php

namespace App\Listeners;

use App\Events\PaymentCompleted;
use App\Services\Offers\WalletService;

class DebitWalletForPayment
{
    public function __construct(protected WalletService $wallet) {}

    public function handle(PaymentCompleted $event): void
    {
        $payment = $event->payment;
        $applied = (float) ($payment->meta['wallet_applied'] ?? 0);

        if ($applied > 0 && $payment->user) {
            $this->wallet->debit($payment->user, $applied, "order {$payment->reference}");
        }
    }
}
