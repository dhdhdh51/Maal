<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\CategoryAccessPlan;
use App\Services\Payments\CheckoutService;
use App\Services\Payments\PaymentGatewayManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function __construct(
        protected CheckoutService $checkout,
        protected PaymentGatewayManager $gateways,
    ) {}

    /**
     * Show the unlock page: plans for this category + bundles covering it.
     */
    public function show(Category $category): View
    {
        $plans = CategoryAccessPlan::query()
            ->where('is_active', true)
            ->where(function ($q) use ($category) {
                $q->where('category_id', $category->id)
                    ->orWhere(function ($q2) use ($category) {
                        $q2->where('type', 'bundle')
                            ->whereJsonContains('bundle_category_ids', $category->id);
                    });
            })
            ->orderBy('sort_order')
            ->get();

        return view('checkout.unlock', [
            'category' => $category,
            'plans' => $plans,
            'gateways' => $this->gateways->enabled(),
        ]);
    }

    public function store(Request $request): View|RedirectResponse
    {
        $data = $request->validate([
            'plan_id' => ['required', 'exists:category_access_plans,id'],
            'gateway' => ['nullable', 'string'],
        ]);

        $plan = CategoryAccessPlan::findOrFail($data['plan_id']);
        $gatewayKey = $data['gateway'] ?? (string) config('payments.default');

        if (! $this->gateways->isEnabled($gatewayKey)) {
            return back()->withErrors(['gateway' => 'That payment method is unavailable.']);
        }

        $payment = $this->checkout->createPayment($request->user(), $plan, $gatewayKey, [
            'utm_source' => $request->query('utm_source'),
            'utm_medium' => $request->query('utm_medium'),
            'utm_campaign' => $request->query('utm_campaign'),
        ]);

        $order = $this->checkout->startOrder($payment);

        return match ($order['type']) {
            'form' => view('checkout.redirect', ['order' => $order]),       // PayU auto-submit
            'redirect' => redirect()->away($order['url']),                   // Stripe
            'razorpay' => view('checkout.razorpay', ['order' => $order, 'payment' => $payment]),
            'manual' => redirect()->route('payment.pending', ['ref' => $payment->reference]),
            'free' => redirect()->route('payment.success', ['ref' => $payment->reference]),
            default => redirect()->route('payment.failed'),
        };
    }
}
