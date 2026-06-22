<?php

namespace App\Services\Streaming;

use Illuminate\Support\Facades\Crypt;

/**
 * Issues opaque, self-contained, expiring tokens that authorize access to an
 * HLS asset directory. The token is embedded in the URL *path* (not the query
 * string) so that relative playlist/segment references inside a .m3u8 resolve
 * to sibling URLs that carry the same token — the only scheme that works for
 * HLS over private storage.
 */
class StreamTokenService
{
    /**
     * @param  string  $base  media-disk directory holding the HLS assets (e.g. "hls/12")
     * @param  string  $mode  full|preview
     */
    public function issue(string $base, int $videoId, string $mode = 'full', ?int $ttl = null): string
    {
        $ttl ??= (int) config('streaming.hls.url_ttl', 14400);

        $payload = [
            'b' => trim($base, '/'),
            'v' => $videoId,
            'm' => $mode,
            'e' => now()->addSeconds($ttl)->getTimestamp(),
        ];

        return $this->base64UrlEncode(Crypt::encrypt($payload));
    }

    /**
     * @return array{b:string,v:int,m:string,e:int}|null
     */
    public function parse(string $token): ?array
    {
        try {
            $payload = Crypt::decrypt($this->base64UrlDecode($token));
        } catch (\Throwable) {
            return null;
        }

        if (! is_array($payload) || ($payload['e'] ?? 0) < now()->getTimestamp()) {
            return null;
        }

        return $payload;
    }

    protected function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    protected function base64UrlDecode(string $value): string
    {
        return base64_decode(strtr($value, '-_', '+/'));
    }
}
