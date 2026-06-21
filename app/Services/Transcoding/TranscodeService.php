<?php

namespace App\Services\Transcoding;

use App\Models\Video;
use App\Models\VideoSubtitle;
use App\Services\Storage\MediaStorage;
use Illuminate\Support\Facades\File;
use RuntimeException;

/**
 * High-level transcoding orchestration. Stages the private original into a
 * local scratch directory, drives ffmpeg via FfmpegProcessor, then publishes
 * the derived HLS/poster/thumbnail/preview assets back to the media disk.
 */
class TranscodeService
{
    public function __construct(
        protected MediaStorage $media,
        protected FfmpegProcessor $ffmpeg,
    ) {}

    public function scratchDir(Video $video): string
    {
        return storage_path('app/transcode/'.$video->id);
    }

    /**
     * Ensure the original is available locally; returns the local source path.
     */
    public function ensureStaged(Video $video): string
    {
        if (! $video->original_path) {
            throw new RuntimeException('Video has no original file.');
        }

        $dir = $this->scratchDir($video);
        File::ensureDirectoryExists($dir);

        $local = $dir.'/source.'.($video->source_format ?: 'mp4');

        if (File::exists($local) && File::size($local) > 0) {
            return $local;
        }

        // Stream the original from the media disk into local scratch.
        $stream = $this->media->disk()->readStream($video->original_path);
        $out = fopen($local, 'wb');
        stream_copy_to_stream($stream, $out);
        fclose($out);
        if (is_resource($stream)) {
            fclose($stream);
        }

        return $local;
    }

    /**
     * Probe the source and persist core metadata + planned quality ladder.
     */
    public function probeAndStore(Video $video): array
    {
        $source = $this->ensureStaged($video);
        $meta = $this->ffmpeg->probe($source);

        $video->update([
            'duration' => $meta['duration'],
            'width' => $meta['width'],
            'height' => $meta['height'],
            'available_qualities' => array_keys($this->ladderFor((int) $meta['height'])),
            'processing_status' => 'processing',
        ]);

        return $meta;
    }

    /**
     * Renditions appropriate for the source height (never upscale).
     *
     * @return array<string, array<string, mixed>>
     */
    public function ladderFor(int $sourceHeight): array
    {
        $all = (array) config('streaming.renditions', []);

        if ($sourceHeight <= 0) {
            return array_slice($all, 0, 1, true); // unknown: smallest only
        }

        $ladder = array_filter($all, fn ($r) => $r['height'] <= $sourceHeight + 16);

        // Always keep at least the smallest rendition.
        return $ladder ?: array_slice($all, 0, 1, true);
    }

    public function transcodeHls(Video $video): void
    {
        $source = $this->ensureStaged($video);
        $dir = $this->scratchDir($video).'/hls';
        File::ensureDirectoryExists($dir);

        $ladder = $this->ladderFor((int) $video->height);
        $masterLines = ['#EXTM3U', '#EXT-X-VERSION:3'];
        $remotePrefix = $this->media->path('hls', (string) $video->id);

        foreach ($ladder as $name => $r) {
            $this->ffmpeg->run($this->ffmpeg->hlsRenditionCommand($source, $r, $dir, $name));

            $bandwidth = $this->kbpsToBits($r['v_bitrate']) + $this->kbpsToBits($r['a_bitrate']);
            $masterLines[] = "#EXT-X-STREAM-INF:BANDWIDTH={$bandwidth},RESOLUTION={$r['width']}x{$r['height']}";
            $masterLines[] = "{$name}.m3u8";

            $video->files()->updateOrCreate(
                ['quality' => $name],
                [
                    'height' => $r['height'],
                    'width' => $r['width'],
                    'bitrate' => $r['v_bitrate'],
                    'playlist_path' => "{$remotePrefix}/{$name}.m3u8",
                    'is_ready' => true,
                ],
            );
        }

        $master = (string) config('streaming.hls.playlist_name', 'master.m3u8');
        File::put($dir.'/'.$master, implode("\n", $masterLines)."\n");

        $this->uploadDirectory($dir, $remotePrefix);

        // Record per-rendition output sizes.
        foreach ($ladder as $name => $r) {
            $size = $this->media->size("{$remotePrefix}/{$name}.m3u8");
            $segs = collect($this->media->disk()->files($remotePrefix))
                ->filter(fn ($f) => str_starts_with(basename($f), $name.'_'))
                ->sum(fn ($f) => $this->media->size($f));
            $video->files()->where('quality', $name)->update(['file_size' => $size + $segs]);
        }

        $video->update([
            'hls_master_path' => "{$remotePrefix}/{$master}",
            'available_qualities' => array_keys($ladder),
        ]);
    }

