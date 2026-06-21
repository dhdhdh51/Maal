<?php

namespace App\Console\Commands;

use App\Models\Video;
use App\Services\Storage\MediaStorage;
use Illuminate\Console\Command;

class CleanupTempUploads extends Command
{
    protected $signature = 'maal:cleanup-uploads {--hours=24 : Age threshold in hours for abandoned uploads}';

    protected $description = 'Remove abandoned multipart upload temp files and stale "uploading" video records.';

    public function handle(MediaStorage $media): int
    {
        $hours = (int) $this->option('hours');
        $cutoff = now()->subHours($hours);

        // Remove temp chunk directories older than the cutoff.
        $tempRoot = (string) config('streaming.paths.temp', 'tmp/uploads');
        $removed = 0;

        foreach ($media->disk()->directories($tempRoot) as $dir) {
            try {
                $lastModified = $media->disk()->lastModified($dir.'/'.basename($dir));
            } catch (\Throwable) {
                $lastModified = null;
            }

            // Fall back to deleting directories with no recent files.
            $files = $media->disk()->files($dir);
            $newest = collect($files)->map(fn ($f) => $media->disk()->lastModified($f))->max();

            if ($newest === null || $newest < $cutoff->timestamp) {
                $media->disk()->deleteDirectory($dir);
                $removed++;
            }
        }

        // Mark stale "uploading" videos as failed.
        $stale = Video::where('processing_status', 'uploading')
            ->where('created_at', '<', $cutoff)
            ->update(['processing_status' => 'failed']);

        $this->info("Removed {$removed} temp upload dirs; marked {$stale} stale uploads as failed.");

        return self::SUCCESS;
    }
}
