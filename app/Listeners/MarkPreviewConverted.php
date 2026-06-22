<?php

namespace App\Listeners;

use App\Events\PaymentCompleted;
use App\Models\PreviewAnalytic;

/**
 * When a payment completes, flag the buyer's recent preview engagements for the
 * purchased category as converted (for preview→payment conversion analytics).
 */
class MarkPreviewConverted
{
    public function handle(PaymentCompleted $event): void
    {
        $payment = $event->payment;

        if (! $payment->user_id) {
            return;
        }

        $query = PreviewAnalytic::where('user_id', $payment->user_id)
            ->where('converted', false)
            ->where('created_at', '>=', now()->subDays(7));

        if ($payment->category_id) {
            $query->where('category_id', $payment->category_id);
        }

        $query->update(['converted' => true, 'payment_id' => $payment->id]);
    }
}
