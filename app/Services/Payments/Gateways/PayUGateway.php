<?php

namespace App\Services\Payments\Gateways;

use App\Models\Payment;
use App\Services\Payments\Contracts\PaymentGateway;
use Illuminate\Http\Request;

/**
 * PayU (primary gateway). Uses the classic hosted-checkout form handover with
 * an SHA-512 request hash and a reverse-order response hash for verification.
 */
class PayUGateway implements PaymentGateway
{
    public function key(): string
    {
        return 'payu';
    }

    public function label(): string
    {
        return (string) config('payments.gateways.payu.label', 'PayU');
    }

    public function isEnabled(): bool
    {
        return (bool) config('payments.gateways.payu.enabled', false);
    }

    protected function merchantKey(): string
    {
        return (string) config('payments.gateways.payu.merchant_key');
    }

    protected function salt(): string
    {
        return (string) config('payments.gateways.payu.merchant_salt');
    }

    protected function endpoint(): string
    {
        $mode = (string) config('payments.gateways.payu.mode', 'production');

        return (string) config("payments.gateways.payu.endpoints.{$mode}");
    }

    public function createOrder(Payment $payment): array
    {
        $user = $payment->user;
        $amount = number_format((float) $payment->total, 2, '.', '');
        $productinfo = 'Access #'.$payment->id;
        $firstname = $user?->name ?: 'Customer';
        $email = $user?->email ?: 'noreply@example.com';

        $fields = [
            'key' => $this->merchantKey(),
            'txnid' => $payment->reference,
            'amount' => $amount,
            'productinfo' => $productinfo,
            'firstname' => $firstname,
            'email' => $email,
            'phone' => $user?->phone ?: '',
            'surl' => route('payment.return', ['gateway' => 'payu']),
            'furl' => route('payment.return', ['gateway' => 'payu']),
        ];

        $fields['hash'] = $this->requestHash($fields);

        return [
            'type' => 'form',
            'method' => 'POST',
            'url' => $this->endpoint(),
            'fields' => $fields,
        ];
    }

    /**
     * Request hash: sha512(key|txnid|amount|productinfo|firstname|email|udf1..udf5||||||SALT)
     *
     * @param  array<string, string>  $f
     */
    public function requestHash(array $f): string
    {
        $sequence = implode('|', [
            $f['key'], $f['txnid'], $f['amount'], $f['productinfo'],
            $f['firstname'], $f['email'],
            '', '', '', '', '',            // udf1..udf5
            '', '', '', '', '',            // reserved
            $this->salt(),
        ]);

        return strtolower(hash('sha512', $sequence));
    }

    /**
     * Response hash: sha512(SALT|status||||||udf5..udf1|email|firstname|productinfo|amount|txnid|key)
     *
     * @param  array<string, string>  $f
     */
    public function responseHash(array $f): string
    {
        $sequence = implode('|', [
            $this->salt(), $f['status'] ?? '',
            '', '', '', '', '',            // reserved
            '', '', '', '', '',            // udf5..udf1
            $f['email'] ?? '', $f['firstname'] ?? '', $f['productinfo'] ?? '',
            $f['amount'] ?? '', $f['txnid'] ?? '', $f['key'] ?? $this->merchantKey(),
        ]);

        return strtolower(hash('sha512', $sequence));
    }

    public function verifyReturn(Request $request): ?array
    {
        $data = $request->all();

        if (empty($data['txnid']) || empty($data['hash'])) {
            return null;
        }

        $expected = $this->responseHash($data);
        $valid = hash_equals($expected, (string) $data['hash']);

        if (! $valid) {
            return null;
        }

        return [
            'reference' => (string) $data['txnid'],
            'status' => ($data['status'] ?? '') === 'success' ? 'paid' : 'failed',
            'gateway_payment_id' => $data['mihpayid'] ?? null,
        ];
    }

    public function parseWebhook(Request $request): ?array
    {
        // PayU also posts to the surl/furl; treat webhook identically.
        $verified = $this->verifyReturn($request);

        if (! $verified) {
            return null;
        }

        return [
            'reference' => $verified['reference'],
            'gateway_payment_id' => $verified['gateway_payment_id'],
            'status' => $verified['status'],
            'event' => 'payment.'.$verified['status'],
            'idempotency_key' => 'payu:'.$verified['reference'].':'.($verified['gateway_payment_id'] ?? ''),
            'signature_valid' => true,
        ];
    }
}
