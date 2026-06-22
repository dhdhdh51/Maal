<?php

namespace App\Jobs\Transcode;

use App\Models\Video;
use App\Services\Transcoding\TranscodeService;

class GeneratePreviewClip extends BaseTranscodeJob
{
    protected function stage(): string
    {
        return 'preview';
    }

    protected function queueKey(): string
    {
        return 'previews';
    }

    protected function progressAfter(): int
    {
        return 40;
    }

    protected function perform(TranscodeService $service, Video $video): void
    {
        $video->update(['processing_status' => 'generating_preview']);
        $service->generatePreview($video);
    }
}
