<?php

namespace App\Jobs\Transcode;

use App\Models\Video;
use App\Services\Transcoding\TranscodeService;

class TranscodeHls extends BaseTranscodeJob
{
    protected function stage(): string
    {
        return 'transcode';
    }

    protected function queueKey(): string
    {
        return 'transcoding';
    }

    protected function progressAfter(): int
    {
        return 90;
    }

    protected function perform(TranscodeService $service, Video $video): void
    {
        $video->update(['processing_status' => 'encoding']);
        $service->transcodeHls($video);
    }
}
