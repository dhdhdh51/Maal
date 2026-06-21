<?php

namespace App\Http\Controllers;

use App\Models\Video;
use App\Services\CatalogService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FavoriteController extends Controller
{
    public function __construct(protected CatalogService $catalog) {}

    public function index(Request $request): View
    {
        $videoIds = $request->user()->favorites()->pluck('video_id');
        $videos = $this->catalog->playableQuery()->whereIn('id', $videoIds)->get();
        $this->catalog->decorate($videos, $request->user());

        return view('dashboard.favorites', compact('videos'));
    }

    public function toggle(Request $request, Video $video): JsonResponse
    {
        $existing = $request->user()->favorites()->where('video_id', $video->id)->first();

        if ($existing) {
            $existing->delete();
            $favorited = false;
        } else {
            $request->user()->favorites()->create(['video_id' => $video->id]);
            $favorited = true;
        }

        return ApiResponse::success(['favorited' => $favorited]);
    }
}
