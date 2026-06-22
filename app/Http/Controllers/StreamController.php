<?php

namespace App\Http\Controllers;

use App\Models\Video;
use App\Services\Storage\MediaStorage;
use App\Services\Streaming\StreamTokenService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams HLS playlists/segments and single derived assets from private
 * storage. HLS is authorized by an opaque path token (so relative child
 * references inherit access); single assets use Laravel signed URLs.
 */
class StreamController extends Controller
{
    public function __construct(
        protected MediaStorage $media,
        protected StreamTokenService $tokens,
    ) {}

    /**
     * Serve an HLS file (master/rendition playlist or .ts segment).
     */
    public function hls(string $token, string $file): StreamedResponse
    {
        $payload = $this->tokens->parse($token);

        abort_if($payload === null, 403, 'Stream link expired.');
        abort_if($this->hasTraversal($file), 400);

        // Block instantly-disabled content even with a valid token.
        if (Video::whereKey($payload['v'])->value('is_disabled')) {
            abort(403, 'This content is unavailable.');
        }

        $path = $payload['b'].'/'.ltrim($file, '/');

        abort_unless($this->media->exists($path), 404);

        return $this->stream($path, $file);
    }

    /**
     * Serve a single signed asset (poster, thumbnail, subtitle, mp4 fallback).
     * Route is protected by the "signed" middleware.
     */
    public function asset(Request $request): StreamedResponse
    {
        $path = (string) $request->query('path');

        abort_if($this->hasTraversal($path) || $path === '', 400);
        abort_unless($this->media->exists($path), 404);

        return $this->stream($path, basename($path));
    }

    protected function stream(string $path, string $name): StreamedResponse
    {
        $contentType = $this->contentType($name);
        $isPlaylist = str_ends_with($name, '.m3u8');

        return $this->media->disk()->response($path, $name, [
            'Content-Type' => $contentType,
            // Playlists must never be cached (they expire); segments may be briefly cached.
            'Cache-Control' => $isPlaylist ? 'no-store, private' : 'private, max-age=60',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    protected function contentType(string $name): string
    {
        return match (true) {
            str_ends_with($name, '.m3u8') => 'application/vnd.apple.mpegurl',
            str_ends_with($name, '.ts') => 'video/mp2t',
            str_ends_with($name, '.vtt') => 'text/vtt',
            str_ends_with($name, '.mp4') => 'video/mp4',
            str_ends_with($name, '.jpg'), str_ends_with($name, '.jpeg') => 'image/jpeg',
            str_ends_with($name, '.png') => 'image/png',
            str_ends_with($name, '.webp') => 'image/webp',
            default => 'application/octet-stream',
        };
    }

    protected function hasTraversal(string $path): bool
    {
        return str_contains($path, '..') || str_starts_with($path, '/');
    }
}
