<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\CategoryAccessPlan;
use App\Models\Report;
use App\Models\User;
use App\Models\Video;
use App\Services\Payments\CheckoutService;
use App\Support\Permissions;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(SettingsSeeder::class);
    }

    protected function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Permissions::ROLE_ADMIN);

        return $user;
    }

    public function test_admin_dashboard_loads_for_admin(): void
    {
        $this->actingAs($this->admin())->get('/admin/dashboard')->assertOk()->assertSee('Dashboard');
    }

    public function test_non_admin_is_forbidden(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Permissions::ROLE_USER);

        $this->actingAs($user)->get('/admin/dashboard')->assertForbidden();
    }

    public function test_admin_can_create_category(): void
    {
        $this->actingAs($this->admin())->post('/admin/categories', [
            'name' => 'New Cat', 'access_type' => 'paid', 'price' => 199, 'currency' => 'INR', 'is_active' => '1',
        ])->assertRedirect();

        $this->assertDatabaseHas('categories', ['name' => 'New Cat', 'slug' => 'new-cat']);
    }

    public function test_admin_can_create_coupon(): void
    {
        $this->actingAs($this->admin())->post('/admin/coupons', [
            'code' => 'WELCOME', 'type' => 'percentage', 'value' => 10, 'per_user_limit' => 1, 'is_active' => '1',
        ])->assertRedirect();

        $this->assertDatabaseHas('coupons', ['code' => 'WELCOME']);
    }

    public function test_disable_video_instantly_blocks_playback(): void
    {
        $cat = Category::create(['name' => 'Free', 'slug' => 'free', 'access_type' => 'free', 'is_active' => true]);
        $video = Video::create([
            'category_id' => $cat->id, 'title' => 'V', 'slug' => 'v',
            'processing_status' => 'ready', 'is_published' => true, 'published_at' => now(),
        ]);

        $this->actingAs($this->admin())->post(route('admin.videos.disable', $video))->assertRedirect();

        $this->assertTrue($video->fresh()->is_disabled);
        // Watch page now 404s.
        $this->get("/watch/{$video->slug}")->assertNotFound();
    }

    public function test_admin_grant_access_to_user(): void
    {
        $cat = Category::create(['name' => 'Paid', 'slug' => 'paid', 'access_type' => 'paid', 'price' => 99, 'is_active' => true]);
        $target = User::factory()->create();

        $this->actingAs($this->admin())->post("/admin/users/{$target->id}/grant-access", [
            'category_id' => $cat->id, 'days' => 30,
        ])->assertRedirect();

        $this->assertTrue($target->fresh()->hasCategoryAccess($cat->id));
    }

    public function test_admin_suspend_user_blocks_them(): void
    {
        $target = User::factory()->create();

        $this->actingAs($this->admin())->put("/admin/users/{$target->id}", [
            'status' => 'suspended', 'roles' => [Permissions::ROLE_USER],
        ])->assertRedirect();

        $this->assertSame('suspended', $target->fresh()->status);
    }

    public function test_settings_update(): void
    {
        $this->actingAs($this->admin())->put('/admin/settings', [
            'settings' => ['site_name' => 'Renamed', 'default_preview_seconds' => '30'],
        ])->assertRedirect();

        $this->assertSame('Renamed', setting('site_name'));
    }

    public function test_report_resolution(): void
    {
        $cat = Category::create(['name' => 'C', 'slug' => 'c', 'access_type' => 'free', 'is_active' => true]);
        $video = Video::create(['category_id' => $cat->id, 'title' => 'V', 'slug' => 'v2', 'processing_status' => 'ready', 'is_published' => true]);
        $report = $video->morphMany(Report::class, 'reportable')->create(['reason' => 'copyright', 'priority' => 'high', 'status' => 'open']);

        $this->actingAs($this->admin())->put("/admin/reports/{$report->id}", ['status' => 'resolved'])->assertRedirect();

        $this->assertSame('resolved', $report->fresh()->status);
    }

    public function test_manual_payment_approval(): void
    {
        config()->set('payments.gateways.manual.enabled', true);
        $cat = Category::create(['name' => 'P', 'slug' => 'p', 'access_type' => 'paid', 'price' => 50, 'is_active' => true]);
        $plan = CategoryAccessPlan::create(['category_id' => $cat->id, 'name' => 'M', 'type' => 'monthly', 'price' => 50, 'currency' => 'INR', 'validity_days' => 30, 'is_active' => true]);
        $buyer = User::factory()->create();
        $payment = app(CheckoutService::class)->createPayment($buyer, $plan, 'manual');

        $this->actingAs($this->admin())->post("/admin/payments/{$payment->id}/approve")->assertRedirect();

        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertTrue($buyer->fresh()->hasCategoryAccess($cat->id));
    }
}
