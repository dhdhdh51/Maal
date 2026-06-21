<?php

namespace Tests\Feature;

use App\Jobs\Transcode\ProcessUploadedVideo;
use App\Models\User;
use App\Models\Video;
use App\Support\Permissions;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UploadFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        config()->set('streaming.media_disk', 'local');
        Storage::fake('local');
        Queue::fake(); // don't run the transcode pipeline during upload tests
    }

    protected function manager(): User
    {
        $user = User::factory()->create();
        $user->assignRole(Permissions::ROLE_CONTENT_MANAGER);

        return $user;
    }

    public function test_local_resumable_upload_end_to_end(): void
    {
        $this->actingAs($this->manager());

        // 1. Initiate
        $init = $this->postJson(route('admin.uploads.init'), [
            'filename' => 'My Clip.mp4',
            'size' => 11,
            'title' => 'My Clip',
        ]);

        $init->assertOk()->assertJsonPath('data.mode', 'local');
        $token = $init->json('data.token');
        $videoId = $init->json('data.video_id');

        $this->assertDatabaseHas('videos', ['id' => $videoId, 'processing_status' => 'uploading']);

        // 2. Upload two chunks (raw body)
        $this->call('PUT', "/admin/uploads/{$token}/parts/1", [], [], [], [], 'Hello ')->assertOk();
        $this->call('PUT', "/admin/uploads/{$token}/parts/2", [], [], [], [], 'World')->assertOk();

        // 3. Complete
        $complete = $this->postJson("/admin/uploads/{$token}/complete", []);
        $complete->assertOk()->assertJsonPath('data.status', 'uploaded');

        $video = Video::find($videoId);
        $this->assertSame('uploaded', $video->processing_status);
        $this->assertNotNull($video->original_path);

        Storage::disk('local')->assertExists($video->original_path);
        $this->assertSame('Hello World', Storage::disk('local')->get($video->original_path));
        $this->assertSame(11, $video->file_size);

        // Transcoding pipeline is queued on completion.
        Queue::assertPushed(ProcessUploadedVideo::class);
    }

    public function test_rejects_unsupported_format(): void
    {
        $this->actingAs($this->manager());

        $this->postJson(route('admin.uploads.init'), [
            'filename' => 'malware.exe',
            'size' => 100,
        ])->assertStatus(422);
    }

    public function test_abort_marks_video_failed(): void
    {
        $this->actingAs($this->manager());

        $token = $this->postJson(route('admin.uploads.init'), [
            'filename' => 'clip.mp4', 'size' => 50,
        ])->json('data.token');

        $this->deleteJson("/admin/uploads/{$token}")->assertOk();

        $this->assertDatabaseHas('videos', ['processing_status' => 'failed']);
    }

    public function test_upload_requires_permission(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Permissions::ROLE_USER);
        $this->actingAs($user);

        $this->postJson(route('admin.uploads.init'), [
            'filename' => 'clip.mp4', 'size' => 50,
        ])->assertForbidden();
    }
}
