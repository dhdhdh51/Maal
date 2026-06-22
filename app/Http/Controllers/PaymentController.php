<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\Payments\CheckoutService;
use App\Services\Payments\PaymentGatewayManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function __construct(
        protected CheckoutService $checkout,
        protected PaymentGatewayManager $gateways,
    ) {}

    /**
     * Synchronous gateway return (PayU surl/furl, Razorpay handler).
     */
    public function return(Request $request, string $gateway): RedirectResponse
    {
        $verified = $this->gateways->gateway($gateway)->verifyReturn($request);

        if (! $verified) {
            return redirect()->route('payment.failed');
        }

        $payment = Payment::where('reference', $verified['reference'])->first();

        if (! $payment) {
            return redirect()->route('payment.failed');
        }

        if ($verified['status'] === 'paid') {
            $this->checkout->markPaid($payment, $verified['gateway_payment_id']);

            return redirect()->route('payment.success', ['ref' => $payment->reference]);
        }

        $this->checkout->markFailed($payment);

        return redirect()->route('payment.failed', ['ref' => $payment->reference]);
    }

    public function success(Request $request): View
    {
        $payment = Payment::where('reference', $request->query('ref'))->first();

        return view('payment.success', ['payment' => $payment]);
    }

    public function failed(Request $request): View
    {
        $payment = Payment::where('reference', $request->query('ref'))->first();

        return view('payment.failed', ['payment' => $payment]);
    }

    public function pending(Request $request): View
    {
        $payment = Payment::where('reference', $request->query('ref'))->firstOrFail();

        return view('payment.pending', ['payment' => $payment]);
    }
}
