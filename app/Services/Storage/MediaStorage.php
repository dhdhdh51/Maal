<?php

namespace App\Services\Storage;

use App\Models\StorageConfiguration;
use Aws\S3\S3Client;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

/**
 * Single entry point for all media (originals, HLS, posters, thumbnails,
 * previews, subtitles). Resolves the active disk from an active
 * StorageConfiguration row, falling back to config/streaming.php.
 *
 * Original source files are always kept private; only signed/CDN URLs are
 * ever exposed for derived assets.
 */
class MediaStorage
{
    /**
     * Resolve the active media disk name.
     */
    public function diskName(): string
    {
        $active = StorageConfiguration::where('is_active', true)->first();

        return $active?->disk ?: (string) config('streaming.media_disk', 'local');
    }

    public function disk(): Filesystem
    {
        return Storage::disk($this->diskName());
    }

    public function isS3(): bool
    {
        return (string) config("filesystems.disks.{$this->diskName()}.driver") === 's3';
    }

    // ---- Path builders ----------------------------------------------------

    public function path(string $type, string ...$segments): string
    {
        $base = (string) config("streaming.paths.{$type}", $type);

        return collect([$base, ...$segments])
            ->filter(fn ($s) => $s !== '' && $s !== null)
            ->map(fn ($s) => trim($s, '/'))
            ->implode('/');
    }

    public function originalPath(string $filename): string
    {
        return $this->path('originals', $filename);
    }

    public function tempPath(string ...$segments): string
    {
        return $this->path('temp', ...$segments);
    }

    // ---- Common operations ------------------------------------------------

    public function exists(string $path): bool
    {
        return $this->disk()->exists($path);
    }

    public function size(string $path): int
    {
        return $this->exists($path) ? (int) $this->disk()->size($path) : 0;
    }

    public function delete(string|array $paths): bool
    {
        return $this->disk()->delete($paths);
    }

    public function deleteDirectory(string $dir): bool
    {
        return $this->disk()->deleteDirectory($dir);
    }

    /**
     * A time-limited URL for a derived asset. Uses the CDN domain when set,
     * otherwise a signed temporary URL (S3) or a signed app route (local).
     */
    public function temporaryUrl(string $path, ?int $seconds = null): string
    {
        $seconds ??= (int) config('streaming.hls.url_ttl', 14400);

        if ($cdn = config('streaming.cdn_url')) {
            return rtrim((string) $cdn, '/').'/'.ltrim($path, '/');
        }

        if ($this->isS3()) {
            return $this->disk()->temporaryUrl($path, now()->addSeconds($seconds));
        }

        // Local/dev: served through a signed streaming route (System 5).
        return URL::temporarySignedRoute(
            'stream.asset',
            now()->addSeconds($seconds),
            ['path' => $path],
        );
    }

    /**
     * Build a raw AWS S3 client from the active disk config (used for
     * multipart upload orchestration which Flysystem does not expose).
     */
    public function s3Client(): S3Client
    {
        $cfg = config("filesystems.disks.{$this->diskName()}");

        $args = [
            'version' => 'latest',
            'region' => $cfg['region'] ?? 'us-east-1',
            'credentials' => [
                'key' => $cfg['key'] ?? '',
                'secret' => $cfg['secret'] ?? '',
            ],
            'use_path_style_endpoint' => (bool) ($cfg['use_path_style_endpoint'] ?? false),
        ];

        if (! empty($cfg['endpoint'])) {
            $args['endpoint'] = $cfg['endpoint'];
        }

        return new S3Client($args);
    }

    public function bucket(): ?string
    {
        return config("filesystems.disks.{$this->diskName()}.bucket");
    }
}
