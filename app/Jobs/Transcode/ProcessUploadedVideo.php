<?php

namespace App\Jobs\Transcode;

use App\Models\Video;
use App\Services\Transcoding\TranscodeService;
use Illuminate\Support\Facades\Bus;
use RuntimeException;

/**
 * Entry point of the transcoding pipeline (dispatched by UploadService on
 * upload completion). Probes the source, validates integrity, then chains the
 * dedicated-queue stage jobs in order. A failure in any chained job marks the
 * video failed (handled by BaseTranscodeJob::failed()).
 */
class ProcessUploadedVideo extends BaseTranscodeJob
{
    protected function stage(): string
    {
        return 'validation';
    }

    protected function queueKey(): string
    {
        return 'upload_validation';
    }

    protected function progressAfter(): int
    {
        return 5;
    }

    protected function perform(TranscodeService $service, Video $video): void
    {
        $meta = $service->probeAndStore($video);

        // Integrity / sanity validation.
        if (($meta['duration'] ?? 0) <= 0 || empty($meta['video_codec'])) {
            throw new RuntimeException('Source file is not a valid/decodable video.');
        }

        Bus::chain([
            new GeneratePoster($video->id),
            new GenerateThumbnails($video->id),
            new GeneratePreviewClip($video->id),
            new TranscodeHls($video->id),
            new FinalizeVideoProcessing($video->id),
        ])->dispatch();
    }
}
