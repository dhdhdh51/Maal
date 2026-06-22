<?php

namespace Tests\Unit;

use App\Services\Transcoding\FfmpegProcessor;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

class FfmpegProcessorTest extends TestCase
{
    public function test_probe_parses_ffprobe_json(): void
    {
        Process::fake([
            '*' => Process::result(output: json_encode([
                'format' => ['duration' => '123.45', 'bit_rate' => '4500000'],
                'streams' => [
                    ['codec_type' => 'video', 'codec_name' => 'h264', 'width' => 1920, 'height' => 1080],
                    ['codec_type' => 'audio', 'codec_name' => 'aac'],
                ],
            ])),
        ]);

        $meta = (new FfmpegProcessor('ffmpeg', 'ffprobe'))->probe('/tmp/source.mp4');

        $this->assertSame(123, $meta['duration']);
        $this->assertSame(1920, $meta['width']);
        $this->assertSame(1080, $meta['height']);
        $this->assertSame('h264', $meta['video_codec']);
        $this->assertSame('aac', $meta['audio_codec']);
    }

    public function test_hls_rendition_command_contains_scaling_and_codecs(): void
    {
        $p = new FfmpegProcessor('ffmpeg', 'ffprobe', threads: 2);
        $r = ['height' => 720, 'width' => 1280, 'v_bitrate' => '2800k', 'a_bitrate' => '128k', 'maxrate' => '2996k', 'bufsize' => '4200k'];

        $cmd = $p->hlsRenditionCommand('/src.mp4', $r, '/out', '720p');
        $joined = implode(' ', $cmd);

        $this->assertStringContainsString('libx264', $joined);
        $this->assertStringContainsString('-b:v 2800k', $joined);
        $this->assertStringContainsString('-hls_playlist_type vod', $joined);
        $this->assertStringContainsString('/out/720p.m3u8', $joined);
        $this->assertStringContainsString('/out/720p_%03d.ts', $joined);
    }

    public function test_preview_and_frame_commands(): void
    {
        $p = new FfmpegProcessor('ffmpeg', 'ffprobe');

        $preview = implode(' ', $p->previewClipCommand('/src.mp4', 5, 20, '/out/preview.mp4'));
        $this->assertStringContainsString('-ss 5', $preview);
        $this->assertStringContainsString('-t 20', $preview);
        $this->assertStringContainsString('+faststart', $preview);

        $frame = implode(' ', $p->frameCommand('/src.mp4', 12.5, 320, '/out/thumb.jpg'));
        $this->assertStringContainsString('-vframes 1', $frame);
        $this->assertStringContainsString('scale=320:-2', $frame);
    }

    public function test_run_throws_on_failure(): void
    {
        Process::fake(['*' => Process::result(output: '', errorOutput: 'boom', exitCode: 1)]);

        $this->expectException(\RuntimeException::class);
        (new FfmpegProcessor('ffmpeg', 'ffprobe'))->run(['ffmpeg', '-i', 'x']);
    }
}
