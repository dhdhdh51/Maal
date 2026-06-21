<?php

namespace App\Services\Payments\Gateways;

use App\Models\Payment;
use App\Services\Payments\Contracts\PaymentGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class RazorpayGateway implements PaymentGateway
{
    public function key(): string
    {
        return 'razorpay';
    }

    public function label(): string
    {
        return (string) config('payments.gateways.razorpay.label', 'Razorpay');
    }

    public function isEnabled(): bool
    {
        return (bool) config('payments.gateways.razorpay.enabled', false);
    }

    protected function keyId(): string
    {
        return (string) config('payments.gateways.razorpay.key_id');
    }

    protected function secret(): string
    {
        return (string) config('payments.gateways.razorpay.key_secret');
    }

    public function createOrder(Payment $payment): array
    {
        $amountPaise = (int) round((float) $payment->total * 100);

        $response = Http::withBasicAuth($this->keyId(), $this->secret())
            ->asJson()
            ->post('https://api.razorpay.com/v1/orders', [
                'amount' => $amountPaise,
                'currency' => $payment->currency,
                'receipt' => $payment->reference,
                'notes' => ['payment_id' => $payment->id],
            ]);

        $order = $response->json();
        $payment->update(['gateway_order_id' => $order['id'] ?? null]);

        return [
            'type' => 'razorpay',
            'payload' => [
                'key' => $this->keyId(),
                'order_id' => $order['id'] ?? null,
                'amount' => $amountPaise,
                'currency' => $payment->currency,
                'name' => (string) setting('site_name', config('app.name')),
                'reference' => $payment->reference,
                'prefill' => ['name' => $payment->user?->name, 'email' => $payment->user?->email],
            ],
        ];
    }

    public function verifyReturn(Request $request): ?array
    {
        $orderId = (string) $request->input('razorpay_order_id');
        $paymentId = (string) $request->input('razorpay_payment_id');
        $signature = (string) $request->input('razorpay_signature');

        if (! $orderId || ! $paymentId || ! $signature) {
            return null;
        }

        $expected = hash_hmac('sha256', $orderId.'|'.$paymentId, $this->secret());

        if (! hash_equals($expected, $signature)) {
            return null;
        }

        $reference = (string) $request->input('reference', '');

        return [
            'reference' => $reference,
            'status' => 'paid',
            'gateway_payment_id' => $paymentId,
        ];
    }

    public function parseWebhook(Request $request): ?array
    {
        $payload = $request->getContent();
        $signature = (string) $request->header('X-Razorpay-Signature', '');
        $secret = (string) config('payments.gateways.razorpay.webhook_secret');

        $expected = hash_hmac('sha256', $payload, $secret);
        $valid = $secret !== '' && hash_equals($expected, $signature);

        $data = json_decode($payload, true) ?: [];
        $event = (string) ($data['event'] ?? 'unknown');
        $entity = $data['payload']['payment']['entity'] ?? [];

        $statusMap = [
            'payment.captured' => 'paid',
            'payment.failed' => 'failed',
            'refund.processed' => 'refunded',
        ];

        return [
            'reference' => $entity['notes']['receipt'] ?? ($entity['receipt'] ?? null),
            'gateway_payment_id' => $entity['id'] ?? null,
            'status' => $statusMap[$event] ?? 'processing',
            'event' => $event,
            'idempotency_key' => 'razorpay:'.($entity['id'] ?? $event).':'.$event,
            'signature_valid' => $valid,
        ];
    }
}
