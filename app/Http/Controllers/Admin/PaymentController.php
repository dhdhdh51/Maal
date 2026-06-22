<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\Payments\CheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function __construct(protected CheckoutService $checkout) {}

    public function index(Request $request): View
    {
        $payments = Payment::query()
            ->with(['user', 'category'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->gateway, fn ($q) => $q->where('gateway', $request->gateway))
            ->latest()->paginate(30)->withQueryString();

        return view('admin.payments.index', compact('payments'));
    }

    public function approve(Payment $payment): RedirectResponse
    {
        abort_unless($payment->gateway === 'manual', 422);
        $this->checkout->markPaid($payment);

        return back()->with('status', 'Payment approved and access granted.');
    }

    public function refund(Payment $payment): RedirectResponse
    {
        abort_unless($payment->status === 'paid', 422);
        $this->checkout->markRefunded($payment);

        return back()->with('status', 'Payment refunded and access revoked.');
    }
}
