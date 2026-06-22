<?php

namespace App\Services\Transcoding;

use App\Jobs\Transcode\GeneratePoster;
use App\Jobs\Transcode\GeneratePreviewClip;
use App\Jobs\Transcode\GenerateThumbnails;
use App\Jobs\Transcode\ProcessSubtitle;
use App\Jobs\Transcode\ProcessUploadedVideo;
use App\Jobs\Transcode\TranscodeHls;
use App\Models\Video;
use App\Models\VideoSubtitle;

/**
 * Façade for kicking off and re-running the transcoding pipeline. Used by the
 * upload flow and the admin video manager (reprocess / retry / reprocess stage).
 */
class TranscodeManager
{
    /**
     * Start the full pipeline for a freshly uploaded video.
     */
    public function start(Video $video): void
    {
        $video->update(['processing_status' => 'uploaded', 'processing_progress' => 0]);
        ProcessUploadedVideo::dispatch($video->id);
    }

    /**
     * Re-run the entire pipeline (e.g. after a failure).
     */
    public function reprocess(Video $video): void
    {
        $video->processingJobs()->delete();
        $this->start($video);
    }

    /**
     * Re-run a single stage on demand.
     */
    public function reprocessStage(Video $video, string $stage): void
    {
        $job = match ($stage) {
            'poster' => new GeneratePoster($video->id),
            'thumbnails' => new GenerateThumbnails($video->id),
            'preview' => new GeneratePreviewClip($video->id),
            'transcode' => new TranscodeHls($video->id),
            default => null,
        };

        if ($job) {
            dispatch($job);
        }
    }

    public function cancel(Video $video): void
    {
        $video->update(['processing_status' => 'failed']);
        $video->processingJobs()
            ->whereIn('status', ['queued', 'processing'])
            ->update(['status' => 'cancelled']);
    }

    public function processSubtitle(VideoSubtitle $subtitle): void
    {
        ProcessSubtitle::dispatch($subtitle->id);
    }
}
