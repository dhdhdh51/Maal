<?php

namespace App\Http\Controllers;

use App\Models\PreviewAnalytic;
use App\Models\Video;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Captures preview-engagement events that feed the conversion analytics
 * (preview plays, completion %, unlock clicks). Conversion is later flagged
 * by a PaymentCompleted listener.
 */
class PreviewAnalyticsController extends Controller
{
    public function store(Request $request, Video $video): JsonResponse
    {
        $data = $request->validate([
            'completed' => ['nullable', 'boolean'],
            'percent' => ['nullable', 'integer', 'min:0', 'max:100'],
            'clicked_unlock' => ['nullable', 'boolean'],
        ]);

        PreviewAnalytic::create([
            'video_id' => $video->id,
            'category_id' => $video->category_id,
            'user_id' => $request->user()?->id,
            'preview_completed' => $request->boolean('completed'),
            'completion_percent' => (int) ($data['percent'] ?? 0),
            'clicked_unlock' => $request->boolean('clicked_unlock'),
            'country' => $request->header('CF-IPCountry') ?? $request->user()?->country,
            'device_type' => $request->attributes->get('device_type'),
            'traffic_source' => $request->query('utm_source'),
            'session_id' => $request->session()->getId(),
        ]);

        $video->increment('preview_plays_count');

        return ApiResponse::success(['recorded' => true]);
    }
}
