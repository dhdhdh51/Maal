<?php

namespace App\Services\Payments\Gateways;

use App\Models\Payment;
use App\Services\Payments\Contracts\PaymentGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class StripeGateway implements PaymentGateway
{
    public function key(): string
    {
        return 'stripe';
    }

    public function label(): string
    {
        return (string) config('payments.gateways.stripe.label', 'Stripe');
    }

    public function isEnabled(): bool
    {
        return (bool) config('payments.gateways.stripe.enabled', false);
    }

    protected function secret(): string
    {
        return (string) config('payments.gateways.stripe.secret');
    }

    public function createOrder(Payment $payment): array
    {
        // Hosted Stripe Checkout Session (redirect flow).
        $response = Http::withToken($this->secret())
            ->asForm()
            ->post('https://api.stripe.com/v1/checkout/sessions', [
                'mode' => 'payment',
                'success_url' => route('payment.success').'?ref='.$payment->reference,
                'cancel_url' => route('payment.failed').'?ref='.$payment->reference,
                'client_reference_id' => $payment->reference,
                'line_items[0][quantity]' => 1,
                'line_items[0][price_data][currency]' => strtolower($payment->currency),
                'line_items[0][price_data][unit_amount]' => (int) round((float) $payment->total * 100),
                'line_items[0][price_data][product_data][name]' => 'Access #'.$payment->id,
            ]);

        $session = $response->json();
        $payment->update(['gateway_order_id' => $session['id'] ?? null]);

        return [
            'type' => 'redirect',
            'url' => $session['url'] ?? route('payment.failed'),
        ];
    }

    public function verifyReturn(Request $request): ?array
    {
        // Stripe confirms via webhook; the return page is informational only.
        return null;
    }

    public function parseWebhook(Request $request): ?array
    {
        $payload = $request->getContent();
        $sigHeader = (string) $request->header('Stripe-Signature', '');
        $secret = (string) config('payments.gateways.stripe.webhook_secret');

        $valid = $this->verifyStripeSignature($payload, $sigHeader, $secret);

        $data = json_decode($payload, true) ?: [];
        $type = (string) ($data['type'] ?? 'unknown');
        $object = $data['data']['object'] ?? [];

        $statusMap = [
            'checkout.session.completed' => 'paid',
            'payment_intent.payment_failed' => 'failed',
            'charge.refunded' => 'refunded',
        ];

        return [
            'reference' => $object['client_reference_id'] ?? null,
            'gateway_payment_id' => $object['payment_intent'] ?? ($object['id'] ?? null),
            'status' => $statusMap[$type] ?? 'processing',
            'event' => $type,
            'idempotency_key' => 'stripe:'.(string) ($data['id'] ?? $type),
            'signature_valid' => $valid,
        ];
    }

    protected function verifyStripeSignature(string $payload, string $header, string $secret): bool
    {
        if ($secret === '' || $header === '') {
            return false;
        }

        $parts = [];
        foreach (explode(',', $header) as $kv) {
            [$k, $v] = array_pad(explode('=', $kv, 2), 2, '');
            $parts[$k][] = $v;
        }

        $timestamp = $parts['t'][0] ?? null;
        $signatures = $parts['v1'] ?? [];

        if (! $timestamp || empty($signatures)) {
            return false;
        }

        // Replay protection.
        if (abs(now()->getTimestamp() - (int) $timestamp) > (int) config('payments.webhooks.tolerance_seconds', 300)) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp.'.'.$payload, $secret);

        foreach ($signatures as $sig) {
            if (hash_equals($expected, $sig)) {
                return true;
            }
        }

        return false;
    }
}
