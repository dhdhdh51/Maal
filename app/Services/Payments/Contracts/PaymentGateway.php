<?php

namespace App\Services\Payments\Contracts;

use App\Models\Payment;
use Illuminate\Http\Request;

interface PaymentGateway
{
    /**
     * The gateway key (matches config/payments.php gateways.<key>).
     */
    public function key(): string;

    public function label(): string;

    public function isEnabled(): bool;

    /**
     * Create the gateway order/session and return the data the frontend needs
     * to proceed.
     *
     * @return array{type:string, url?:string, method?:string, fields?:array<string,string>, payload?:array<string,mixed>}
     */
    public function createOrder(Payment $payment): array;

    /**
     * Verify a synchronous return/callback from the gateway.
     *
     * @return array{reference:string, status:string, gateway_payment_id:?string}|null
     */
    public function verifyReturn(Request $request): ?array;

    /**
     * Parse + verify an asynchronous webhook.
     *
     * @return array{reference:?string, gateway_payment_id:?string, status:string, event:string, idempotency_key:string, signature_valid:bool}|null
     */
    public function parseWebhook(Request $request): ?array;
}
