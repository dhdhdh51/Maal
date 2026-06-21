<?php

namespace App\Services\Transcoding;

use Illuminate\Support\Facades\Process;
use RuntimeException;

/**
 * Thin, testable wrapper around the ffmpeg/ffprobe binaries.
 *
 * Command construction is split into pure builder methods (easy to unit test)
 * from execution (Process facade, fakeable in tests). All paths are local
 * filesystem paths — staging to/from object storage is the caller's job.
 */
class FfmpegProcessor
{
    public function __construct(
        protected string $ffmpeg,
        protected string $ffprobe,
        protected int $threads = 0,
        protected int $timeout = 21600,
    ) {}

    public static function fromConfig(): static
    {
        return new static(
            (string) config('streaming.ffmpeg.binary', 'ffmpeg'),
            (string) config('streaming.ffmpeg.ffprobe', 'ffprobe'),
            (int) config('streaming.ffmpeg.threads', 0),
            (int) config('streaming.ffmpeg.timeout', 21600),
        );
    }

    // ---- Probe ------------------------------------------------------------

    /**
     * @return array<int, string>
     */
    public function probeCommand(string $source): array
    {
        return [
            $this->ffprobe, '-v', 'quiet', '-print_format', 'json',
            '-show_format', '-show_streams', $source,
        ];
    }

    /**
     * Probe a source file and return normalised metadata.
     *
     * @return array{duration:int,width:?int,height:?int,video_codec:?string,audio_codec:?string,bitrate:?int}
     */
    public function probe(string $source): array
    {
        $result = Process::timeout(120)->run($this->probeCommand($source));

        if (! $result->successful()) {
            throw new RuntimeException('ffprobe failed: '.$result->errorOutput());
        }

        $data = json_decode($result->output(), true) ?: [];
        $video = collect($data['streams'] ?? [])->firstWhere('codec_type', 'video');
        $audio = collect($data['streams'] ?? [])->firstWhere('codec_type', 'audio');

        return [
            'duration' => (int) round((float) ($data['format']['duration'] ?? 0)),
            'width' => isset($video['width']) ? (int) $video['width'] : null,
            'height' => isset($video['height']) ? (int) $video['height'] : null,
            'video_codec' => $video['codec_name'] ?? null,
            'audio_codec' => $audio['codec_name'] ?? null,
            'bitrate' => isset($data['format']['bit_rate']) ? (int) $data['format']['bit_rate'] : null,
        ];
    }

    // ---- HLS rendition ----------------------------------------------------

    /**
     * Build the ffmpeg command for a single HLS rendition.
     *
     * @param  array{height:int,width:int,v_bitrate:string,a_bitrate:string,maxrate:string,bufsize:string}  $r
     * @return array<int, string>
     */
    public function hlsRenditionCommand(string $source, array $r, string $outDir, string $name): array
    {
        $segmentSeconds = (int) config('streaming.hls.segment_seconds', 6);

        return array_merge([
            $this->ffmpeg, '-y', '-i', $source,
            '-vf', "scale=w={$r['width']}:h={$r['height']}:force_original_aspect_ratio=decrease,pad={$r['width']}:{$r['height']}:(ow-iw)/2:(oh-ih)/2",
            '-c:v', 'libx264', '-profile:v', 'main', '-preset', 'veryfast',
            '-b:v', $r['v_bitrate'], '-maxrate', $r['maxrate'], '-bufsize', $r['bufsize'],
            '-c:a', 'aac', '-b:a', $r['a_bitrate'], '-ac', '2',
            '-threads', (string) $this->threads,
            '-hls_time', (string) $segmentSeconds,
            '-hls_playlist_type', 'vod',
            '-hls_segment_filename', "{$outDir}/{$name}_%03d.ts",
            "{$outDir}/{$name}.m3u8",
        ]);
    }

    // ---- Poster / thumbnails ---------------------------------------------

    /**
     * @return array<int, string>
     */
    public function frameCommand(string $source, float $atSeconds, int $width, string $out): array
    {
        return [
            $this->ffmpeg, '-y', '-ss', (string) $atSeconds, '-i', $source,
            '-vframes', '1', '-vf', "scale={$width}:-2", '-q:v', '3', $out,
        ];
    }

    // ---- Preview clip -----------------------------------------------------

    /**
     * @return array<int, string>
     */
    public function previewClipCommand(string $source, int $start, int $duration, string $out): array
    {
        return [
            $this->ffmpeg, '-y', '-ss', (string) $start, '-t', (string) $duration, '-i', $source,
            '-c:v', 'libx264', '-preset', 'veryfast', '-crf', '23',
            '-c:a', 'aac', '-b:a', '128k', '-movflags', '+faststart', $out,
        ];
    }

    // ---- Subtitle conversion ---------------------------------------------

    /**
     * @return array<int, string>
     */
    public function subtitleConvertCommand(string $source, string $out): array
    {
        return [$this->ffmpeg, '-y', '-i', $source, $out];
    }

    // ---- Execution --------------------------------------------------------

    /**
     * @param  array<int, string>  $command
     */
    public function run(array $command): void
    {
        $result = Process::timeout($this->timeout)->run($command);

        if (! $result->successful()) {
            throw new RuntimeException('ffmpeg failed: '.trim($result->errorOutput() ?: $result->output()));
        }
    }
}
