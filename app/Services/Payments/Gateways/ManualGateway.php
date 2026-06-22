<?php

namespace App\Services\Payments\Gateways;

use App\Models\Payment;
use App\Services\Payments\Contracts\PaymentGateway;
use Illuminate\Http\Request;

/**
 * Manual / bank-transfer gateway. No external call — the order stays pending
 * until an admin approves it from the dashboard.
 */
class ManualGateway implements PaymentGateway
{
    public function key(): string
    {
        return 'manual';
    }

    public function label(): string
    {
        return (string) config('payments.gateways.manual.label', 'Manual / Bank Transfer');
    }

    public function isEnabled(): bool
    {
        return (bool) config('payments.gateways.manual.enabled', false);
    }

    public function createOrder(Payment $payment): array
    {
        $payment->update(['status' => 'pending']);

        return [
            'type' => 'manual',
            'payload' => [
                'reference' => $payment->reference,
                'amount' => $payment->total,
                'currency' => $payment->currency,
                'instructions' => (string) setting('manual_payment_instructions', 'Transfer the amount and await approval.'),
            ],
        ];
    }

    public function verifyReturn(Request $request): ?array
    {
        return null; // approved by admin, not by return
    }

    public function parseWebhook(Request $request): ?array
    {
        return null; // no webhook
    }
}