    public function generatePoster(Video $video): void
    {
        $source = $this->ensureStaged($video);
        $dir = $this->scratchDir($video);
        $at = max(1.0, ($video->duration ?: 30) * 0.1);
        $local = $dir.'/poster.jpg';

        $this->ffmpeg->run($this->ffmpeg->frameCommand(
            $source, $at, (int) config('streaming.poster.width', 1280), $local
        ));

        $remote = $this->media->path('posters', $video->id.'/poster.jpg');
        $this->putFile($local, $remote);
        $video->update(['poster_path' => $remote]);
    }

    public function generateThumbnails(Video $video): void
    {
        $source = $this->ensureStaged($video);
        $dir = $this->scratchDir($video).'/thumbs';
        File::ensureDirectoryExists($dir);

        $count = max(1, (int) config('streaming.thumbnails.count', 6));
        $width = (int) config('streaming.thumbnails.width', 320);
        $duration = max(1, (int) $video->duration);

        $video->thumbnails()->delete();

        for ($i = 0; $i < $count; $i++) {
            $at = $duration * (($i + 0.5) / $count);
            $local = $dir."/thumb_{$i}.jpg";
            $this->ffmpeg->run($this->ffmpeg->frameCommand($source, $at, $width, $local));

            $remote = $this->media->path('thumbnails', $video->id."/thumb_{$i}.jpg");
            $this->putFile($local, $remote);

            $video->thumbnails()->create([
                'path' => $remote,
                'time_offset' => (int) $at,
                'sort_order' => $i,
            ]);
        }
    }

    public function generatePreview(Video $video): void
    {
        $source = $this->ensureStaged($video);
        $dir = $this->scratchDir($video).'/preview';
        File::ensureDirectoryExists($dir);

        $start = (int) ($video->preview_start ?? config('streaming.preview.default_start', 0));
        $seconds = $video->effectivePreviewSeconds();
        $local = $dir.'/preview.mp4';

        $this->ffmpeg->run($this->ffmpeg->previewClipCommand($source, $start, $seconds, $local));

        $remoteMp4 = $this->media->path('previews', $video->id.'/preview.mp4');
        $this->putFile($local, $remoteMp4);

        // Also produce a single-rendition HLS preview for adaptive playback.
        $hlsDir = $dir.'/hls';
        File::ensureDirectoryExists($hlsDir);
        $rendition = config('streaming.renditions.480p');
        $this->ffmpeg->run($this->ffmpeg->hlsRenditionCommand($local, $rendition, $hlsDir, 'preview'));

        $remoteHlsPrefix = $this->media->path('previews', $video->id.'/hls');
        $this->uploadDirectory($hlsDir, $remoteHlsPrefix);

        $video->preview()->updateOrCreate([], [
            'mp4_path' => $remoteMp4,
            'hls_path' => "{$remoteHlsPrefix}/preview.m3u8",
            'start_seconds' => $start,
            'duration_seconds' => $seconds,
            'is_ready' => true,
        ]);
    }

    public function processSubtitle(VideoSubtitle $subtitle): void
    {
        // Stage the uploaded subtitle locally and convert SRT -> VTT.
        $dir = storage_path('app/transcode/sub_'.$subtitle->id);
        File::ensureDirectoryExists($dir);

        $srcExt = $subtitle->format ?: 'srt';
        $local = $dir.'/input.'.$srcExt;
        $stream = $this->media->disk()->readStream($subtitle->path);
        File::put($local, stream_get_contents($stream));
        if (is_resource($stream)) {
            fclose($stream);
        }

        $vtt = $dir.'/output.vtt';
        $this->ffmpeg->run($this->ffmpeg->subtitleConvertCommand($local, $vtt));

        $remote = $this->media->path('subtitles', $subtitle->video_id.'/'.$subtitle->language.'.vtt');
        $this->putFile($vtt, $remote);

        $subtitle->update(['path' => $remote, 'format' => 'vtt', 'is_ready' => true]);
        File::deleteDirectory($dir);
    }

    public function finalize(Video $video): void
    {
        $video->update([
            'processing_status' => 'ready',
            'processing_progress' => 100,
        ]);

        File::deleteDirectory($this->scratchDir($video));
    }

    public function cleanup(Video $video): void
    {
        File::deleteDirectory($this->scratchDir($video));
    }

    // ---- helpers ----------------------------------------------------------

    protected function putFile(string $local, string $remote): void
    {
        $this->media->disk()->put($remote, fopen($local, 'rb'));
    }

    protected function uploadDirectory(string $localDir, string $remotePrefix): void
    {
        foreach (File::allFiles($localDir) as $file) {
            $remote = $remotePrefix.'/'.$file->getRelativePathname();
            $this->media->disk()->put($remote, fopen($file->getPathname(), 'rb'));
        }
    }

    protected function kbpsToBits(string $bitrate): int
    {
        $n = (int) preg_replace('/[^0-9]/', '', $bitrate);

        return str_contains(strtolower($bitrate), 'k') ? $n * 1000 : $n;
    }
}
