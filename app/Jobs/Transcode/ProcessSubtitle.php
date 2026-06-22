<?php

namespace App\Jobs\Transcode;

use App\Models\Video;
use App\Models\VideoSubtitle;
use App\Services\Transcoding\TranscodeService;

class ProcessSubtitle extends BaseTranscodeJob
{
    public function __construct(public int $subtitleId)
    {
        // Resolve the parent video for queue/stage bookkeeping.
        $videoId = (int) (VideoSubtitle::whereKey($subtitleId)->value('video_id') ?? 0);
        parent::__construct($videoId);
    }

    protected function stage(): string
    {
        return 'subtitles';
    }

    protected function queueKey(): string
    {
        return 'subtitles';
    }

    protected function progressAfter(): int
    {
        // Subtitles are independent of the main pipeline progress.
        return Video::whereKey($this->videoId)->value('processing_progress') ?? 0;
    }

    protected function perform(TranscodeService $service, Video $video): void
    {
        $subtitle = VideoSubtitle::findOrFail($this->subtitleId);
        $service->processSubtitle($subtitle);
    }
}
