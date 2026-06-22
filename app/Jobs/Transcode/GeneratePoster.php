<?php

namespace App\Jobs\Transcode;

use App\Models\Video;
use App\Services\Transcoding\TranscodeService;

class GeneratePoster extends BaseTranscodeJob
{
    protected function stage(): string
    {
        return 'poster';
    }

    protected function queueKey(): string
    {
        return 'thumbnails';
    }

    protected function progressAfter(): int
    {
        return 15;
    }

    protected function perform(TranscodeService $service, Video $video): void
    {
        $service->generatePoster($video);
    }
}
