<?php

namespace App\Jobs\Transcode;

use App\Models\Video;
use App\Services\Transcoding\TranscodeService;

class GenerateThumbnails extends BaseTranscodeJob
{
    protected function stage(): string
    {
        return 'thumbnails';
    }

    protected function queueKey(): string
    {
        return 'thumbnails';
    }

    protected function progressAfter(): int
    {
        return 25;
    }

    protected function perform(TranscodeService $service, Video $video): void
    {
        $service->generateThumbnails($video);
    }
}
