<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\PaymentTransaction;
use App\Services\Payments\CheckoutService;
use App\Services\Payments\PaymentGatewayManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Receives asynchronous payment webhooks. Verifies signatures, enforces
 * idempotency (unique transaction key) and applies the resulting state.
 */
class WebhookController extends Controller
{
    public function __construct(
        protected PaymentGatewayManager $gateways,
        protected CheckoutService $checkout,
    ) {}

    public function handle(Request $request, string $gateway): JsonResponse
    {
        if (! $this->gateways->isEnabled($gateway)) {
            return response()->json(['ok' => false], 404);
        }

        $parsed = $this->gateways->gateway($gateway)->parseWebhook($request);

        if (! $parsed || ! $parsed['signature_valid']) {
            Log::warning("[webhook:{$gateway}] invalid signature or unparseable payload");

            return response()->json(['ok' => false], 400);
        }

        // Idempotency: a duplicate event is acknowledged but not reprocessed.
        $existing = PaymentTransaction::where('idempotency_key', $parsed['idempotency_key'])->first();
        if ($existing) {
            return response()->json(['ok' => true, 'duplicate' => true]);
        }

        $payment = $this->resolvePayment($parsed);

        PaymentTransaction::create([
            'payment_id' => $payment?->id,
            'gateway' => $gateway,
            'event' => $parsed['event'],
            'status' => $parsed['status'],
            'idempotency_key' => $parsed['idempotency_key'],
            'gateway_event_id' => $parsed['gateway_payment_id'],
            'signature_verified' => true,
            'request_payload' => $request->all() ?: ['raw' => $request->getContent()],
            'ip_address' => $request->ip(),
        ]);

        if ($payment) {
            match ($parsed['status']) {
                'paid' => $this->checkout->markPaid($payment, $parsed['gateway_payment_id']),
                'failed' => $this->checkout->markFailed($payment),
                'refunded' => $this->checkout->markRefunded($payment),
                default => null,
            };
        }

        return response()->json(['ok' => true]);
    }

    protected function resolvePayment(array $parsed): ?Payment
    {
        if (! empty($parsed['reference'])) {
            return Payment::where('reference', $parsed['reference'])->first();
        }

        if (! empty($parsed['gateway_payment_id'])) {
            return Payment::where('gateway_payment_id', $parsed['gateway_payment_id'])->first();
        }

        return null;
    }
}
