<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\CategoryAccessPlan;
use App\Models\PaymentTransaction;
use App\Models\User;
use App\Services\Payments\AccessGrantService;
use App\Services\Payments\CheckoutService;
use App\Services\Payments\Gateways\PayUGateway;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(SettingsSeeder::class);
    }

    protected function plan(array $attrs = []): CategoryAccessPlan
    {
        $cat = Category::create(['name' => 'Prem', 'slug' => 'prem-'.uniqid(), 'access_type' => 'paid', 'price' => 299]);

        return CategoryAccessPlan::create(array_merge([
            'category_id' => $cat->id, 'name' => 'Monthly', 'type' => 'monthly',
            'price' => 299, 'currency' => 'INR', 'validity_days' => 30, 'is_active' => true,
        ], $attrs));
    }

    public function test_payu_request_hash_is_deterministic(): void
    {
        config()->set('payments.gateways.payu.merchant_key', 'KEY');
        config()->set('payments.gateways.payu.merchant_salt', 'SALT');

        $gw = new PayUGateway;
        $fields = [
            'key' => 'KEY', 'txnid' => 'TXN1', 'amount' => '299.00',
            'productinfo' => 'Access #1', 'firstname' => 'Ann', 'email' => 'a@b.com',
        ];

        $hash = $gw->requestHash($fields);
        $this->assertSame(128, strlen($hash)); // sha512 hex
        $this->assertSame($hash, $gw->requestHash($fields)); // stable
    }

    public function test_checkout_computes_totals_and_creates_payment(): void
    {
        $user = User::factory()->create();
        $plan = $this->plan(['price' => 500]);

        $payment = app(CheckoutService::class)->createPayment($user, $plan, 'payu');

        $this->assertSame('500.00', (string) $payment->amount);
        $this->assertSame('500.00', (string) $payment->total);
        $this->assertSame('pending', $payment->status);
        $this->assertStringStartsWith('MAAL', $payment->reference);
    }

    public function test_mark_paid_grants_access_and_is_idempotent(): void
    {
        $user = User::factory()->create(['is_premium' => false]);
        $plan = $this->plan();
        $checkout = app(CheckoutService::class);
        $payment = $checkout->createPayment($user, $plan, 'payu');

        $checkout->markPaid($payment, 'pay_abc');
        $checkout->markPaid($payment, 'pay_abc'); // duplicate

        $payment->refresh();
        $this->assertSame('paid', $payment->status);
        $this->assertNotNull($payment->invoice_number);

        $this->assertTrue($user->fresh()->hasCategoryAccess($plan->category_id));
        $this->assertTrue($user->fresh()->is_premium);
        $this->assertSame(1, $user->categoryAccess()->count()); // not duplicated
    }

    public function test_access_extends_then_expires(): void
    {
        $user = User::factory()->create();
        $plan = $this->plan(['validity_days' => 30]);
        $access = app(AccessGrantService::class);

        $first = $access->grant($user, $plan->category_id, 30);
        $firstExpiry = $first->expires_at->copy();

        // Re-grant extends from current expiry.
        $access->grant($user, $plan->category_id, 30);
        $this->assertTrue($user->categoryAccess()->first()->expires_at->gt($firstExpiry));

        // Force-expire.
        $user->categoryAccess()->update(['expires_at' => now()->subDay()]);
        $this->assertSame(1, $access->expireDue());
        $this->assertSame('expired', $user->categoryAccess()->first()->status);
    }

    public function test_refund_revokes_access(): void
    {
        $user = User::factory()->create();
        $plan = $this->plan();
        $checkout = app(CheckoutService::class);
        $payment = $checkout->createPayment($user, $plan, 'payu');
        $checkout->markPaid($payment, 'pay_x');

        $this->assertTrue($user->fresh()->hasCategoryAccess($plan->category_id));

        $checkout->markRefunded($payment->fresh());

        $this->assertFalse($user->fresh()->hasCategoryAccess($plan->category_id));
        $this->assertSame('refunded', $payment->fresh()->status);
    }

    public function test_manual_checkout_flow(): void
    {
        config()->set('payments.gateways.manual.enabled', true);
        $user = User::factory()->create();
        $plan = $this->plan();

        $this->actingAs($user)
            ->post('/checkout', ['plan_id' => $plan->id, 'gateway' => 'manual'])
            ->assertRedirectContains('/payment/pending');

        $this->assertDatabaseHas('payments', ['user_id' => $user->id, 'gateway' => 'manual', 'status' => 'pending']);
    }

    public function test_razorpay_webhook_is_verified_idempotent_and_grants_access(): void
    {
        config()->set('payments.gateways.razorpay.enabled', true);
        config()->set('payments.gateways.razorpay.webhook_secret', 'whsec');

        $user = User::factory()->create();
        $plan = $this->plan();
        $payment = app(CheckoutService::class)->createPayment($user, $plan, 'razorpay');

        $payload = json_encode([
            'event' => 'payment.captured',
            'payload' => ['payment' => ['entity' => [
                'id' => 'pay_777', 'notes' => ['receipt' => $payment->reference],
            ]]],
        ]);
        $sig = hash_hmac('sha256', $payload, 'whsec');

        $headers = ['HTTP_X-Razorpay-Signature' => $sig, 'CONTENT_TYPE' => 'application/json'];

        $this->call('POST', '/webhooks/razorpay', [], [], [], $headers, $payload)->assertOk();
        // Duplicate delivery acknowledged but not reprocessed.
        $this->call('POST', '/webhooks/razorpay', [], [], [], $headers, $payload)
            ->assertOk()->assertJsonPath('duplicate', true);

        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertTrue($user->fresh()->hasCategoryAccess($plan->category_id));
        $this->assertSame(1, PaymentTransaction::count());
    }

    public function test_webhook_rejects_bad_signature(): void
    {
        config()->set('payments.gateways.razorpay.enabled', true);
        config()->set('payments.gateways.razorpay.webhook_secret', 'whsec');

        $payload = json_encode(['event' => 'payment.captured', 'payload' => ['payment' => ['entity' => ['id' => 'x']]]]);

        $this->call('POST', '/webhooks/razorpay', [], [], [], [
            'HTTP_X-Razorpay-Signature' => 'wrong', 'CONTENT_TYPE' => 'application/json',
        ], $payload)->assertStatus(400);
    }

    public function test_payu_return_marks_paid(): void
    {
        config()->set('payments.gateways.payu.merchant_key', 'KEY');
        config()->set('payments.gateways.payu.merchant_salt', 'SALT');
        config()->set('payments.gateways.payu.enabled', true);

        $user = User::factory()->create();
        $plan = $this->plan();
        $payment = app(CheckoutService::class)->createPayment($user, $plan, 'payu');

        $gw = new PayUGateway;
        $resp = [
            'status' => 'success', 'email' => $user->email, 'firstname' => $user->name,
            'productinfo' => 'Access #'.$payment->id, 'amount' => number_format($payment->total, 2, '.', ''),
            'txnid' => $payment->reference, 'key' => 'KEY', 'mihpayid' => 'mih_1',
        ];
        $resp['hash'] = $gw->responseHash($resp);

        $this->post('/payment/return/payu', $resp)->assertRedirectContains('/payment/success');
        $this->assertSame('paid', $payment->fresh()->status);
    }
}
