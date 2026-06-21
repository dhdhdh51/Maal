<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Services\Notifier;
use Illuminate\Console\Command;

/**
 * Nudges users who started but never completed a checkout.
 */
class AbandonedCheckoutReminder extends Command
{
    protected $signature = 'maal:abandoned-checkout {--hours=2 : Minimum age before a started checkout is considered abandoned}';

    protected $description = 'Send reminders for abandoned checkouts (started but unpaid orders).';

    public function handle(Notifier $notifier): int
    {
        $cutoff = now()->subHours((int) $this->option('hours'));

        $abandoned = Payment::with('user')
            ->whereIn('status', ['pending', 'processing'])
            ->whereNull('meta->reminded_at')
            ->where('created_at', '<', $cutoff)
            ->where('created_at', '>', now()->subDays(7))
            ->limit(200)
            ->get();

        foreach ($abandoned as $payment) {
            if (! $payment->user) {
                continue;
            }

            $notifier->notify(
                $payment->user,
                'abandoned_checkout',
                'Complete your purchase',
                'You left an order unfinished. Complete it to unlock your content.',
                route('payment.pending', ['ref' => $payment->reference]),
                'cart',
            );

            $meta = $payment->meta ?? [];
            $meta['reminded_at'] = now()->toIso8601String();
            $payment->update(['meta' => $meta]);
        }

        $this->info("Sent {$abandoned->count()} abandoned-checkout reminder(s).");

        return self::SUCCESS;
    }
}
