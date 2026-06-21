<?php

namespace App\Http\Controllers;

use App\Models\Video;
use App\Models\WatchHistory;
use App\Services\CatalogService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WatchHistoryController extends Controller
{
    public function __construct(protected CatalogService $catalog) {}

    public function index(Request $request): View
    {
        $history = $request->user()->watchHistory()
            ->with('video.category')
            ->latest('last_watched_at')
            ->paginate(24);

        return view('dashboard.history', compact('history'));
    }

    /**
     * Save playback progress (called periodically by the player).
     */
    public function store(Request $request, Video $video): JsonResponse
    {
        if (! setting('watch_history_enabled', true)) {
            return ApiResponse::success(['tracked' => false]);
        }

        $data = $request->validate([
            'position' => ['required', 'integer', 'min:0'],
            'duration' => ['nullable', 'integer', 'min:0'],
        ]);

        $duration = $data['duration'] ?: $video->duration;
        $percent = $duration > 0 ? min(100, (int) round($data['position'] / $duration * 100)) : 0;

        WatchHistory::updateOrCreate(
            ['user_id' => $request->user()->id, 'video_id' => $video->id],
            [
                'position' => $data['position'],
                'duration' => $duration,
                'percent' => $percent,
                'completed' => $percent >= 95,
                'last_watched_at' => now(),
            ]
        );

        return ApiResponse::success(['tracked' => true, 'percent' => $percent]);
    }

    public function destroy(Request $request, WatchHistory $history): JsonResponse
    {
        abort_unless($history->user_id === $request->user()->id, 403);
        $history->delete();

        return ApiResponse::success(null, 'Removed from history.');
    }
}
