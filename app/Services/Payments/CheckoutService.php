<?php

namespace App\Services\Payments;

use App\Events\PaymentCompleted;
use App\Models\CategoryAccessPlan;
use App\Models\Payment;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\Notifier;
use App\Services\Offers\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Builds payment orders, finalises paid/failed/refunded states and triggers
 * access grants + notifications. Coupon/referral hooks are invoked here and
 * fleshed out by the offers system.
 */
class CheckoutService
{
    public function __construct(
        protected PaymentGatewayManager $gateways,
        protected AccessGrantService $access,
        protected Notifier $notifier,
        protected WalletService $wallet,
    ) {}

    /**
     * Create a pending Payment for a plan.
     *
     * @param  array<string, mixed>  $opts  discount, coupon_id, apply_wallet, utm_*, referred_by
     */
    public function createPayment(User $user, CategoryAccessPlan $plan, string $gatewayKey, array $opts = []): Payment
    {
        $amount = (float) $plan->price;
        $couponDiscount = (float) ($opts['discount'] ?? 0);
        $taxPercent = (float) setting('tax_percent', 0);
        $taxable = max(0, $amount - $couponDiscount);
        $tax = round($taxable * $taxPercent / 100, 2);
        $preWalletTotal = round($taxable + $tax, 2);

        // Optional wallet application.
        $walletApplied = 0.0;
        if (! empty($opts['apply_wallet'])) {
            $walletApplied = $this->wallet->applicable($user, $preWalletTotal);
        }

        $total = round($preWalletTotal - $walletApplied, 2);

        return Payment::create([
            'reference' => $this->reference(),
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'category_id' => $plan->category_id,
            'coupon_id' => $opts['coupon_id'] ?? null,
            'gateway' => $gatewayKey,
            'amount' => $amount,
            'discount' => $couponDiscount,
            'tax' => $tax,
            'total' => $total,
            'currency' => $plan->currency,
            'status' => 'pending',
            'meta' => ['wallet_applied' => $walletApplied],
            'utm_source' => $opts['utm_source'] ?? null,
            'utm_medium' => $opts['utm_medium'] ?? null,
            'utm_campaign' => $opts['utm_campaign'] ?? null,
            'referred_by' => $opts['referred_by'] ?? $user->referred_by,
        ]);
    }

    /**
     * Create the gateway order for a pending payment.
     *
     * @return array<string, mixed>
     */
    public function startOrder(Payment $payment): array
    {
        // Zero-total (e.g. full coupon / free trial) completes immediately.
        if ((float) $payment->total <= 0) {
            $this->markPaid($payment, 'free');

            return ['type' => 'free', 'reference' => $payment->reference];
        }

        $gateway = $this->gateways->gateway($payment->gateway);
        $order = $gateway->createOrder($payment);

        // Online gateways move to "processing" while awaiting capture; manual
        // bank transfers stay "pending" until an admin approves them.
        if (in_array($order['type'], ['form', 'redirect', 'razorpay'], true)) {
            $payment->update(['status' => 'processing']);
        }

        return $order;
    }

    /**
     * Idempotently finalise a successful payment.
     */
    public function markPaid(Payment $payment, ?string $gatewayPaymentId = null): void
    {
        if ($payment->status === 'paid') {
            return; // idempotent
        }

        DB::transaction(function () use ($payment, $gatewayPaymentId) {
            $payment->forceFill([
                'status' => 'paid',
                'gateway_payment_id' => $gatewayPaymentId ?? $payment->gateway_payment_id,
                'invoice_number' => $payment->invoice_number ?: $this->invoiceNumber($payment),
                'paid_at' => now(),
            ])->save();

            $this->access->grantFromPayment($payment);

            // Coupon redemption + referral reward hooks (offers system).
            event(new PaymentCompleted($payment));
        });

        AuditLogger::log('payment.paid', $payment, "Payment {$payment->reference} captured", actor: $payment->user);

        $this->notifier->sendTemplate('payment_success', $payment->user->email, [
            'name' => $payment->user->name,
            'amount' => $payment->total,
            'currency' => $payment->currency,
            'invoice_number' => $payment->invoice_number,
        ], $payment->user->name);

        $this->notifier->notify(
            $payment->user,
            'payment_success',
            'Payment successful',
            'Your access has been unlocked.',
            url('/dashboard/access'),
            'check',
        );
    }

    public function markFailed(Payment $payment): void
    {
        if ($payment->status === 'paid') {
            return;
        }

        $payment->update(['status' => 'failed']);
        AuditLogger::log('payment.failed', $payment, "Payment {$payment->reference} failed");
    }

    public function markRefunded(Payment $payment): void
    {
        $payment->update(['status' => 'refunded', 'refunded_at' => now()]);
        $this->access->revokeForPayment($payment);
        AuditLogger::log('payment.refunded', $payment, "Payment {$payment->reference} refunded");
    }

    protected function reference(): string
    {
        return 'MAAL'.now()->format('ymd').strtoupper(Str::random(8));
    }

    protected function invoiceNumber(Payment $payment): string
    {
        return 'INV-'.now()->format('Y').'-'.str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT);
    }
}
