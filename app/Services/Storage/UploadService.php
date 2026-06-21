<?php

namespace App\Services\Storage;

use App\Models\User;
use App\Models\Video;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Resumable, chunked upload orchestration.
 *
 * Large files never pass through a normal PHP form upload. For S3-compatible
 * disks the browser uploads parts directly to the bucket using presigned
 * UploadPart URLs (true multipart). For the local dev disk, chunks are PUT to
 * the app and appended server-side.
 *
 * The server holds an opaque upload session (cache) keyed by a random token so
 * clients can never dictate arbitrary storage keys.
 */
class UploadService
{
    public const SESSION_TTL = 86400; // 24h

    public function __construct(
        protected MediaStorage $media,
        protected StorageQuota $quota,
    ) {}

    /**
     * Begin an upload session and create the backing Video record.
     *
     * @param  array{filename:string,size:int,checksum?:string,content_type?:string,category_id?:int,title?:string}  $data
     * @return array<string, mixed>
     */
    public function initiate(User $user, array $data): array
    {
        $filename = $this->sanitizeFilename($data['filename']);
        $ext = strtolower((string) pathinfo($filename, PATHINFO_EXTENSION));
        $size = (int) $data['size'];

        $allowed = (array) config('streaming.uploads.allowed_formats', []);
        if (! in_array($ext, $allowed, true)) {
            throw new RuntimeException("Unsupported format .{$ext}. Allowed: ".implode(', ', $allowed));
        }

        if ($size <= 0) {
            throw new RuntimeException('Invalid file size.');
        }

        if (! $this->quota->canFit($size)) {
            throw new RuntimeException('Storage quota exceeded. Free up space or increase the quota.');
        }

        $storedName = (string) Str::uuid().'.'.$ext;
        $video = Video::create([
            'uploaded_by' => $user->id,
            'category_id' => $data['category_id'] ?? null,
            'title' => $data['title'] ?? pathinfo($data['filename'], PATHINFO_FILENAME),
            'slug' => $this->uniqueSlug($data['title'] ?? pathinfo($data['filename'], PATHINFO_FILENAME)),
            'original_filename' => $data['filename'],
            'file_size' => $size,
            'source_format' => $ext,
            'processing_status' => 'uploading',
            'processing_progress' => 0,
            'is_published' => false,
        ]);

        $key = $this->media->originalPath($video->id.'/'.$storedName);
        $partSize = max(5, (int) config('streaming.uploads.chunk_size_mb', 8)) * 1024 * 1024;

        $mode = $this->media->isS3() ? 's3' : 'local';
        $uploadId = null;

        if ($mode === 's3') {
            $result = $this->media->s3Client()->createMultipartUpload([
                'Bucket' => $this->media->bucket(),
                'Key' => $key,
                'ContentType' => $data['content_type'] ?? 'application/octet-stream',
            ]);
            $uploadId = $result['UploadId'];
        }

        $token = (string) Str::uuid();
        Cache::put($this->cacheKey($token), [
            'user_id' => $user->id,
            'video_id' => $video->id,
            'key' => $key,
            'mode' => $mode,
            'upload_id' => $uploadId,
            'filename' => $data['filename'],
            'size' => $size,
            'checksum' => $data['checksum'] ?? null,
        ], self::SESSION_TTL);

        return [
            'token' => $token,
            'video_id' => $video->id,
            'mode' => $mode,
            'part_size' => $partSize,
            'total_parts' => (int) ceil($size / $partSize),
        ];
    }

    /**
     * Presign a single part upload (S3 mode). Returns the PUT URL.
     */
    public function signPart(string $token, int $partNumber): string
    {
        $session = $this->session($token);

        if ($session['mode'] !== 's3') {
            throw new RuntimeException('Part signing is only available for cloud storage.');
        }

        $client = $this->media->s3Client();
        $command = $client->getCommand('UploadPart', [
            'Bucket' => $this->media->bucket(),
            'Key' => $session['key'],
            'UploadId' => $session['upload_id'],
            'PartNumber' => $partNumber,
        ]);

        return (string) $client->createPresignedRequest($command, '+30 minutes')->getUri();
    }

