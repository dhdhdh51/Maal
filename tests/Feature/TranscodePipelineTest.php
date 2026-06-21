<?php

namespace Tests\Feature;

use App\Jobs\Transcode\FinalizeVideoProcessing;
use App\Jobs\Transcode\GeneratePoster;
use App\Jobs\Transcode\GeneratePreviewClip;
use App\Jobs\Transcode\GenerateThumbnails;
use App\Jobs\Transcode\ProcessUploadedVideo;
use App\Jobs\Transcode\TranscodeHls;
use App\Models\Video;
use App\Services\Transcoding\TranscodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Mockery;
use Tests\TestCase;

class TranscodePipelineTest extends TestCase
{
    use RefreshDatabase;

    public function test_ladder_never_upscales_source(): void
    {
        $service = app(TranscodeService::class);

        $this->assertSame(['360p', '480p', '720p'], array_keys($service->ladderFor(720)));
        $this->assertSame(['360p', '480p', '720p', '1080p', '4k'], array_keys($service->ladderFor(2160)));
        $this->assertSame(['360p'], array_keys($service->ladderFor(240)));
    }

    public function test_process_uploaded_video_probes_then_chains_pipeline(): void
    {
        Bus::fake();

        $video = Video::create([
            'title' => 'Clip', 'slug' => 'clip', 'original_path' => 'originals/1/source.mp4',
            'source_format' => 'mp4', 'processing_status' => 'uploaded', 'file_size' => 1000,
        ]);

        // Stub the heavy service so no ffmpeg runs.
        $service = Mockery::mock(TranscodeService::class);
        $service->shouldReceive('probeAndStore')->once()->andReturnUsing(function (Video $v) {
            $v->update(['duration' => 100, 'width' => 1280, 'height' => 720, 'processing_status' => 'processing']);

            return ['duration' => 100, 'width' => 1280, 'height' => 720, 'video_codec' => 'h264', 'audio_codec' => 'aac', 'bitrate' => 1000];
        });

        (new ProcessUploadedVideo($video->id))->handle($service);

        Bus::assertChained([
            GeneratePoster::class,
            GenerateThumbnails::class,
            GeneratePreviewClip::class,
            TranscodeHls::class,
            FinalizeVideoProcessing::class,
        ]);

        $this->assertDatabaseHas('video_processing_jobs', [
            'video_id' => $video->id, 'stage' => 'validation', 'status' => 'completed',
        ]);
    }

    public function test_invalid_source_fails_validation(): void
    {
        $video = Video::create([
            'title' => 'Bad', 'slug' => 'bad', 'original_path' => 'originals/2/source.mp4',
            'source_format' => 'mp4', 'processing_status' => 'uploaded', 'file_size' => 10,
        ]);

        $service = Mockery::mock(TranscodeService::class);
        $service->shouldReceive('probeAndStore')->once()
            ->andReturn(['duration' => 0, 'width' => null, 'height' => null, 'video_codec' => null, 'audio_codec' => null, 'bitrate' => null]);

        $job = new ProcessUploadedVideo($video->id);

        try {
            $job->handle($service);
            $this->fail('Expected validation to throw.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('not a valid', $e->getMessage());
        }

        $this->assertDatabaseHas('video_processing_jobs', [
            'video_id' => $video->id, 'stage' => 'validation', 'status' => 'failed',
        ]);
    }

    public function test_jobs_use_dedicated_queues(): void
    {
        $this->assertSame(config('streaming.queues.transcoding'), (new TranscodeHls(1))->queue);
        $this->assertSame(config('streaming.queues.previews'), (new GeneratePreviewClip(1))->queue);
        $this->assertSame(config('streaming.queues.thumbnails'), (new GeneratePoster(1))->queue);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
