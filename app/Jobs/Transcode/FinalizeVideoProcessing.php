<?php

namespace App\Jobs\Transcode;

use App\Models\User;
use App\Models\Video;
use App\Services\Notifier;
use App\Services\Transcoding\TranscodeService;

class FinalizeVideoProcessing extends BaseTranscodeJob
{
    protected function stage(): string
    {
        return 'finalize';
    }

    protected function queueKey(): string
    {
        return 'upload_validation';
    }

    protected function progressAfter(): int
    {
        return 100;
    }

    protected function perform(TranscodeService $service, Video $video): void
    {
        $service->finalize($video);

        // Notify the uploader that the video is ready.
        if ($uploader = User::find($video->uploaded_by)) {
            app(Notifier::class)->notify(
                $uploader,
                'video_ready',
                'Video ready',
                "“{$video->title}” has finished processing and is ready to publish.",
                null,
                'check',
            );
        }
    }
}
