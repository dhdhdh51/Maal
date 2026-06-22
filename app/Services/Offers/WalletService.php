<?php

namespace App\Services\Offers;

use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;

/**
 * Simple stored-value wallet on the user record. Balance changes are audited.
 */
class WalletService
{
    public function credit(User $user, float $amount, string $reason = 'credit'): void
    {
        if ($amount <= 0) {
            return;
        }

        DB::transaction(function () use ($user, $amount, $reason) {
            $user->forceFill(['wallet_balance' => round((float) $user->wallet_balance + $amount, 2)])->save();
            AuditLogger::log('wallet.credit', $user, "Wallet credited {$amount} ({$reason})", actor: $user);
        });
    }

    public function debit(User $user, float $amount, string $reason = 'debit'): float
    {
        $amount = round(min($amount, (float) $user->wallet_balance), 2);

        if ($amount <= 0) {
            return 0.0;
        }

        DB::transaction(function () use ($user, $amount, $reason) {
            $user->forceFill(['wallet_balance' => round((float) $user->wallet_balance - $amount, 2)])->save();
            AuditLogger::log('wallet.debit', $user, "Wallet debited {$amount} ({$reason})", actor: $user);
        });

        return $amount;
    }

    /**
     * Maximum wallet amount usable against an order total.
     */
    public function applicable(User $user, float $orderTotal): float
    {
        return round(min((float) $user->wallet_balance, $orderTotal), 2);
    }
}