    /**
     * Store a chunk for the local dev disk.
     */
    public function storeLocalChunk(string $token, int $partNumber, string $contents): void
    {
        $session = $this->session($token);

        if ($session['mode'] !== 'local') {
            throw new RuntimeException('Direct chunk upload is only used for local storage.');
        }

        $part = $this->media->tempPath($token, sprintf('%06d.part', $partNumber));
        $this->media->disk()->put($part, $contents);
    }

    /**
     * Finalise the upload, returning the ready-to-process Video.
     *
     * @param  array<int, array{PartNumber:int,ETag:string}>  $parts
     */
    public function complete(string $token, array $parts = []): Video
    {
        $session = $this->session($token);
        $video = Video::findOrFail($session['video_id']);

        if ($session['mode'] === 's3') {
            $this->media->s3Client()->completeMultipartUpload([
                'Bucket' => $this->media->bucket(),
                'Key' => $session['key'],
                'UploadId' => $session['upload_id'],
                'MultipartUpload' => ['Parts' => array_map(fn ($p) => [
                    'PartNumber' => (int) $p['PartNumber'],
                    'ETag' => $p['ETag'],
                ], $parts)],
            ]);
        } else {
            $this->assembleLocalParts($token, $session['key']);
        }

        $actualSize = $this->media->size($session['key']);

        $video->update([
            'original_path' => $session['key'],
            'file_size' => $actualSize ?: $session['size'],
            'checksum' => $session['checksum'],
            'processing_status' => 'uploaded',
            'processing_progress' => 0,
        ]);

        Cache::forget($this->cacheKey($token));

        $this->onUploadComplete($video);

        return $video->refresh();
    }

    public function abort(string $token): void
    {
        $session = Cache::get($this->cacheKey($token));

        if (! $session) {
            return;
        }

        if ($session['mode'] === 's3' && $session['upload_id']) {
            try {
                $this->media->s3Client()->abortMultipartUpload([
                    'Bucket' => $this->media->bucket(),
                    'Key' => $session['key'],
                    'UploadId' => $session['upload_id'],
                ]);
            } catch (\Throwable) {
                // best-effort
            }
        } else {
            $this->media->deleteDirectory($this->media->tempPath($token));
        }

        if ($video = Video::find($session['video_id'])) {
            $video->update(['processing_status' => 'failed']);
        }

        Cache::forget($this->cacheKey($token));
    }

    // ---- internals --------------------------------------------------------

    protected function assembleLocalParts(string $token, string $key): void
    {
        $dir = $this->media->tempPath($token);
        $files = collect($this->media->disk()->files($dir))->sort()->values();

        if ($files->isEmpty()) {
            throw new RuntimeException('No uploaded chunks found.');
        }

        // Stream-concatenate parts into the destination to bound memory use.
        $disk = $this->media->disk();
        $tmpLocal = tempnam(sys_get_temp_dir(), 'maal_merge_');
        $out = fopen($tmpLocal, 'wb');

        foreach ($files as $file) {
            $stream = $disk->readStream($file);
            stream_copy_to_stream($stream, $out);
            fclose($stream);
        }
        fclose($out);

        $disk->put($key, fopen($tmpLocal, 'rb'));
        @unlink($tmpLocal);
        $disk->deleteDirectory($dir);
    }

    /**
     * Hand off to the transcoding pipeline (System #4) if present.
     */
    protected function onUploadComplete(Video $video): void
    {
        $job = 'App\\Jobs\\Transcode\\ProcessUploadedVideo';

        if (class_exists($job)) {
            $job::dispatch($video->id);
        }
    }

    protected function session(string $token): array
    {
        $session = Cache::get($this->cacheKey($token));

        if (! $session) {
            throw new RuntimeException('Upload session expired or not found.');
        }

        return $session;
    }

    protected function cacheKey(string $token): string
    {
        return "upload:{$token}";
    }

    protected function sanitizeFilename(string $name): string
    {
        $base = Str::slug(pathinfo($name, PATHINFO_FILENAME)) ?: 'video';
        $ext = preg_replace('/[^a-z0-9]/i', '', (string) pathinfo($name, PATHINFO_EXTENSION));

        return $ext ? "{$base}.{$ext}" : $base;
    }

    protected function uniqueSlug(string $title): string
    {
        $slug = Str::slug($title) ?: 'video';
        $base = $slug;
        $i = 1;

        while (Video::where('slug', $slug)->exists()) {
            $slug = $base.'-'.(++$i);
        }

        return $slug;
    }
}
