<?php

namespace App\Services\Streaming;

use App\Models\User;
use App\Models\Video;
use App\Services\AccessService;
use App\Services\Storage\MediaStorage;

/**
 * Builds the complete playback payload for a video + viewer: access mode,
 * signed manifest URL, qualities, subtitle tracks, watermark instructions and
 * preview limits. Consumed by both the web player and the mobile API.
 */
class PlaybackService
{
    public function __construct(
        protected AccessService $access,
        protected StreamTokenService $tokens,
        protected MediaStorage $media,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function payload(Video $video, ?User $user, ?string $sessionId = null): array
    {
        $base = [
            'id' => $video->id,
            'title' => $video->title,
            'slug' => $video->slug,
            'poster' => $video->poster_path ? $this->media->temporaryUrl($video->poster_path) : null,
            'duration' => (int) $video->duration,
            'autoplay_next' => (bool) setting('autoplay_next', true),
        ];

        if ($video->is_disabled) {
            return array_merge($base, ['mode' => 'unavailable', 'reason' => 'This content is unavailable.']);
        }

        if (! $video->isReady()) {
            return array_merge($base, [
                'mode' => 'processing',
                'status' => $video->processing_status,
                'progress' => (int) $video->processing_progress,
            ]);
        }

        $canFull = $this->access->canWatchFull($user, $video);

        if ($canFull) {
            return array_merge($base, $this->fullPayload($video, $user, $sessionId));
        }

        if ($video->preview && $video->preview->is_ready) {
            return array_merge($base, $this->previewPayload($video));
        }

        return array_merge($base, $this->lockedPayload($video));
    }

    /**
     * @return array<string, mixed>
     */
    protected function fullPayload(Video $video, ?User $user, ?string $sessionId): array
    {
        $base = $this->media->path('hls', (string) $video->id);
        $token = $this->tokens->issue($base, $video->id, 'full');

        return [
            'mode' => 'full',
            'master_url' => route('stream.hls', ['token' => $token, 'file' => 'master.m3u8']),
            'qualities' => array_merge(['auto'], (array) $video->available_qualities),
            'subtitles' => $this->subtitles($video),
            'watermark' => $this->watermark($video, $user, $sessionId),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function previewPayload(Video $video): array
    {
        $base = $this->media->path('previews', $video->id.'/hls');
        $token = $this->tokens->issue($base, $video->id, 'preview');

        return [
            'mode' => 'preview',
            'master_url' => route('stream.hls', ['token' => $token, 'file' => 'preview.m3u8']),
            'preview_seconds' => $video->preview->duration_seconds ?: $video->effectivePreviewSeconds(),
            'subtitles' => [],
            'unlock' => $this->unlockInfo($video),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function lockedPayload(Video $video): array
    {
        return [
            'mode' => 'locked',
            'reason' => 'This video is locked.',
            'unlock' => $this->unlockInfo($video),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function subtitles(Video $video): array
    {
        return $video->subtitles->where('is_ready', true)->map(fn ($s) => [
            'language' => $s->language,
            'label' => $s->label ?: strtoupper($s->language),
            'url' => $this->media->temporaryUrl($s->path),
            'default' => (bool) $s->is_default,
        ])->values()->all();
    }

    /**
     * Dynamic watermark instructions (rendered + moved client-side).
     *
     * @return array<string, mixed>|null
     */
    protected function watermark(Video $video, ?User $user, ?string $sessionId): ?array
    {
        if (! $video->watermarkEnabled() || ! $user) {
            return null;
        }

        $template = (string) setting('watermark.text', '{email} • {datetime}');
        $text = strtr($template, [
            '{email}' => $user->email,
            '{id}' => (string) $user->id,
            '{phone}' => $this->maskPhone($user->phone),
            '{datetime}' => now()->format('Y-m-d H:i'),
            '{session}' => substr((string) $sessionId, 0, 8),
        ]);

        return [
            'text' => $text,
            'opacity' => (float) setting('watermark.opacity', config('streaming.watermark.opacity', 0.35)),
            'font_size' => (int) setting('watermark.font_size', config('streaming.watermark.font_size', 14)),
            'move_interval' => (int) setting('watermark.move_interval', config('streaming.watermark.move_interval', 8)),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function unlockInfo(Video $video): array
    {
        $category = $video->category;

        return [
            'category' => $category ? [
                'name' => $category->name,
                'slug' => $category->slug,
                'access_type' => $category->access_type,
                'price' => $category->price,
                'currency' => $category->currency,
            ] : null,
            'checkout_url' => $category ? url('/unlock/'.$category->slug) : null,
        ];
    }

    protected function maskPhone(?string $phone): string
    {
        if (! $phone) {
            return '';
        }

        $len = strlen($phone);

        return $len <= 4 ? str_repeat('•', $len) : substr($phone, 0, 3).str_repeat('•', max(0, $len - 6)).substr($phone, -3);
    }
}
