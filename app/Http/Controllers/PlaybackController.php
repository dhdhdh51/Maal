<?php

namespace App\Http\Controllers;

use App\Models\Video;
use App\Services\DeviceManager;
use App\Services\Streaming\PlaybackService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlaybackController extends Controller
{
    public function __construct(
        protected PlaybackService $playback,
        protected DeviceManager $devices,
    ) {}

    /**
     * The watch page.
     */
    public function watch(Request $request, Video $video): View
    {
        abort_if($video->is_disabled, 404);

        $video->loadMissing(['category', 'preview', 'subtitles']);

        $payload = $this->playback->payload($video, $request->user(), $request->session()->getId());

        // Count a view for ready, playable loads.
        if (in_array($payload['mode'], ['full', 'preview'], true)) {
            Video::whereKey($video->id)->increment('views_count');
        }

        return view('watch.player', [
            'video' => $video,
            'payload' => $payload,
        ]);
    }

    /**
     * JSON manifest for the player / mobile clients (fresh signed URLs).
     */
    public function manifest(Request $request, Video $video): JsonResponse
    {
        abort_if($video->is_disabled, 404);
        $video->loadMissing(['category', 'preview', 'subtitles']);

        return ApiResponse::success(
            $this->playback->payload($video, $request->user(), $request->session()->getId())
        );
    }

    /**
     * Playback heartbeat: keeps the session alive, enforces single-stream
     * concurrency for the device limit, and reports preview limits.
     */
    public function heartbeat(Request $request, Video $video): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return ApiResponse::success(['ok' => true]); // guests: nothing to track
        }

        $session = $this->currentSession($request, $user);

        // Block concurrent playback from another active device/session.
        if ($this->devices->hasConcurrentPlayback($user, $session?->id)) {
            if ($session) {
                $session->update(['is_playing' => false]);
            }

            return ApiResponse::error(
                'This account is already streaming on another device. Stop it to continue here.',
                409,
                ['concurrent' => true],
            );
        }

        if ($session) {
            $session->update([
                'is_playing' => true,
                'playing_video_id' => $video->id,
                'last_activity_at' => now(),
            ]);
        }

        return ApiResponse::success(['ok' => true]);
    }

    public function stopped(Request $request, Video $video): JsonResponse
    {
        if ($user = $request->user()) {
            $this->currentSession($request, $user)?->update(['is_playing' => false, 'playing_video_id' => null]);
        }

        return ApiResponse::success(['ok' => true]);
    }

    protected function currentSession(Request $request, $user)
    {
        $fingerprint = $request->attributes->get('device_fingerprint')
            ?: $this->devices->fingerprint($request);

        $device = $user->devices()->where('device_id', $fingerprint)->first();

        if (! $device) {
            return null; // no session for *this* device — never borrow another's
        }

        return $user->sessions()
            ->where('device_id', $device->id)
            ->whereNull('revoked_at')
            ->latest('last_activity_at')
            ->first();
    }
}
