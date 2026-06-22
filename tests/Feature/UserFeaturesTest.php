<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\CategoryAccessPlan;
use App\Models\Payment;
use App\Models\PreviewAnalytic;
use App\Models\Setting;
use App\Models\User;
use App\Models\Video;
use App\Models\WatchHistory;
use App\Services\Payments\CheckoutService;
use Database\Seeders\DemoContentSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserFeaturesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(SettingsSeeder::class);
        Storage::fake('public');
    }

    protected function freeVideo(): Video
    {
        $cat = Category::create(['name' => 'Free', 'slug' => 'free-'.uniqid(), 'access_type' => 'free', 'is_active' => true]);

        return Video::create([
            'category_id' => $cat->id, 'title' => 'Clip '.uniqid(), 'slug' => 'clip-'.uniqid(),
            'processing_status' => 'ready', 'is_published' => true, 'duration' => 300,
            'available_qualities' => ['360p'], 'published_at' => now(),
        ]);
    }

    public function test_home_page_renders(): void
    {
        $this->seed(DemoContentSeeder::class);
        $this->get('/')->assertOk()->assertSee(setting('site_name', 'Maal'));
    }

    public function test_category_lists_videos_with_lock_state(): void
    {
        $cat = Category::create(['name' => 'Paid', 'slug' => 'paid', 'access_type' => 'paid', 'price' => 99, 'is_active' => true]);
        Video::create([
            'category_id' => $cat->id, 'title' => 'Locked', 'slug' => 'locked',
            'processing_status' => 'ready', 'is_published' => true, 'is_locked' => true, 'published_at' => now(),
        ]);

        Setting::where('key', 'age_gate_enabled')->update(['value' => '0']);
        Cache::forget(Setting::CACHE_KEY);

        $this->get('/category/paid')->assertOk()->assertSee('Locked');
    }

    public function test_favorite_toggle(): void
    {
        $user = User::factory()->create();
        $video = $this->freeVideo();

        $this->actingAs($user)->postJson(route('favorites.toggle', $video))
            ->assertOk()->assertJsonPath('data.favorited', true);
        $this->assertDatabaseHas('favorites', ['user_id' => $user->id, 'video_id' => $video->id]);

        $this->actingAs($user)->postJson(route('favorites.toggle', $video))
            ->assertOk()->assertJsonPath('data.favorited', false);
    }

    public function test_watch_progress_upserts_history(): void
    {
        $user = User::factory()->create();
        $video = $this->freeVideo();

        $this->actingAs($user)->postJson(route('watch.progress', $video), ['position' => 150, 'duration' => 300])
            ->assertOk()->assertJsonPath('data.percent', 50);

        $this->assertDatabaseHas('watch_history', ['user_id' => $user->id, 'video_id' => $video->id, 'percent' => 50]);

        // Upsert (not duplicate).
        $this->actingAs($user)->postJson(route('watch.progress', $video), ['position' => 300, 'duration' => 300]);
        $this->assertSame(1, WatchHistory::count());
        $this->assertTrue(WatchHistory::first()->completed);
    }

    public function test_support_ticket_creation(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('support.store'), [
            'category' => 'payment', 'subject' => 'Help', 'message' => 'I need help with a payment.',
        ])->assertRedirect();

        $this->assertDatabaseHas('support_tickets', ['user_id' => $user->id, 'subject' => 'Help']);
        $this->assertDatabaseHas('support_ticket_replies', ['is_staff' => false]);
    }

    public function test_report_submission(): void
    {
        $video = $this->freeVideo();

        $this->post(route('report.store'), [
            'type' => 'video', 'id' => $video->id, 'reason' => 'copyright', 'details' => 'Mine',
        ])->assertRedirect();

        $this->assertDatabaseHas('reports', [
            'reportable_type' => Video::class, 'reportable_id' => $video->id,
            'reason' => 'copyright', 'priority' => 'high',
        ]);
    }

    public function test_notifications_mark_all_read(): void
    {
        $user = User::factory()->create();
        $user->notifications()->createMany([
            ['type' => 'x', 'title' => 'A'],
            ['type' => 'x', 'title' => 'B'],
        ]);

        $this->actingAs($user)->postJson(route('notifications.read_all'))->assertOk();
        $this->assertSame(0, $user->notifications()->whereNull('read_at')->count());
    }

    public function test_newsletter_subscribe(): void
    {
        $this->postJson(route('newsletter.subscribe'), ['email' => 'sub@example.com'])->assertOk();
        $this->assertDatabaseHas('newsletter_subscribers', ['email' => 'sub@example.com']);
    }

    public function test_preview_event_records_and_conversion_marks(): void
    {
        $cat = Category::create(['name' => 'Paid', 'slug' => 'paidx', 'access_type' => 'paid', 'price' => 50, 'is_active' => true]);
        $video = Video::create([
            'category_id' => $cat->id, 'title' => 'Pv', 'slug' => 'pv',
            'processing_status' => 'ready', 'is_published' => true, 'is_locked' => true, 'published_at' => now(),
        ]);
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(route('watch.preview_event', $video), [
            'completed' => true, 'percent' => 100, 'clicked_unlock' => true,
        ])->assertOk();

        $this->assertDatabaseHas('preview_analytics', ['video_id' => $video->id, 'user_id' => $user->id, 'clicked_unlock' => true]);

        // A completed payment in the same category flags conversion.
        $plan = CategoryAccessPlan::create([
            'category_id' => $cat->id, 'name' => 'P', 'type' => 'monthly', 'price' => 50, 'currency' => 'INR', 'validity_days' => 30, 'is_active' => true,
        ]);
        $payment = app(CheckoutService::class)->createPayment($user, $plan, 'manual');
        app(CheckoutService::class)->markPaid($payment);

        $this->assertTrue(PreviewAnalytic::where('video_id', $video->id)->first()->converted);
    }
}
