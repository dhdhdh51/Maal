<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\CategoryAccessPlan;
use App\Services\Offers\CouponService;
use App\Services\Offers\WalletService;
use App\Services\Payments\CheckoutService;
use App\Services\Payments\PaymentGatewayManager;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function __construct(
        protected CheckoutService $checkout,
        protected PaymentGatewayManager $gateways,
        protected CouponService $coupons,
        protected WalletService $wallet,
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
            'coupon_code' => ['nullable', 'string', 'max:64'],
            'apply_wallet' => ['nullable', 'boolean'],
        ]);

        $plan = CategoryAccessPlan::findOrFail($data['plan_id']);
        $gatewayKey = $data['gateway'] ?? (string) config('payments.default');

        if (! $this->gateways->isEnabled($gatewayKey)) {
            return back()->withErrors(['gateway' => 'That payment method is unavailable.']);
        }

        $opts = [
            'utm_source' => $request->query('utm_source'),
            'utm_medium' => $request->query('utm_medium'),
            'utm_campaign' => $request->query('utm_campaign'),
            'apply_wallet' => $request->boolean('apply_wallet'),
        ];

        // Apply a coupon if supplied.
        if (! empty($data['coupon_code'])) {
            $result = $this->coupons->validate(
                $data['coupon_code'], $request->user(), $plan->category_id, (float) $plan->price
            );

            if (! $result['valid']) {
                return back()->withErrors(['coupon_code' => $result['reason']])->withInput();
            }

            $opts['discount'] = $result['discount'];
            $opts['coupon_id'] = $result['coupon']->id;
        }

        $payment = $this->checkout->createPayment($request->user(), $plan, $gatewayKey, $opts);

        $order = $this->checkout->startOrder($payment);

        return match ($order['type']) {
            'form' => view('checkout.redirect', ['order' => $order]),
            'redirect' => redirect()->away($order['url']),
            'razorpay' => view('checkout.razorpay', ['order' => $order, 'payment' => $payment]),
            'manual' => redirect()->route('payment.pending', ['ref' => $payment->reference]),
            'free' => redirect()->route('payment.success', ['ref' => $payment->reference]),
            default => redirect()->route('payment.failed'),
        };
    }

    /**
     * AJAX: preview a coupon's discount for a plan.
     */
    public function validateCoupon(Request $request): JsonResponse
    {
        $data = $request->validate([
            'plan_id' => ['required', 'exists:category_access_plans,id'],
            'code' => ['required', 'string', 'max:64'],
        ]);

        $plan = CategoryAccessPlan::findOrFail($data['plan_id']);
        $result = $this->coupons->validate($data['code'], $request->user(), $plan->category_id, (float) $plan->price);

        if (! $result['valid']) {
            return ApiResponse::error($result['reason'], 422);
        }

        return ApiResponse::success([
            'discount' => $result['discount'],
            'total' => max(0, round((float) $plan->price - $result['discount'], 2)),
            'currency' => $plan->currency,
        ], 'Coupon applied.');
    }
}
