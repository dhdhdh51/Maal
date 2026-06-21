<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\CategoryAccessPlan;
use App\Models\Coupon;
use App\Models\Referral;
use App\Models\User;
use App\Services\Offers\CouponService;
use App\Services\Offers\WalletService;
use App\Services\Payments\CheckoutService;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OffersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(SettingsSeeder::class);
    }

    protected function plan(float $price = 500): CategoryAccessPlan
    {
        $cat = Category::create(['name' => 'C', 'slug' => 'c-'.uniqid(), 'access_type' => 'paid', 'price' => $price]);

        return CategoryAccessPlan::create([
            'category_id' => $cat->id, 'name' => 'P', 'type' => 'monthly',
            'price' => $price, 'currency' => 'INR', 'validity_days' => 30, 'is_active' => true,
        ]);
    }

    public function test_percentage_coupon_with_cap(): void
    {
        $plan = $this->plan(1000);
        $coupon = Coupon::create([
            'code' => 'SAVE50', 'type' => 'percentage', 'value' => 50,
            'max_discount' => 200, 'is_active' => true,
        ]);
        $user = User::factory()->create();

        $result = app(CouponService::class)->validate('save50', $user, $plan->category_id, 1000);

        $this->assertTrue($result['valid']);
        $this->assertSame(200.0, $result['discount']); // capped
    }

    public function test_coupon_per_user_limit_and_window(): void
    {
        $plan = $this->plan();
        $user = User::factory()->create();

        $expired = Coupon::create(['code' => 'OLD', 'type' => 'flat', 'value' => 50, 'is_active' => true, 'expires_at' => now()->subDay()]);
        $this->assertFalse(app(CouponService::class)->validate('OLD', $user, $plan->category_id, 500)['valid']);

        $cat = $plan->category;
        $otherCat = Category::create(['name' => 'Other', 'slug' => 'other-'.uniqid(), 'access_type' => 'paid', 'price' => 100]);
        $scoped = Coupon::create(['code' => 'OTHER', 'type' => 'flat', 'value' => 50, 'is_active' => true, 'category_id' => $otherCat->id]);
        $this->assertFalse(app(CouponService::class)->validate('OTHER', $user, $plan->category_id, 500)['valid']);
    }

    public function test_free_access_coupon_zero_total_grants_access(): void
    {
        config()->set('payments.gateways.manual.enabled', true);
        $plan = $this->plan(300);
        $coupon = Coupon::create(['code' => 'FREE', 'type' => 'free_access', 'value' => 0, 'is_active' => true]);
        $user = User::factory()->create();

        $this->actingAs($user)->post('/checkout', [
            'plan_id' => $plan->id, 'gateway' => 'manual', 'coupon_code' => 'FREE',
        ])->assertRedirectContains('/payment/success');

        // Zero-total order auto-completes and grants access.
        $this->assertTrue($user->fresh()->hasCategoryAccess($plan->category_id));
        $this->assertDatabaseHas('coupon_redemptions', ['coupon_id' => $coupon->id, 'user_id' => $user->id]);
        $this->assertSame(1, $coupon->fresh()->used_count);
    }

    public function test_wallet_credit_and_debit(): void
    {
        $user = User::factory()->create(['wallet_balance' => 0]);
        $wallet = app(WalletService::class);

        $wallet->credit($user, 150, 'test');
        $this->assertSame('150.00', (string) $user->fresh()->wallet_balance);

        $debited = $wallet->debit($user->fresh(), 200, 'test'); // capped at balance
        $this->assertSame(150.0, $debited);
        $this->assertSame('0.00', (string) $user->fresh()->wallet_balance);
    }

    public function test_wallet_applied_at_checkout_reduces_total_and_is_debited(): void
    {
        config()->set('payments.gateways.manual.enabled', true);
        $plan = $this->plan(300);
        $user = User::factory()->create(['wallet_balance' => 100]);

        $payment = app(CheckoutService::class)->createPayment($user, $plan, 'manual', ['apply_wallet' => true]);
        $this->assertSame('200.00', (string) $payment->total); // 300 - 100 wallet
        $this->assertSame(100.0, (float) $payment->meta['wallet_applied']);

        app(CheckoutService::class)->markPaid($payment);
        $this->assertSame('0.00', (string) $user->fresh()->wallet_balance); // debited
    }

    public function test_referral_reward_on_first_paid_order(): void
    {
        $referrer = User::factory()->create(['wallet_balance' => 0]);
        $referred = User::factory()->create(['referred_by' => $referrer->id]);

        Referral::create([
            'referrer_id' => $referrer->id, 'referred_user_id' => $referred->id,
            'reward_type' => 'wallet_credit', 'reward_value' => 75, 'status' => 'pending',
        ]);

        $plan = $this->plan(300);
        $checkout = app(CheckoutService::class);
        $payment = $checkout->createPayment($referred, $plan, 'manual');
        $checkout->markPaid($payment);

        $this->assertSame('75.00', (string) $referrer->fresh()->wallet_balance);
        $this->assertSame('rewarded', Referral::first()->status);
    }

    public function test_coupon_validate_endpoint(): void
    {
        $plan = $this->plan(1000);
        Coupon::create(['code' => 'TEN', 'type' => 'percentage', 'value' => 10, 'is_active' => true]);
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/checkout/coupon', ['plan_id' => $plan->id, 'code' => 'TEN'])
            ->assertOk()
            ->assertJsonPath('data.discount', 100)
            ->assertJsonPath('data.total', 900);
    }
}
