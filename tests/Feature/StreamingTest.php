<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use App\Models\UserSession;
use App\Models\Video;
use App\Models\VideoPreview;
use App\Services\AccessService;
use App\Services\Streaming\PlaybackService;
use App\Services\Streaming\StreamTokenService;
use App\Support\Permissions;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StreamingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(SettingsSeeder::class);
        config()->set('streaming.media_disk', 'local');
        Storage::fake('local');
    }

    protected function readyVideo(array $attrs = []): Video
    {
        return Video::create(array_merge([
            'title' => 'V', 'slug' => 'v-'.uniqid(),
            'processing_status' => 'ready', 'is_published' => true,
            'hls_master_path' => 'hls/1/master.m3u8',
            'available_qualities' => ['360p', '720p'], 'duration' => 120,
        ], $attrs));
    }

    public function test_token_roundtrip_and_expiry(): void
    {
        $svc = app(StreamTokenService::class);
        $token = $svc->issue('hls/5', 5, 'full', 3600);

        $parsed = $svc->parse($token);
        $this->assertSame('hls/5', $parsed['b']);
        $this->assertSame(5, $parsed['v']);

        $expired = $svc->issue('hls/5', 5, 'full', -10);
        $this->assertNull($svc->parse($expired));
        $this->assertNull($svc->parse('garbage'));
    }

    public function test_full_payload_for_free_category(): void
    {
        $cat = Category::create(['name' => 'Free', 'slug' => 'free', 'access_type' => 'free']);
        $video = $this->readyVideo(['category_id' => $cat->id]);

        $payload = app(PlaybackService::class)->payload($video, null);

        $this->assertSame('full', $payload['mode']);
        $this->assertStringContainsString('/stream/hls/', $payload['master_url']);
        $this->assertContains('auto', $payload['qualities']);
    }

    public function test_preview_payload_for_locked_paid_category(): void
    {
        $cat = Category::create(['name' => 'Paid', 'slug' => 'paid', 'access_type' => 'paid', 'price' => 99]);
        $video = $this->readyVideo(['category_id' => $cat->id, 'is_locked' => true]);
        VideoPreview::create(['video_id' => $video->id, 'hls_path' => 'previews/'.$video->id.'/hls/preview.m3u8', 'duration_seconds' => 20, 'is_ready' => true]);

        $user = User::factory()->create();
        $payload = app(PlaybackService::class)->payload($video->fresh('preview'), $user);

        $this->assertSame('preview', $payload['mode']);
        $this->assertSame(20, $payload['preview_seconds']);
        $this->assertNotNull($payload['unlock']['checkout_url']);
    }

    public function test_access_service_rules(): void
    {
        $access = app(AccessService::class);
        $free = Category::create(['name' => 'F', 'slug' => 'f', 'access_type' => 'free']);
        $paid = Category::create(['name' => 'P', 'slug' => 'p', 'access_type' => 'paid', 'price' => 50]);

        $freeVideo = $this->readyVideo(['category_id' => $free->id]);
        $paidVideo = $this->readyVideo(['category_id' => $paid->id, 'is_locked' => true]);

        $user = User::factory()->create();

        $this->assertTrue($access->canWatchFull($user, $freeVideo));
        $this->assertFalse($access->canWatchFull($user, $paidVideo));

        $admin = User::factory()->create();
        $admin->assignRole(Permissions::ROLE_ADMIN);
        $this->assertTrue($access->canWatchFull($admin, $paidVideo)); // staff QA access
    }

    public function test_watermark_included_when_enabled(): void
    {
        $cat = Category::create(['name' => 'Free', 'slug' => 'free', 'access_type' => 'free']);
        $video = $this->readyVideo(['category_id' => $cat->id, 'watermark_enabled' => true]);
        $user = User::factory()->create(['email' => 'wm@example.com', 'phone' => '+919812345678']);

        $payload = app(PlaybackService::class)->payload($video, $user, 'sess1234abcd');

        $this->assertNotNull($payload['watermark']);
        $this->assertStringContainsString('wm@example.com', $payload['watermark']['text']);
    }

    public function test_hls_route_streams_local_file_with_valid_token(): void
    {
        $video = $this->readyVideo();
        Storage::disk('local')->put('hls/'.$video->id.'/master.m3u8', "#EXTM3U\n#EXT-X-VERSION:3\n");

        $token = app(StreamTokenService::class)->issue('hls/'.$video->id, $video->id, 'full', 3600);

        $res = $this->get("/stream/hls/{$token}/master.m3u8");
        $res->assertOk();
        $this->assertSame('application/vnd.apple.mpegurl', $res->headers->get('Content-Type'));
        $this->assertStringContainsString('#EXTM3U', $res->streamedContent());
    }

    public function test_hls_route_rejects_invalid_token_and_traversal(): void
    {
        $this->get('/stream/hls/badtoken/master.m3u8')->assertForbidden();

        $token = app(StreamTokenService::class)->issue('hls/1', 1, 'full', 3600);
        $this->get("/stream/hls/{$token}/..%2f..%2fsecret")->assertStatus(400);
    }

    public function test_disabled_video_blocked_even_with_token(): void
    {
        $video = $this->readyVideo(['is_disabled' => true]);
        Storage::disk('local')->put('hls/'.$video->id.'/master.m3u8', '#EXTM3U');
        $token = app(StreamTokenService::class)->issue('hls/'.$video->id, $video->id, 'full', 3600);

        $this->get("/stream/hls/{$token}/master.m3u8")->assertForbidden();
    }

    public function test_heartbeat_blocks_concurrent_playback(): void
    {
        $user = User::factory()->create();
        $video = $this->readyVideo();

        // An active playing session on another device.
        UserSession::create([
            'user_id' => $user->id, 'is_playing' => true,
            'last_activity_at' => now(), 'token_id' => 'other',
        ]);

        $this->actingAs($user)
            ->postJson("/watch/{$video->slug}/heartbeat")
            ->assertStatus(409)
            ->assertJsonPath('errors.concurrent', true);
    }
}
