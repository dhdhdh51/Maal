<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Payment;
use App\Models\PreviewAnalytic;
use App\Models\User;
use App\Models\UserCategoryAccess;
use App\Models\Video;
use App\Models\VideoProcessingJob;
use App\Models\WatchHistory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Aggregates platform metrics for the admin dashboard and reports.
 */
class AnalyticsService
{
    /**
     * @return array<string, mixed>
     */
    public function overview(): array
    {
        return [
            'users' => User::count(),
            'new_users_30d' => User::where('created_at', '>=', now()->subDays(30))->count(),
            'revenue_total' => (float) Payment::where('status', 'paid')->sum('total'),
            'revenue_30d' => (float) Payment::where('status', 'paid')->where('paid_at', '>=', now()->subDays(30))->sum('total'),
            'active_subscriptions' => UserCategoryAccess::query()->active()->count(),
            'expiring_7d' => UserCategoryAccess::query()->active()->whereNotNull('expires_at')
                ->whereBetween('expires_at', [now(), now()->addDays(7)])->count(),
            'failed_payments' => Payment::where('status', 'failed')->count(),
            'refunds' => Payment::where('status', 'refunded')->count(),
            'watch_hours' => round((int) WatchHistory::sum('watch_seconds') / 3600, 1),
            'videos_ready' => Video::where('processing_status', 'ready')->count(),
            'videos_processing' => Video::whereIn('processing_status', ['uploaded', 'processing', 'encoding', 'generating_preview'])->count(),
            'videos_failed' => Video::where('processing_status', 'failed')->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function previewConversion(): array
    {
        $totalPreviews = PreviewAnalytic::count();
        $converted = PreviewAnalytic::where('converted', true)->count();

        $mostConverted = PreviewAnalytic::where('converted', true)
            ->select('category_id', DB::raw('count(*) as c'))
            ->groupBy('category_id')->orderByDesc('c')->first();

        $mostWatched = PreviewAnalytic::select('video_id', DB::raw('count(*) as c'))
            ->groupBy('video_id')->orderByDesc('c')->first();

        return [
            'total_previews' => $totalPreviews,
            'conversion_rate' => $totalPreviews ? round($converted / $totalPreviews * 100, 1) : 0.0,
            'most_converted_category' => $mostConverted ? Category::find($mostConverted->category_id)?->name : null,
            'most_watched_preview' => $mostWatched ? Video::find($mostWatched->video_id)?->title : null,
            'abandoned_checkouts' => Payment::whereIn('status', ['pending', 'processing'])->count(),
        ];
    }

    /**
     * Daily revenue for the last N days.
     */
    public function revenueSeries(int $days = 30): Collection
    {
        $rows = Payment::where('status', 'paid')
            ->where('paid_at', '>=', now()->subDays($days))
            ->select(DB::raw('DATE(paid_at) as d'), DB::raw('SUM(total) as total'))
            ->groupBy('d')->orderBy('d')->get()->keyBy('d');

        return collect(range($days - 1, 0))->map(function ($i) use ($rows) {
            $date = now()->subDays($i)->toDateString();

            return ['date' => $date, 'total' => (float) ($rows[$date]->total ?? 0)];
        });
    }

    /**
     * @return Collection<int, Video>
     */
    public function topVideos(int $limit = 10): Collection
    {
        return Video::orderByDesc('views_count')->limit($limit)->get(['id', 'title', 'views_count', 'preview_plays_count']);
    }

    public function topCategoriesBySales(int $limit = 10): Collection
    {
        return Payment::where('status', 'paid')
            ->select('category_id', DB::raw('SUM(total) as revenue'), DB::raw('COUNT(*) as sales'))
            ->whereNotNull('category_id')
            ->groupBy('category_id')->orderByDesc('revenue')->limit($limit)->get()
            ->map(fn ($r) => [
                'category' => Category::find($r->category_id)?->name ?? '—',
                'revenue' => (float) $r->revenue,
                'sales' => (int) $r->sales,
            ]);
    }

    /**
     * @return array<string, int>
     */
    public function processingStatusCounts(): array
    {
        return VideoProcessingJob::select('status', DB::raw('count(*) as c'))
            ->groupBy('status')->pluck('c', 'status')->all();
    }
}
