<?php

namespace App\Jobs\Transcode;

use App\Models\User;
use App\Models\Video;
use App\Models\VideoProcessingJob;
use App\Services\Notifier;
use App\Services\Transcoding\TranscodeService;
use App\Support\Permissions;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Base for all transcoding stage jobs. Handles per-stage VideoProcessingJob
 * bookkeeping, progress updates, retries and failure notification so the
 * concrete jobs only implement the actual work.
 */
abstract class BaseTranscodeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 21600;

    public function __construct(public int $videoId)
    {
        $this->onQueue(config('streaming.queues.'.$this->queueKey(), 'default'));
    }

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [30, 120, 300];
    }

    abstract protected function stage(): string;

    abstract protected function queueKey(): string;

    abstract protected function progressAfter(): int;

    abstract protected function perform(TranscodeService $service, Video $video): void;

    public function handle(TranscodeService $service): void
    {
        $video = Video::find($this->videoId);

        if (! $video || $video->is_disabled) {
            return;
        }

        $job = VideoProcessingJob::firstOrCreate(
            ['video_id' => $video->id, 'stage' => $this->stage()],
            ['status' => 'queued'],
        );

        $job->markProcessing();

        try {
            $this->perform($service, $video);

            $job->markCompleted();
            $video->update(['processing_progress' => $this->progressAfter()]);
        } catch (\Throwable $e) {
            $job->markFailed($e->getMessage());
            Log::error("[transcode:{$this->stage()}] video {$video->id}: ".$e->getMessage());
            throw $e; // allow queue retry/backoff
        }
    }

    /**
     * Called by the queue after final retry exhaustion.
     */
    public function failed(\Throwable $e): void
    {
        $video = Video::find($this->videoId);

        if (! $video) {
            return;
        }

        $video->update(['processing_status' => 'failed']);

        VideoProcessingJob::where('video_id', $video->id)
            ->where('stage', $this->stage())
            ->update(['status' => 'failed', 'error_message' => $e->getMessage()]);

        $this->notifyAdmins($video, $e->getMessage());
    }

    protected function notifyAdmins(Video $video, string $message): void
    {
        $notifier = app(Notifier::class);

        User::role(Permissions::ROLE_ADMIN)->get()->each(function (User $admin) use ($notifier, $video, $message) {
            $notifier->notify(
                $admin,
                'transcode_failed',
                'Video processing failed',
                "“{$video->title}” failed at stage {$this->stage()}: {$message}",
                null,
                'alert',
            );
        });
    }
}
